<?php

namespace App\Http\Controllers;

use App\Enums\NotificationTypeEnum;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\TaskCommentResource;
use App\Http\Resources\TaskResource;
use App\Models\Event;
use App\Models\Sector;
use App\Models\Task;
use App\Models\TaskComment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function __construct(protected NotificationService $notificationService)
    {
    }

    /**
     * Quadro de tarefas. Filtros: scope (mine, shared, me, all), sector_id, user_id, event_id,
     * search, overdue, order (due_date ou urgency) e from/to (prazo entre duas datas, para o
     * calendário). Concluídas aparecem só dos últimos 60 dias, a não ser que include_old_done=1
     * ou que o período (from/to) seja informado.
     */
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = $this->filteredQuery($request, $userId);

        $summary = [
            'overdue_or_today' => (clone $query)->overdueOrToday()->count(),
        ];

        if ($request->boolean('overdue')) {
            $query->overdueOrToday();
        }

        $hasPeriod = $request->filled('from') || $request->filled('to');
        if ($request->filled('from')) {
            $query->whereDate('due_date', '>=', $request->input('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('due_date', '<=', $request->input('to'));
        }

        if (!$hasPeriod && !$request->boolean('include_old_done')) {
            $query->where(function ($q) {
                $q->where('status', '!=', 'done')->orWhere('completed_at', '>=', now()->subDays(60));
            });
        }

        $urgency = "CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END";
        if ($request->input('order') === 'urgency') {
            $query->orderByRaw($urgency)->orderByRaw('due_date IS NULL')->orderBy('due_date');
        } else {
            $query->orderByRaw('due_date IS NULL')->orderBy('due_date')->orderBy('due_time')->orderByRaw($urgency);
        }
        $query->orderByDesc('id');

        $tasks = $query->with(['users:id,name', 'sector:id,name', 'event:id,name,date', 'creator:id,name'])
            ->withCount('comments')
            ->limit((int) $request->input('limit', 500))
            ->get();

        return response()->json([
            'data' => TaskResource::collection($tasks),
            'summary' => $summary,
        ], 200);
    }

    private function filteredQuery(Request $request, int $userId)
    {
        $query = Task::query();

        // mine: criadas pelo usuário; shared: criadas por outra pessoa com ele como responsável;
        // me: as duas juntas (calendário); all: todas.
        match ($request->input('scope')) {
            'mine' => $query->where('created_by', $userId),
            'shared' => $query->sharedWith($userId),
            'me' => $query->involving($userId),
            default => null,
        };

        if ($request->filled('sector_id')) {
            $query->where('sector_id', $request->input('sector_id'));
        }

        if ($request->filled('user_id')) {
            $query->assignedTo((int) $request->input('user_id'));
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('search')) {
            $query->filterByField('search', $request->input('search'));
        }

        return $query;
    }

    /** Listas para o quadro e o formulário: áreas (setores), funcionários ativos e eventos recentes. */
    public function formData()
    {
        return response()->json([
            'sectors' => Sector::query()->orderBy('id')->get(['id', 'name']),
            'users' => User::query()
                ->where('is_employee', true)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'events' => Event::query()
                ->whereDate('date', '>=', now()->subDays(30)->toDateString())
                ->orderBy('date')
                ->limit(500)
                ->get(['id', 'name', 'date']),
        ], 200);
    }

    public function store(StoreTaskRequest $request)
    {
        $data = $request->validated();
        $userIds = $data['user_ids'] ?? [];
        unset($data['user_ids']);

        $data['status'] = $data['status'] ?? 'todo';
        $data['priority'] = $data['priority'] ?? 'medium';
        $data['recurrence'] = $data['recurrence'] ?? 'none';
        $data['completed_at'] = $data['status'] === 'done' ? now() : null;
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $task = DB::transaction(function () use ($data, $userIds) {
            $task = Task::create($data);
            $task->users()->sync($userIds);
            return $task;
        });

        $this->notifyUsers($task, $userIds, $request->user());
        $this->createNextOccurrence($task);

        return response()->json(new TaskResource($task->fresh()), 201);
    }

    public function show(Task $task)
    {
        return response()->json(new TaskResource($task), 200);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->validated();
        $newUserIds = null;
        if (array_key_exists('user_ids', $data)) {
            $newUserIds = $data['user_ids'] ?? [];
            unset($data['user_ids']);
        }

        if (array_key_exists('status', $data)) {
            $data = array_merge($data, $this->completionFields($task, $data['status']));
        }
        $data['updated_by'] = $request->user()->id;

        $added = [];
        DB::transaction(function () use ($task, $data, $newUserIds, &$added) {
            $task->update($data);
            if ($newUserIds !== null) {
                $added = $task->users()->sync($newUserIds)['attached'];
            }
        });

        $this->notifyUsers($task, $added, $request->user());
        $this->createNextOccurrence($task);

        return response()->json(new TaskResource($task->fresh()), 200);
    }

    public function updateStatus(Request $request, Task $task)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(Task::STATUSES)],
        ], [
            'status.required' => 'Informe a situação da tarefa.',
            'status.in' => 'Situação inválida.',
        ]);

        $task->update(array_merge(
            ['status' => $data['status'], 'updated_by' => $request->user()->id],
            $this->completionFields($task, $data['status'])
        ));

        $next = $this->createNextOccurrence($task);

        return response()->json([
            'task' => new TaskResource($task->fresh()),
            'next_task' => $next ? new TaskResource($next) : null,
        ], 200);
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return response()->json([], 204);
    }

    public function comments(Task $task)
    {
        return response()->json(TaskCommentResource::collection($task->comments()->with('user:id,name')->get()), 200);
    }

    public function storeComment(Request $request, Task $task)
    {
        $data = $request->validate(['text' => 'required|string'], ['text.required' => 'Escreva o comentário.']);

        $comment = TaskComment::create([
            'task_id' => $task->id,
            'user_id' => $request->user()->id,
            'text' => $data['text'],
        ]);

        return response()->json(new TaskCommentResource($comment), 201);
    }

    public function destroyComment(Request $request, Task $task, TaskComment $comment)
    {
        if ($comment->task_id !== $task->id) {
            abort(404);
        }

        if ($comment->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'Só quem escreveu pode apagar o comentário.'], 403);
        }

        $comment->delete();

        return response()->json([], 204);
    }

    private function completionFields(Task $task, string $status): array
    {
        if ($status === 'done') {
            return ['completed_at' => $task->completed_at ?? now()];
        }

        return ['completed_at' => null];
    }

    /** Ao concluir uma tarefa que se repete, cria a próxima (uma vez só). */
    private function createNextOccurrence(Task $task): ?Task
    {
        if ($task->status !== 'done' || $task->recurrence === 'none' || $task->next_task_id) {
            return null;
        }

        $next = DB::transaction(function () use ($task) {
            $next = Task::create([
                'title' => $task->title,
                'description' => $task->description,
                'sector_id' => $task->sector_id,
                'event_id' => $task->event_id,
                'due_date' => $task->nextDueDate(),
                'due_time' => $task->due_time,
                'priority' => $task->priority,
                'status' => 'todo',
                'recurrence' => $task->recurrence,
                'created_by' => $task->created_by,
                'updated_by' => $task->updated_by,
            ]);
            $next->users()->sync($task->users()->pluck('users.id'));
            $task->update(['next_task_id' => $next->id]);

            return $next;
        });

        return $next->fresh();
    }

    /** Avisa quem entrou na tarefa, menos quem fez a ação. */
    private function notifyUsers(Task $task, array $userIds, User $actor): void
    {
        $message = "{$actor->name} incluiu você na tarefa \"{$task->title}\"";
        if ($task->due_date) {
            $message .= ' com prazo em ' . $task->due_date->format('d/m/Y');
        }

        foreach (User::whereIn('id', $userIds)->where('id', '!=', $actor->id)->get() as $user) {
            try {
                $this->notificationService->create(
                    NotificationTypeEnum::TASK_ASSIGNED,
                    $user,
                    'Nova tarefa para você',
                    $message . '.',
                    ['task_id' => $task->id, 'link' => '/admin/tasks'],
                    $task,
                );
            } catch (\Throwable $e) {
                // A tarefa já foi salva; uma falha no aviso não deve desfazer isso.
                Log::warning('Falha ao notificar responsável da tarefa', ['task_id' => $task->id, 'error' => $e->getMessage()]);
            }
        }
    }
}
