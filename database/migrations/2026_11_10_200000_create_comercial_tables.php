<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Menu Comercial (igual ao do Parthenon): Meu dia, Funil de vendas, Agenda de datas, Gráficos de vendas,
 * Relatório de comissão, Comunicação (WhatsApp/Instagram) e Licitações, mais as Regras comerciais,
 * Cidades atendidas e Mercado por consultor em Configurações.
 * Junta numa migração só o estado final das tabelas do Parthenon (03/11 a 10/11/2026).
 */
return new class extends Migration
{
    private array $permissions = [
        ['slug' => 'comercial-funil-visualizar', 'name' => 'Visualizar Funil de vendas'],
        ['slug' => 'comercial-funil-criar', 'name' => 'Criar Leads no Funil'],
        ['slug' => 'comercial-funil-editar', 'name' => 'Editar Leads do Funil'],
        ['slug' => 'comercial-funil-excluir', 'name' => 'Excluir Leads do Funil'],
        ['slug' => 'comercial-funil-ver-todos', 'name' => 'Ver leads de todos os consultores'],
        ['slug' => 'comercial-funil-aprovar', 'name' => 'Aprovar descontos e viabilidade do funil'],
        ['slug' => 'comercial-comunicacao-visualizar', 'name' => 'Visualizar Comunicação'],
        ['slug' => 'comercial-comunicacao-criar', 'name' => 'Cadastrar canais da Comunicação'],
        ['slug' => 'comercial-comunicacao-editar', 'name' => 'Configurar a Comunicação e atribuir conversas'],
        ['slug' => 'comercial-comunicacao-excluir', 'name' => 'Excluir canais da Comunicação'],
        ['slug' => 'comercial-comunicacao-responder', 'name' => 'Responder conversas da Comunicação'],
        ['slug' => 'comercial-comunicacao-ver-todos', 'name' => 'Ver conversas de todos os consultores'],
        ['slug' => 'licitacoes-visualizar', 'name' => 'Visualizar Licitações'],
        ['slug' => 'licitacoes-criar', 'name' => 'Criar Licitações'],
        ['slug' => 'licitacoes-editar', 'name' => 'Editar Licitações'],
        ['slug' => 'licitacoes-excluir', 'name' => 'Excluir Licitações'],
        ['slug' => 'configuracoes-cidades-visualizar', 'name' => 'Visualizar Cidades atendidas'],
        ['slug' => 'configuracoes-cidades-criar', 'name' => 'Criar Cidades atendidas'],
        ['slug' => 'configuracoes-cidades-editar', 'name' => 'Editar Cidades atendidas'],
        ['slug' => 'configuracoes-cidades-excluir', 'name' => 'Excluir Cidades atendidas'],
        ['slug' => 'configuracoes-regras-comerciais-visualizar', 'name' => 'Visualizar Regras comerciais'],
        ['slug' => 'configuracoes-regras-comerciais-editar', 'name' => 'Editar Regras comerciais'],
        ['slug' => 'configuracoes-mercado-visualizar', 'name' => 'Visualizar Mercado por consultor'],
        ['slug' => 'configuracoes-mercado-criar', 'name' => 'Criar Mercado por consultor'],
        ['slug' => 'configuracoes-mercado-editar', 'name' => 'Editar Mercado por consultor'],
        ['slug' => 'configuracoes-mercado-excluir', 'name' => 'Excluir Mercado por consultor'],
    ];

    /** Motivos de perda da gerência comercial, em grupos. */
    private array $lostReasons = [
        ['group' => 'Não era oportunidade', 'name' => 'Lead duplicado', 'code' => null],
        ['group' => 'Não era oportunidade', 'name' => 'Não era cliente', 'code' => null],
        ['group' => 'Não era oportunidade', 'name' => 'Contato inválido', 'code' => null],
        ['group' => 'Sem retorno', 'name' => 'Sem resposta', 'code' => 'sem_resposta'],
        ['group' => 'Fora do perfil', 'name' => 'Cidade não atendida', 'code' => null],
        ['group' => 'Fora do perfil', 'name' => 'Abaixo do mínimo de convidados', 'code' => null],
        ['group' => 'Fora do perfil', 'name' => 'Tipo de evento não atendido', 'code' => null],
        ['group' => 'Data', 'name' => 'Data indisponível', 'code' => null],
        ['group' => 'Cliente desistiu', 'name' => 'Evento cancelado ou adiado sem data', 'code' => null],
        ['group' => 'Cliente desistiu', 'name' => 'Mudou o formato do evento', 'code' => null],
        ['group' => 'Concorrência', 'name' => 'Fechou com concorrente', 'code' => 'concorrente'],
        ['group' => 'Preço', 'name' => 'Acima do orçamento do cliente', 'code' => null],
        ['group' => 'Produto', 'name' => 'Cardápio ou serviço não agradou', 'code' => null],
        ['group' => 'Licitação', 'name' => 'Licitação não vencida', 'code' => null],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('app_settings')) {
            Schema::create('app_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->longText('value')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('lost_reasons', 'group')) {
            Schema::table('lost_reasons', function (Blueprint $table) {
                $table->string('group', 50)->nullable()->after('name');
                $table->string('code', 30)->nullable()->after('group');
            });
        }

        Schema::create('served_cities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('state', 2)->nullable();
            $table->boolean('served')->default(true);
            $table->unsignedInteger('min_guests')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('segment_rules')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('instagram')->nullable();
            $table->string('city')->nullable();
            $table->foreignId('served_city_id')->nullable()->constrained('served_cities')->nullOnDelete();
            $table->string('source', 30)->nullable();
            $table->string('temperature', 10)->nullable();
            $table->json('tags')->nullable();
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();
            $table->foreignId('event_type_id')->nullable()->constrained('event_types')->nullOnDelete();
            $table->date('event_date')->nullable();
            $table->unsignedInteger('guests')->nullable();
            $table->decimal('estimated_value', 12, 2)->nullable();
            $table->decimal('expected_viability', 5, 2)->nullable();
            $table->decimal('real_viability', 5, 2)->nullable();
            $table->date('meeting_date')->nullable();
            $table->string('meeting_result', 30)->nullable();
            $table->text('meeting_notes')->nullable();
            $table->date('proposal_sent_at')->nullable();
            $table->date('proposal_valid_until')->nullable();
            $table->decimal('final_value', 12, 2)->nullable();
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->string('approval_status', 20)->nullable();
            $table->text('approval_note')->nullable();
            $table->text('approval_request_note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('deposit_value', 12, 2)->nullable();
            $table->date('deposit_date')->nullable();
            $table->boolean('tasting')->nullable();
            $table->date('tasting_date')->nullable();
            $table->string('tasting_result', 30)->nullable();
            $table->string('tasting_participants')->nullable();
            $table->string('stage', 30)->default('novo');
            $table->unsignedInteger('position')->default(0);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lost_reason_id')->nullable()->constrained('lost_reasons')->nullOnDelete();
            $table->text('lost_note')->nullable();
            $table->string('competitor')->nullable();
            $table->foreignId('competing_company_id')->nullable()->constrained('competing_companies')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('event_id')->nullable()->constrained('events')->nullOnDelete();
            $table->unsignedBigInteger('pipedrive_id')->nullable()->index();
            $table->timestamp('imported_at')->nullable();
            $table->string('imported_owner', 120)->nullable();
            $table->date('next_contact_date')->nullable();
            $table->string('next_action')->nullable();
            $table->text('observation')->nullable();
            $table->timestamp('stage_changed_at')->nullable();
            $table->timestamp('first_contact_at')->nullable();
            $table->timestamp('pre_reserve_released_at')->nullable();
            $table->foreignId('pre_reserve_released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['stage', 'position']);
        });

        Schema::create('lead_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->string('result', 20)->nullable();
            $table->dateTime('happened_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['lead_id', 'type']);
        });

        Schema::create('lead_stage_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->string('from_stage', 40)->nullable();
            $table->string('to_stage', 40);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->index(['to_stage', 'changed_at']);
        });

        Schema::create('lead_covers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('absent_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('substitute_user_id')->constrained('users')->cascadeOnDelete();
            $table->date('starts_at');
            $table->date('ends_at');
            $table->string('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('lead_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->unique()->constrained('leads')->cascadeOnDelete();
            $table->decimal('ideal_value', 14, 2)->nullable();
            $table->decimal('sold_value', 14, 2)->nullable();
            $table->decimal('gifts_value', 14, 2)->nullable();
            $table->decimal('courtesy_value', 14, 2)->nullable();
            $table->decimal('percent', 6, 3)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('commercial_rules', function (Blueprint $table) {
            $table->id();
            $table->json('high_season_months')->nullable();
            $table->decimal('viability_min_default', 5, 2)->default(35);
            $table->json('viability_min_by_event_type')->nullable();
            $table->decimal('max_discount_percent', 5, 2)->nullable();
            $table->unsignedInteger('proposal_validity_days')->default(10);
            $table->unsignedInteger('pre_reserve_days')->default(10);
            $table->unsignedInteger('max_events_per_day')->nullable();
            $table->unsignedInteger('max_guests_per_day')->nullable();
            $table->unsignedInteger('max_events_per_week')->nullable();
            $table->unsignedInteger('complex_max_events_per_day')->nullable();
            $table->unsignedInteger('complex_max_events_per_week')->nullable();
            $table->unsignedInteger('complex_guests_threshold')->nullable();
            $table->json('complex_keywords')->nullable();
            $table->unsignedInteger('max_small_per_weekend')->nullable();
            $table->json('small_keywords')->nullable();
            $table->unsignedInteger('max_pre_events_per_weekend')->nullable();
            $table->unsignedInteger('pre_event_max_guests')->nullable();
            $table->json('pre_event_keywords')->nullable();
            $table->unsignedInteger('first_contact_hours')->default(24);
            $table->string('business_hours_start', 5)->default('08:00');
            $table->string('business_hours_end', 5)->default('18:00');
            $table->json('business_days')->nullable();
            $table->json('rotation_user_ids')->nullable();
            $table->json('rotation_event_type_ids')->nullable();
            $table->boolean('rotation_include_unknown')->default(true);
            $table->unsignedBigInteger('rotation_last_user_id')->nullable();
            $table->json('segments')->nullable();
            $table->json('rotation_pointers')->nullable();
            $table->unsignedInteger('max_distance_km')->nullable();
            $table->unsignedInteger('no_answer_attempts')->default(6);
            $table->unsignedInteger('no_answer_channels')->default(2);
            $table->unsignedInteger('no_answer_days')->default(14);
            $table->json('stage_max_days')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('commission_percent_default', 6, 3)->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('market_rules', function (Blueprint $table) {
            $table->id();
            $table->string('city');
            $table->string('state', 2)->nullable();
            $table->string('segment', 40);
            $table->json('user_ids')->nullable();
            $table->json('consultant_names')->nullable();
            $table->unsignedInteger('min_guests')->nullable();
            $table->unsignedInteger('min_guests_high')->nullable();
            $table->unsignedInteger('distance_km')->nullable();
            $table->string('potential', 40)->nullable();
            $table->boolean('served')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['segment', 'city']);
        });

        Schema::create('messaging_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 20);
            $table->string('name');
            $table->string('external_id');
            $table->string('display')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['channel', 'external_id']);
        });

        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('messaging_account_id')->constrained('messaging_accounts')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('contact_external_id');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone', 30)->nullable();
            $table->string('contact_username')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('status', 20)->default('open');
            $table->boolean('internal')->default(false);
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_direction', 5)->nullable();
            $table->string('last_message_preview', 160)->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->timestamp('waiting_since')->nullable();
            $table->timestamps();
            $table->unique(['messaging_account_id', 'contact_external_id']);
            $table->index(['status', 'last_message_at']);
        });

        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->cascadeOnDelete();
            $table->string('direction', 5);
            $table->string('type', 20)->default('text');
            $table->text('body')->nullable();
            $table->string('media_url')->nullable();
            $table->string('media_mime')->nullable();
            $table->string('media_name')->nullable();
            $table->string('external_id')->nullable()->index();
            $table->string('status', 20)->default('received');
            $table->text('error')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['conversation_id', 'sent_at']);
        });

        Schema::create('biddings', function (Blueprint $table) {
            $table->id();
            $table->string('agency');
            $table->string('notice_number')->nullable();
            $table->date('opening_date')->nullable();
            $table->time('opening_time')->nullable();
            $table->string('status', 20)->default('prevista');
            $table->text('observation')->nullable();
            $table->longText('summary')->nullable();
            $table->foreignId('notice_file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('notice_file_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('bidding_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bidding_id')->constrained('biddings')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment');
            $table->timestamps();
        });

        $this->seedRules();
        $this->seedLostReasons();

        DB::table('notification_types')->insertOrIgnore([
            ['name' => 'lead_approval_requested', 'description' => 'Funil: pedido de aprovação', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'lead_approval_decided', 'description' => 'Funil: aprovação respondida', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'lead_assigned', 'description' => 'Funil: lead distribuído', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->seedPermissions();
    }

    private function seedRules(): void
    {
        $fold = function (?string $v): string {
            $v = mb_strtolower(trim((string) $v));
            $a = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $v);

            return preg_replace('/[^a-z0-9 ]/', '', $a !== false ? $a : $v) ?? '';
        };
        $types = DB::table('event_types')->whereNull('deleted_at')->get(['id', 'name']);
        $typeIds = fn (array $words) => $types->filter(function ($t) use ($words, $fold) {
            foreach ($words as $w) {
                if (str_contains($fold($t->name), $w)) {
                    return true;
                }
            }

            return false;
        })->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        DB::table('commercial_rules')->insert([
            'high_season_months' => json_encode([11, 12, 1]),
            'viability_min_default' => 35,
            'viability_min_by_event_type' => json_encode([]),
            'proposal_validity_days' => 10,
            'pre_reserve_days' => 10,
            'complex_keywords' => json_encode([]),
            'small_keywords' => json_encode([]),
            'pre_event_keywords' => json_encode(['pré-evento', 'pre-evento', 'pré evento', 'pre evento']),
            'first_contact_hours' => 24,
            'business_hours_start' => '08:00',
            'business_hours_end' => '18:00',
            'business_days' => json_encode([1, 2, 3, 4, 5]),
            'rotation_include_unknown' => true,
            'segments' => json_encode([
                ['key' => 'casamentos', 'name' => 'Casamentos e aniversários', 'event_type_ids' => $typeIds(['casamento', 'aniversario', 'bodas', 'debutante', '15 anos'])],
                ['key' => 'corporativos', 'name' => 'Corporativos', 'event_type_ids' => $typeIds(['corporativ', 'comporativ', 'empresa'])],
                ['key' => 'formaturas', 'name' => 'Formaturas', 'event_type_ids' => $typeIds(['formatura'])],
            ]),
            'rotation_pointers' => json_encode(new \stdClass()),
            'no_answer_attempts' => 6,
            'no_answer_channels' => 2,
            'no_answer_days' => 14,
            'stage_max_days' => json_encode([
                'novo' => 1, 'em_contato' => 3, 'reuniao' => 2, 'proposta_enviada' => 10, 'follow_up' => 10,
                'negociacao' => 10, 'aguardando_assinatura' => 5, 'nutricao' => 30, 'licitacao' => 10,
            ]),
            'payment_terms' => 'Sinal na assinatura do contrato; saldo conforme negociado até a data do evento.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function seedLostReasons(): void
    {
        foreach ($this->lostReasons as $reason) {
            $existing = DB::table('lost_reasons')->where('name', $reason['name'])->whereNull('deleted_at')->first();
            if ($existing) {
                DB::table('lost_reasons')->where('id', $existing->id)->update(['group' => $reason['group'], 'code' => $reason['code'], 'updated_at' => now()]);
            } else {
                DB::table('lost_reasons')->insert($reason + ['created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    private function seedPermissions(): void
    {
        foreach ($this->permissions as $permission) {
            Permission::firstOrCreate(
                ['slug' => $permission['slug'], 'guard_name' => 'api'],
                ['name' => $permission['name']]
            );
        }

        $all = array_column($this->permissions, 'slug');
        // Administrador tem tudo, menos responder conversas (no Parthenon só o consultor responde).
        $admin = Role::where('guard_name', 'api')->where(fn ($q) => $q->where('slug', 'admin')->orWhere('name', 'Admin'))->first();
        $admin?->permissions()->syncWithoutDetaching(
            Permission::where('guard_name', 'api')->whereIn('slug', array_diff($all, ['comercial-comunicacao-responder']))->pluck('id')
        );

        // Perfil Comercial do Fastbar = consultor do Parthenon: funil, comunicação e consulta das cidades/mercado.
        $comercial = Role::where('guard_name', 'api')->where(fn ($q) => $q->where('slug', 'comercial')->orWhere('name', 'Comercial'))->first();
        $comercial?->permissions()->syncWithoutDetaching(Permission::where('guard_name', 'api')->whereIn('slug', [
            'comercial-funil-visualizar', 'comercial-funil-criar', 'comercial-funil-editar',
            'comercial-comunicacao-visualizar', 'comercial-comunicacao-criar', 'comercial-comunicacao-responder',
            'licitacoes-visualizar', 'configuracoes-cidades-visualizar', 'configuracoes-mercado-visualizar',
        ])->pluck('id'));

        $this->forgetPermissionCache();
    }

    public function down(): void
    {
        Schema::dropIfExists('bidding_comments');
        Schema::dropIfExists('biddings');
        Schema::dropIfExists('messages');
        Schema::dropIfExists('conversations');
        Schema::dropIfExists('messaging_accounts');
        Schema::dropIfExists('market_rules');
        Schema::dropIfExists('commercial_rules');
        Schema::dropIfExists('lead_commissions');
        Schema::dropIfExists('lead_covers');
        Schema::dropIfExists('lead_stage_changes');
        Schema::dropIfExists('lead_activities');
        Schema::dropIfExists('lead_comments');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('served_cities');

        DB::table('notification_types')->whereIn('name', ['lead_approval_requested', 'lead_approval_decided', 'lead_assigned'])->delete();
        Permission::whereIn('slug', array_column($this->permissions, 'slug'))->get()->each->delete();
        $this->forgetPermissionCache();
    }

    private function forgetPermissionCache(): void
    {
        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
