<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Office;
use App\Models\Sector;

return new class extends Migration
{
    public function up(): void
    {
        $board          = Sector::where('name', 'Diretoria')->first();
        $quality        = Sector::where('name', 'Qualidade')->first();
        $financial        = Sector::where('name', 'Financeiro')->first();
        $accounting     = Sector::where('name', 'Contabilidade')->first();
        $commercial     = Sector::where('name', 'Comercial')->first();
        $events         = Sector::where('name', 'Eventos')->first();
        $supplies       = Sector::where('name', 'Suprimentos')->first();
        $production     = Sector::where('name', 'Produção')->first();
        $infrastructure = Sector::where('name', 'Infraestrutura e Logística')->first();
        $administrative = Sector::where('name', 'Administrativo')->first();
        $services       = Sector::where('name', 'Serviços')->first();
        $transport      = Sector::where('name', 'Transporte')->first();

        Office::create(['sector_id' => $board->id, 'name' => 'Diretor de Infraestrutura e Fomento']);
        Office::create(['sector_id' => $board->id, 'name' => 'Diretor Financeiro']);
        Office::create(['sector_id' => $board->id, 'name' => 'Diretor Geral']);
        Office::create(['sector_id' => $board->id, 'name' => 'Vice-diretora']);
        Office::create(['sector_id' => $quality->id, 'name' => 'Gerente Comercial']);
        Office::create(['sector_id' => $quality->id, 'name' => 'Gerente de Qualidade']);
        Office::create(['sector_id' => $quality->id, 'name' => 'Gerente de Operações']);
        Office::create(['sector_id' => $financial->id, 'name' => 'Auxiliar Financeiro']);
        Office::create(['sector_id' => $financial->id, 'name' => ' Gerente Financeiro']);
        Office::create(['sector_id' => $accounting->id, 'name' => 'Contador']);
        Office::create(['sector_id' => $commercial->id, 'name' => 'Estoquista']);
        Office::create(['sector_id' => $commercial->id, 'name' => 'Financeiro']);
        Office::create(['sector_id' => $commercial->id, 'name' => 'Logistica']);
        Office::create(['sector_id' => $commercial->id, 'name' => 'Nutricionista']);
        Office::create(['sector_id' => $events->id, 'name' => 'Assistente de Custos']);
        Office::create(['sector_id' => $events->id, 'name' => 'Assistente de Eventos']);
        Office::create(['sector_id' => $events->id, 'name' => 'Assistente de Publicidade e Propaganda']);
        Office::create(['sector_id' => $events->id, 'name' => 'Consultor de Vendas']);
        Office::create(['sector_id' => $events->id, 'name' => 'Coordenador de Eventos']);
        Office::create(['sector_id' => $events->id, 'name' => 'Quantificador de Cardápio']);
        Office::create(['sector_id' => $supplies->id, 'name' => 'Auxiliar de Expedição e Retorno']);
        Office::create(['sector_id' => $supplies->id, 'name' => 'Auxiliar de Organização do Estoque']);
        Office::create(['sector_id' => $supplies->id, 'name' => 'Auxiliar do Estoque de Copos, Taças e Jarras']);
        Office::create(['sector_id' => $supplies->id, 'name' => 'Auxiliar do Estoque de Utensílios']);
        Office::create(['sector_id' => $supplies->id, 'name' => 'Coordenador de Suprimentos']);
        Office::create(['sector_id' => $supplies->id, 'name' => 'Estoquista de Alimentícios']);
        Office::create(['sector_id' => $supplies->id, 'name' => 'Estoquista de Copos, Taças e Jarras']);
        Office::create(['sector_id' => $supplies->id, 'name' => 'Estoquista de Utensílios']);
        Office::create(['sector_id' => $supplies->id, 'name' => 'Supervisor de estoque']);
        Office::create(['sector_id' => $production->id, 'name' => 'Açougueiro']);
        Office::create(['sector_id' => $production->id, 'name' => 'Auxiliar de Açougue']);
        Office::create(['sector_id' => $production->id, 'name' => 'Auxiliar Operacional de Produção de Doces']);
        Office::create(['sector_id' => $production->id, 'name' => 'Auxiliar Operacional de Produção de Salgados']);
        Office::create(['sector_id' => $production->id, 'name' => 'Chef de Cozinha Geral']);
        Office::create(['sector_id' => $production->id, 'name' => 'Chef do Assado']);
        Office::create(['sector_id' => $production->id, 'name' => 'Chef do Doce']);
        Office::create(['sector_id' => $production->id, 'name' => 'Chef do Frito']);
        Office::create(['sector_id' => $production->id, 'name' => 'Coordenadora de Produção']);
        Office::create(['sector_id' => $infrastructure->id, 'name' => 'Auxiliar de Carga e Descarga']);
        Office::create(['sector_id' => $infrastructure->id, 'name' => 'Auxiliar de Limpeza e Lavanderia']);
        Office::create(['sector_id' => $infrastructure->id, 'name' => 'Auxiliar de Manutenção de Patrimônio']);
        Office::create(['sector_id' => $infrastructure->id, 'name' => 'Coordenador de Infraestrutura e Logística']);
        Office::create(['sector_id' => $infrastructure->id, 'name' => 'Encarregado de Carga e Descarga']);
        Office::create(['sector_id' => $infrastructure->id, 'name' => 'Encarregado de Manutenção Estrutural']);
        Office::create(['sector_id' => $infrastructure->id, 'name' => 'Encarregado de Patrimônio de Evento']);
        Office::create(['sector_id' => $infrastructure->id, 'name' => 'Motorista']);
        Office::create(['sector_id' => $infrastructure->id, 'name' => 'Porteiro']);
        Office::create(['sector_id' => $administrative->id, 'name' => 'Chef de segurança']);
        Office::create(['sector_id' => $administrative->id, 'name' => 'Segurança']);
        Office::create(['sector_id' => $services->id, 'name' => 'Ajudante de Churrasqueiro']);
        Office::create(['sector_id' => $services->id, 'name' => 'Churrasqueiro']);
        Office::create(['sector_id' => $services->id, 'name' => 'Copa']);
        Office::create(['sector_id' => $services->id, 'name' => 'Cozinha']);
        Office::create(['sector_id' => $services->id, 'name' => 'Cumim']);
        Office::create(['sector_id' => $services->id, 'name' => 'Garçom']);
        Office::create(['sector_id' => $services->id, 'name' => 'Garçon universitário']);
        Office::create(['sector_id' => $services->id, 'name' => 'Garçonete']);
        Office::create(['sector_id' => $services->id, 'name' => 'Maitres']);
        Office::create(['sector_id' => $services->id, 'name' => 'Mesa']);
        Office::create(['sector_id' => $services->id, 'name' => 'Portaria']);
        Office::create(['sector_id' => $services->id, 'name' => 'Programador']);
        Office::create(['sector_id' => $transport->id, 'name' => 'Carregamento de caminhão']);
        Office::create(['sector_id' => $transport->id, 'name' => 'Motorista']);
    }

    public function down(): void
    {
        Office::query()->delete(); 
    }
};
