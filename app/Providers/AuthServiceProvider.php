<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Gate;
use App\Models\Ceremonial;
use App\Models\Client;
use App\Models\Collaborator;
use App\Models\Contract;
use App\Policies\CeremonialPolicy;
use App\Policies\ClientPolicy;
use App\Policies\CollaboratorPolicy;
use App\Policies\ContractPolicy;


class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Ceremonial::class => CeremonialPolicy::class,
        Client::class => ClientPolicy::class,
        Collaborator::class => CollaboratorPolicy::class,
        Contract::class => ContractPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        $this->registerPermissions();    

        Gate::after(function ($user, $ability, $result, $arguments) {
            if (!$result) {
                abort(403, 'Forbidden');
            }
        });
    }

    /**
     * Register any permissions.
     */
    public function registerPermissions(): void
    {
        $tables = DB::select('SHOW TABLES');

        if (empty($tables)) {
            return;
        }

        $tables =  array_map(function ($object) {
            return $object->{'Tables_in_' . env('DB_DATABASE')};
        }, $tables);

        foreach ($tables as $table) {
            $table = str_replace('_', '-', $table);
            $this->createPermissionsForTable($table);
        }
    }

    private function createPermissionsForTable($table)
    {
        $operations = ['list', 'read', 'create', 'update', 'delete'];

        foreach ($operations as $operation) {
            $permission = "{$operation}-{$table}";
            if (!Permission::where('name', $permission)->exists()) {
                Permission::create(['name' => $permission, 'guard_name' => 'api']);
            }
        }
    }
}
