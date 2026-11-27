<?php

namespace App\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class DataFixSnapshot
{
    public function path(string $name): string
    {
        return storage_path("app/data-fixes/{$name}.json");
    }

    /**
     * @param array<int, array{table: string, id: int, set: array<string, mixed>}> $changes
     */
    public function apply(string $name, array $changes): int
    {
        $entries = [];

        foreach ($changes as $change) {
            $row = DB::table($change['table'])->where('id', $change['id'])->first();

            if (!$row) {
                continue;
            }

            $entries[] = [
                'table' => $change['table'],
                'id' => $change['id'],
                'before' => Arr::only((array) $row, array_keys($change['set'])),
                'after' => $change['set'],
                'row_before' => (array) $row,
            ];
        }

        if (empty($entries)) {
            return 0;
        }

        $path = $this->path($name);

        if (File::exists($path)) {
            throw new RuntimeException("Já existe um snapshot em {$path}.");
        }

        // O snapshot é gravado antes de alterar o banco: se o disco falhar, nada muda.
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            'name' => $name,
            'applied_at' => now()->toDateTimeString(),
            'revert_command' => "php artisan data-fix:revert {$name}",
            'changes' => $entries,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        try {
            DB::transaction(function () use ($entries) {
                foreach ($entries as $entry) {
                    DB::table($entry['table'])->where('id', $entry['id'])->update($entry['after']);
                }
            });
        } catch (Throwable $e) {
            File::delete($path);
            throw $e;
        }

        return count($entries);
    }

    /**
     * @return array{reverted: string[], skipped: string[], archived_to: ?string}
     */
    public function revert(string $name, bool $force = false): array
    {
        $path = $this->path($name);

        if (!File::exists($path)) {
            throw new RuntimeException("Snapshot não encontrado: {$path}.");
        }

        $snapshot = json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR);
        $reverted = [];
        $skipped = [];

        DB::transaction(function () use ($snapshot, $force, &$reverted, &$skipped) {
            foreach (array_reverse($snapshot['changes']) as $entry) {
                $label = "{$entry['table']}#{$entry['id']}";
                $row = DB::table($entry['table'])->where('id', $entry['id'])->first();

                if (!$row) {
                    $skipped[] = "{$label}: a linha não existe mais";
                    continue;
                }

                $current = Arr::only((array) $row, array_keys($entry['after']));

                if (!$force && !$this->sameValues($current, $entry['after'])) {
                    $skipped[] = "{$label}: foi alterada depois da correção";
                    continue;
                }

                DB::table($entry['table'])->where('id', $entry['id'])->update($entry['before']);
                $reverted[] = $label;
            }
        });

        $archivedTo = null;

        // Com linhas ignoradas o arquivo fica no lugar, para dar para rodar de novo com --force.
        if (empty($skipped)) {
            $archivedTo = preg_replace('/\.json$/', '.reverted-' . now()->format('YmdHis') . '.json', $path);
            File::move($path, $archivedTo);
        }

        return ['reverted' => $reverted, 'skipped' => $skipped, 'archived_to' => $archivedTo];
    }

    private function sameValues(array $current, array $expected): bool
    {
        foreach ($expected as $column => $value) {
            if ((string) ($current[$column] ?? '') !== (string) ($value ?? '')) {
                return false;
            }
        }

        return true;
    }
}
