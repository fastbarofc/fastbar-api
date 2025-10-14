<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        Schema::disableForeignKeyConstraints();

        DB::table('model_has_permissions')->truncate();
        DB::table('permissions')->truncate();
        DB::table('role_has_permissions')->truncate();

        Schema::enableForeignKeyConstraints();

        $permissions = [
            // Calendário
            ['slug' => 'calendario-visualizar', 'name' => 'Visualizar Calendário'],
        
            // Requisições
            ['slug' => 'requisicoes-visualizar', 'name' => 'Visualizar Requisições'],
        
            // Colaboradores
            ['slug' => 'colaboradores-visualizar', 'name' => 'Visualizar Colaboradores'],
            ['slug' => 'colaboradores-criar', 'name' => 'Criar Colaboradores'],
            ['slug' => 'colaboradores-editar', 'name' => 'Editar Colaboradores'],
            ['slug' => 'colaboradores-excluir', 'name' => 'Excluir Colaboradores'],
        
            // Clientes
            ['slug' => 'clientes-visualizar', 'name' => 'Visualizar Clientes'],
            ['slug' => 'clientes-visualizar-eventos', 'name' => 'Visualizar eventos do cliente'],
            ['slug' => 'clientes-criar', 'name' => 'Criar Clientes'],
            ['slug' => 'clientes-editar', 'name' => 'Editar Clientes'],
            ['slug' => 'clientes-excluir', 'name' => 'Excluir Clientes'],
        
            // Eventos
            ['slug' => 'eventos-visualizar', 'name' => 'Visualizar Eventos'],
            ['slug' => 'eventos-criar', 'name' => 'Criar Eventos'],
            ['slug' => 'eventos-editar', 'name' => 'Editar Eventos'],
            ['slug' => 'eventos-excluir', 'name' => 'Excluir Eventos'],
            ['slug' => 'eventos-gerenciar-eventos', 'name' => 'Gerenciar eventos'],
            ['slug' => 'eventos-duplicar-eventos', 'name' => 'Duplicar eventos'],
            ['slug' => 'eventos-imprimir-informacoes-eventos', 'name' => 'Imprimir informações dos eventos'],
        
            // Locais
            ['slug' => 'locais-visualizar', 'name' => 'Visualizar Locais'],
            ['slug' => 'locais-criar', 'name' => 'Criar Locais'],
            ['slug' => 'locais-editar', 'name' => 'Editar Locais'],
            ['slug' => 'locais-excluir', 'name' => 'Excluir Locais'],
        
            // Kits
            ['slug' => 'kits-visualizar', 'name' => 'Visualizar Kits'],
            ['slug' => 'kits-editar', 'name' => 'Editar Kits'],
        
            // Produtos
            ['slug' => 'produtos-visualizar', 'name' => 'Visualizar Produtos'],
            ['slug' => 'produtos-criar', 'name' => 'Criar Produtos'],
            ['slug' => 'produtos-editar', 'name' => 'Editar Produtos'],
            ['slug' => 'produtos-excluir', 'name' => 'Excluir Produtos'],
        
            // Parceiros
            ['slug' => 'parceiros-visualizar', 'name' => 'Visualizar Parceiros'],
            ['slug' => 'parceiros-criar', 'name' => 'Criar Parceiros'],
            ['slug' => 'parceiros-editar', 'name' => 'Editar Parceiros'],
            ['slug' => 'parceiros-excluir', 'name' => 'Excluir Parceiros'],
        
            // Fornecedores
            ['slug' => 'fornecedores-visualizar', 'name' => 'Visualizar Fornecedores'],
            ['slug' => 'fornecedores-criar', 'name' => 'Criar Fornecedores'],
            ['slug' => 'fornecedores-editar', 'name' => 'Editar Fornecedores'],
            ['slug' => 'fornecedores-excluir', 'name' => 'Excluir Fornecedores'],
        
            // Empresas
            ['slug' => 'empresas-visualizar', 'name' => 'Visualizar Empresas'],
            ['slug' => 'empresas-criar', 'name' => 'Criar Empresas'],
            ['slug' => 'empresas-editar', 'name' => 'Editar Empresas'],
            ['slug' => 'empresas-excluir', 'name' => 'Excluir Empresas'],
        
            // Relatórios
            ['slug' => 'relatorios-gerar-relatorio-eventos', 'name' => 'Gerar relatório de eventos'],
            ['slug' => 'relatorios-gerar-relatorio-itens', 'name' => 'Gerar relatório de itens'],
            ['slug' => 'relatorios-gerar-relatorio-mesas', 'name' => 'Gerar relatório de mesas'],
            ['slug' => 'relatorios-gerar-relatorio-falhas', 'name' => 'Gerar relatório de falhas'],
        
            // Gráficos
            ['slug' => 'graficos-visualizar', 'name' => 'Visualizar Gráficos'],
        
            // Operação Financeira
            ['slug' => 'financeiro-operacao-financeira-visualizar', 'name' => 'Visualizar Operação Financeira'],
            ['slug' => 'financeiro-operacao-financeira-criar', 'name' => 'Criar Operação Financeira'],
            ['slug' => 'financeiro-operacao-financeira-editar', 'name' => 'Editar Operação Financeira'],
            ['slug' => 'financeiro-operacao-financeira-excluir', 'name' => 'Excluir Operação Financeira'],
        
            // Centro de Custo
            ['slug' => 'financeiro-centro-de-custo-visualizar', 'name' => 'Visualizar Centro de Custo'],
            ['slug' => 'financeiro-centro-de-custo-criar', 'name' => 'Criar Centro de Custo'],
            ['slug' => 'financeiro-centro-de-custo-editar', 'name' => 'Editar Centro de Custo'],
            ['slug' => 'financeiro-centro-de-custo-excluir', 'name' => 'Excluir Centro de Custo'],
        
            // Pedido de compras
            ['slug' => 'compras-pedidos-compras-visualizar', 'name' => 'Visualizar pedido de compras'],
            ['slug' => 'compras-pedidos-compras-criar', 'name' => 'Criar pedido de compras'],
            ['slug' => 'compras-pedidos-compras-editar', 'name' => 'Editar pedido de compras'],
            ['slug' => 'compras-pedidos-compras-excluir', 'name' => 'Excluir pedido de compras'],
            
            // Ordens de compras
            ['slug' => 'compras-ordem-compras-visualizar', 'name' => 'Visualizar ordem de compras'],
            ['slug' => 'compras-ordem-compras-criar', 'name' => 'Criar ordem de compras'],
            ['slug' => 'compras-ordem-compras-editar', 'name' => 'Editar ordem de compras'],
            ['slug' => 'compras-ordem-compras-excluir', 'name' => 'Excluir ordem de compras'],
            
            // Grupos de usuários
            ['slug' => 'configuracoes-grupos-usuarios-visualizar', 'name' => 'Visualizar Grupos de Usuários'],
            ['slug' => 'configuracoes-grupos-usuarios-criar', 'name' => 'Criar Grupos de Usuários'],
            ['slug' => 'configuracoes-grupos-usuarios-editar', 'name' => 'Editar Grupos de Usuários'],
            ['slug' => 'configuracoes-grupos-usuarios-excluir', 'name' => 'Excluir Grupos de Usuários'],
        
            // Funções colaboradores
            ['slug' => 'configuracoes-funcoes-colaboradores-visualizar', 'name' => 'Visualizar Funções Colaboradores'],
            ['slug' => 'configuracoes-funcoes-colaboradores-criar', 'name' => 'Criar Funções Colaboradores'],
            ['slug' => 'configuracoes-funcoes-colaboradores-editar', 'name' => 'Editar Funções Colaboradores'],
            ['slug' => 'configuracoes-funcoes-colaboradores-excluir', 'name' => 'Excluir Funções Colaboradores'],
        
            // Funções colaborador evento
            ['slug' => 'configuracoes-funcoes-colaborador-evento-visualizar', 'name' => 'Visualizar Funções Colaborador Evento'],
            ['slug' => 'configuracoes-funcoes-colaborador-evento-criar', 'name' => 'Criar Funções Colaborador Evento'],
            ['slug' => 'configuracoes-funcoes-colaborador-evento-editar', 'name' => 'Editar Funções Colaborador Evento'],
            ['slug' => 'configuracoes-funcoes-colaborador-evento-excluir', 'name' => 'Excluir Funções Colaborador Evento'],
        
            // Qualificações
            ['slug' => 'configuracoes-qualificacoes-visualizar', 'name' => 'Visualizar Qualificações'],
            ['slug' => 'configuracoes-qualificacoes-criar', 'name' => 'Criar Qualificações'],
            ['slug' => 'configuracoes-qualificacoes-editar', 'name' => 'Editar Qualificações'],
            ['slug' => 'configuracoes-qualificacoes-excluir', 'name' => 'Excluir Qualificações'],
        
            // Unidades
            ['slug' => 'configuracoes-unidades-visualizar', 'name' => 'Visualizar Unidades'],
            ['slug' => 'configuracoes-unidades-criar', 'name' => 'Criar Unidades'],
            ['slug' => 'configuracoes-unidades-editar', 'name' => 'Editar Unidades'],
            ['slug' => 'configuracoes-unidades-excluir', 'name' => 'Excluir Unidades'],
        
            // Tipos de eventos
            ['slug' => 'configuracoes-tipos-eventos-visualizar', 'name' => 'Visualizar Tipos de Eventos'],
            ['slug' => 'configuracoes-tipos-eventos-criar', 'name' => 'Criar Tipos de Eventos'],
            ['slug' => 'configuracoes-tipos-eventos-editar', 'name' => 'Editar Tipos de Eventos'],
            ['slug' => 'configuracoes-tipos-eventos-excluir', 'name' => 'Excluir Tipos de Eventos'],
        
            // Quebras
            ['slug' => 'configuracoes-quebras-visualizar', 'name' => 'Visualizar Quebras'],
            ['slug' => 'configuracoes-quebras-criar', 'name' => 'Criar Quebras'],
            ['slug' => 'configuracoes-quebras-editar', 'name' => 'Editar Quebras'],
            ['slug' => 'configuracoes-quebras-excluir', 'name' => 'Excluir Quebras'],
        
            // Datas bloqueadas
            ['slug' => 'configuracoes-datas-bloqueadas-visualizar', 'name' => 'Visualizar Datas Bloqueadas'],
            ['slug' => 'configuracoes-datas-bloqueadas-criar', 'name' => 'Criar Datas Bloqueadas'],
            ['slug' => 'configuracoes-datas-bloqueadas-editar', 'name' => 'Editar Datas Bloqueadas'],
            ['slug' => 'configuracoes-datas-bloqueadas-excluir', 'name' => 'Excluir Datas Bloqueadas'],
        
            // Materiais
            ['slug' => 'configuracoes-materiais-visualizar', 'name' => 'Visualizar Materiais'],
            ['slug' => 'configuracoes-materiais-criar', 'name' => 'Criar Materiais'],
            ['slug' => 'configuracoes-materiais-editar', 'name' => 'Editar Materiais'],
            ['slug' => 'configuracoes-materiais-excluir', 'name' => 'Excluir Materiais'],
        
            // Setores
            ['slug' => 'configuracoes-setores-visualizar', 'name' => 'Visualizar Setores'],
            ['slug' => 'configuracoes-setores-criar', 'name' => 'Criar Setores'],
            ['slug' => 'configuracoes-setores-editar', 'name' => 'Editar Setores'],
            ['slug' => 'configuracoes-setores-excluir', 'name' => 'Excluir Setores'],
        
            // Como nos conheceu
            ['slug' => 'configuracoes-como-nos-conheceu-visualizar', 'name' => 'Visualizar Como Nos Conheceu'],
            ['slug' => 'configuracoes-como-nos-conheceu-criar', 'name' => 'Criar Como Nos Conheceu'],
            ['slug' => 'configuracoes-como-nos-conheceu-editar', 'name' => 'Editar Como Nos Conheceu'],
            ['slug' => 'configuracoes-como-nos-conheceu-excluir', 'name' => 'Excluir Como Nos Conheceu'],
        
            // Concorrentes
            ['slug' => 'configuracoes-concorrentes-visualizar', 'name' => 'Visualizar Concorrentes'],
            ['slug' => 'configuracoes-concorrentes-criar', 'name' => 'Criar Concorrentes'],
            ['slug' => 'configuracoes-concorrentes-editar', 'name' => 'Editar Concorrentes'],
            ['slug' => 'configuracoes-concorrentes-excluir', 'name' => 'Excluir Concorrentes'],
        
            // Motivos da perda
            ['slug' => 'configuracoes-motivos-perda-visualizar', 'name' => 'Visualizar Motivos da Perda'],
            ['slug' => 'configuracoes-motivos-perda-criar', 'name' => 'Criar Motivos da Perda'],
            ['slug' => 'configuracoes-motivos-perda-editar', 'name' => 'Editar Motivos da Perda'],
            ['slug' => 'configuracoes-motivos-perda-excluir', 'name' => 'Excluir Motivos da Perda'],
        
            // Métodos de pagamento
            ['slug' => 'configuracoes-metodos-pagamento-visualizar', 'name' => 'Visualizar Métodos de Pagamento'],
            ['slug' => 'configuracoes-metodos-pagamento-criar', 'name' => 'Criar Métodos de Pagamento'],
            ['slug' => 'configuracoes-metodos-pagamento-editar', 'name' => 'Editar Métodos de Pagamento'],
            ['slug' => 'configuracoes-metodos-pagamento-excluir', 'name' => 'Excluir Métodos de Pagamento'],
        
            // Contas Parthenon
            ['slug' => 'configuracoes-contas-parthenon-visualizar', 'name' => 'Visualizar Contas Parthenon'],
            ['slug' => 'configuracoes-contas-parthenon-criar', 'name' => 'Criar Contas Parthenon'],
            ['slug' => 'configuracoes-contas-parthenon-editar', 'name' => 'Editar Contas Parthenon'],
            ['slug' => 'configuracoes-contas-parthenon-excluir', 'name' => 'Excluir Contas Parthenon'],
        
            // Modelo de contrato
            ['slug' => 'configuracoes-modelo-contrato-visualizar', 'name' => 'Visualizar Modelo de Contrato'],
            ['slug' => 'configuracoes-modelo-contrato-editar', 'name' => 'Editar Modelo de Contrato'],
        
            // Histórico
            ['slug' => 'historico-visualizar', 'name' => 'Visualizar Histórico'],
        ];        

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => "{$permission['name']}", 'slug' => "{$permission['slug']}", 'guard_name' => 'api']
            );
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $permissions = Permission::where('guard_name', 'api')->pluck('id');
            $adminRole->permissions()->sync($permissions);
        }

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        // Esta migration não pode ser revertida pois os dados foram perdidos
        // Se necessário, os dados devem ser restaurados de um backup
    }
};
