<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\SectorOffice;
use App\Models\Sector;

return new class extends Migration
{
    public function up(): void
    {
        $board          = Sector::where('name', 'Diretoria')->first();
        $quality        = Sector::where('name', 'Qualidade')->first();
        $financial      = Sector::where('name', 'Financeiro')->first();
        $accounting     = Sector::where('name', 'Contabilidade')->first();
        $commercial     = Sector::where('name', 'Comercial')->first();
        $events         = Sector::where('name', 'Eventos')->first();
        $supplies       = Sector::where('name', 'Suprimentos')->first();
        $production     = Sector::where('name', 'Produção')->first();
        $infrastructure = Sector::where('name', 'Infraestrutura e Logística')->first();
        $administrative = Sector::where('name', 'Administrativo')->first();
        $services       = Sector::where('name', 'Serviços')->first();
        $transport      = Sector::where('name', 'Transporte')->first();

        SectorOffice::create(['sector_id' => $board->id, 'name' => 'Diretor de Infraestrutura e Fomento']);
        SectorOffice::create(['sector_id' => $board->id, 'name' => 'Diretor Financeiro']);
        SectorOffice::create(['sector_id' => $board->id, 'name' => 'Diretor Geral']);
        SectorOffice::create(['sector_id' => $board->id, 'name' => 'Vice-diretora']);
        SectorOffice::create(['sector_id' => $quality->id, 'name' => 'Gerente Comercial']);
        SectorOffice::create(['sector_id' => $quality->id, 'name' => 'Gerente de Qualidade']);
        SectorOffice::create(['sector_id' => $quality->id, 'name' => 'Gerente de Operações']);
        SectorOffice::create(['sector_id' => $financial->id, 'name' => 'Auxiliar Financeiro']);
        SectorOffice::create(['sector_id' => $financial->id, 'name' => ' Gerente Financeiro']);
        SectorOffice::create(['sector_id' => $accounting->id, 'name' => 'Contador']);
        SectorOffice::create(['sector_id' => $commercial->id, 'name' => 'Estoquista']);
        SectorOffice::create(['sector_id' => $commercial->id, 'name' => 'Financeiro']);
        SectorOffice::create(['sector_id' => $commercial->id, 'name' => 'Logistica']);
        SectorOffice::create(['sector_id' => $commercial->id, 'name' => 'Nutricionista']);
        SectorOffice::create(['sector_id' => $events->id, 'name' => 'Assistente de Custos']);
        SectorOffice::create(['sector_id' => $events->id, 'name' => 'Assistente de Eventos']);
        SectorOffice::create(['sector_id' => $events->id, 'name' => 'Assistente de Publicidade e Propaganda']);
        SectorOffice::create(['sector_id' => $events->id, 'name' => 'Consultor de Vendas']);
        SectorOffice::create(['sector_id' => $events->id, 'name' => 'Coordenador de Eventos']);
        SectorOffice::create(['sector_id' => $events->id, 'name' => 'Quantificador de Cardápio']);
        SectorOffice::create(['sector_id' => $supplies->id, 'name' => 'Auxiliar de Expedição e Retorno']);
        SectorOffice::create(['sector_id' => $supplies->id, 'name' => 'Auxiliar de Organização do Estoque']);
        SectorOffice::create(['sector_id' => $supplies->id, 'name' => 'Auxiliar do Estoque de Copos, Taças e Jarras']);
        SectorOffice::create(['sector_id' => $supplies->id, 'name' => 'Auxiliar do Estoque de Utensílios']);
        SectorOffice::create(['sector_id' => $supplies->id, 'name' => 'Coordenador de Suprimentos']);
        SectorOffice::create(['sector_id' => $supplies->id, 'name' => 'Estoquista de Alimentícios']);
        SectorOffice::create(['sector_id' => $supplies->id, 'name' => 'Estoquista de Copos, Taças e Jarras']);
        SectorOffice::create(['sector_id' => $supplies->id, 'name' => 'Estoquista de Utensílios']);
        SectorOffice::create(['sector_id' => $supplies->id, 'name' => 'Supervisor de estoque']);
        SectorOffice::create(['sector_id' => $production->id, 'name' => 'Açougueiro']);
        SectorOffice::create(['sector_id' => $production->id, 'name' => 'Auxiliar de Açougue']);
        SectorOffice::create(['sector_id' => $production->id, 'name' => 'Auxiliar Operacional de Produção de Doces']);
        SectorOffice::create(['sector_id' => $production->id, 'name' => 'Auxiliar Operacional de Produção de Salgados']);
        SectorOffice::create(['sector_id' => $production->id, 'name' => 'Chef de Cozinha Geral']);
        SectorOffice::create(['sector_id' => $production->id, 'name' => 'Chef do Assado']);
        SectorOffice::create(['sector_id' => $production->id, 'name' => 'Chef do Doce']);
        SectorOffice::create(['sector_id' => $production->id, 'name' => 'Chef do Frito']);
        SectorOffice::create(['sector_id' => $production->id, 'name' => 'Coordenadora de Produção']);
        SectorOffice::create(['sector_id' => $infrastructure->id, 'name' => 'Auxiliar de Carga e Descarga']);
        SectorOffice::create(['sector_id' => $infrastructure->id, 'name' => 'Auxiliar de Limpeza e Lavanderia']);
        SectorOffice::create(['sector_id' => $infrastructure->id, 'name' => 'Auxiliar de Manutenção de Patrimônio']);
        SectorOffice::create(['sector_id' => $infrastructure->id, 'name' => 'Coordenador de Infraestrutura e Logística']);
        SectorOffice::create(['sector_id' => $infrastructure->id, 'name' => 'Encarregado de Carga e Descarga']);
        SectorOffice::create(['sector_id' => $infrastructure->id, 'name' => 'Encarregado de Manutenção Estrutural']);
        SectorOffice::create(['sector_id' => $infrastructure->id, 'name' => 'Encarregado de Patrimônio de Evento']);
        SectorOffice::create(['sector_id' => $infrastructure->id, 'name' => 'Motorista']);
        SectorOffice::create(['sector_id' => $infrastructure->id, 'name' => 'Porteiro']);
        SectorOffice::create(['sector_id' => $administrative->id, 'name' => 'Chef de segurança']);
        SectorOffice::create(['sector_id' => $administrative->id, 'name' => 'Segurança']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Ajudante de Churrasqueiro']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Churrasqueiro']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Copa']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Cozinha']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Cumim']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Garçom']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Garçon universitário']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Garçonete']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Maitres']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Mesa']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Portaria']);
        SectorOffice::create(['sector_id' => $services->id, 'name' => 'Programador']);
        SectorOffice::create(['sector_id' => $transport->id, 'name' => 'Carregamento de caminhão']);
        SectorOffice::create(['sector_id' => $transport->id, 'name' => 'Motorista']);
    }

    public function down(): void
    {
        SectorOffice::query()->delete(); 
    }
};
