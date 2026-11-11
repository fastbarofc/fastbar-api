<?php

namespace App\Services;

use App\Models\CommercialRule;
use App\Models\EventType;
use App\Models\Lead;
use App\Models\LeadStageChange;
use App\Models\LeadComment;
use App\Models\LostReason;
use App\Models\MarketRule;
use App\Models\ServedCity;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Funil de vendas > Importar: lê o Excel/CSV exportado do Pipedrive (Negócios > Exportar) e cria ou atualiza os leads.
 * Reconhece as colunas pelo nome (com ou sem o prefixo "Negócio - "), mostra a prévia e só grava depois da confirmação.
 * Não dispara avisos, rodízio, aprovação nem criação de evento no Ganho: negócios antigos entram como histórico.
 */
class PipedriveImportService
{
    /** Campo do sistema => nomes de coluna do Pipedrive (sem acento, minúsculo). */
    public const FIELDS = [
        'pipedrive_id' => ['id', 'id pipedrive', 'id do negocio', 'negocio id', 'deal id'],
        'name' => ['titulo', 'negocio', 'nome do negocio', 'title', 'deal title', 'negocio titulo'],
        'person' => ['pessoa de contato', 'pessoa', 'pessoa nome', 'contato', 'nome da pessoa', 'cliente', 'person'],
        'person_id' => ['id da pessoa de contato', 'pessoa id', 'id da pessoa', 'person id'],
        'organization' => ['organizacao', 'organizacao nome', 'empresa', 'organization'],
        'phone' => ['telefone', 'pessoa telefone', 'telefone da pessoa', 'celular', 'whatsapp', 'phone'],
        'email' => ['e mail', 'email', 'pessoa e mail', 'pessoa email', 'e mail da pessoa'],
        'stage' => ['etapa', 'fase', 'stage', 'etapa do funil'],
        'status' => ['status', 'situacao'],
        'value' => ['valor', 'valor r', 'valor do negocio', 'value', 'valor rs'],
        'owner' => ['proprietario', 'proprietario id', 'proprietario nome', 'dono', 'responsavel', 'consultor', 'owner', 'vendedor'],
        'lost_reason' => ['motivo da perda', 'motivo de perda', 'motivo perda', 'lost reason', 'motivo'],
        'created_at' => ['criado em', 'data de criacao', 'negocio criado em', 'data de adicao', 'add time', 'created'],
        'won_at' => ['data de ganho', 'hora de ganho', 'ganho em', 'won time', 'data ganho'],
        'lost_at' => ['data de perda', 'hora de perda', 'perdido em', 'lost time', 'data perda'],
        'closed_at' => ['negocio fechado em', 'fechado em', 'data de fechamento', 'close time'],
        'stage_changed_at' => ['ultima alteracao de etapa', 'ultima mudanca de etapa', 'stage change time'],
        'city' => ['cidade', 'cidade padronizada', 'cidade do evento', 'municipio', 'city'],
        'event_date' => ['data do evento', 'data evento', 'data da festa', 'data'],
        'event_date_alt' => ['data de execucao do evento', 'data de execucao'],
        'guests' => ['convidados', 'n de convidados', 'numero de convidados', 'quantidade de pessoas', 'pessoas', 'qtd convidados', 'n convidados'],
        'event_type' => ['tipo de evento', 'tipo do evento', 'tipo', 'perfil do evento'],
        'segment' => ['segmento'],
        'source' => ['origem do lead', 'canal de origem', 'fonte', 'canal', 'origem', 'source', 'como conheceu'],
        'partner' => ['indicacao de qual parceiro', 'parceiro', 'cerimonial', 'indicacao'],
        'expected_viability' => ['viabilidade prevista', 'viabilidade prevista %', 'viabilidade'],
        'label' => ['etiqueta', 'etiquetas', 'rotulo', 'label'],
        'notes' => ['observacoes', 'observacao', 'notas', 'descricao', 'nota'],
    ];

    public const FIELD_LABELS = [
        'pipedrive_id' => 'ID do negócio', 'name' => 'Título do negócio', 'person' => 'Pessoa de contato', 'organization' => 'Organização',
        'phone' => 'Telefone', 'email' => 'E-mail', 'stage' => 'Etapa', 'status' => 'Status (aberto/ganho/perdido)', 'value' => 'Valor',
        'owner' => 'Proprietário (consultor)', 'lost_reason' => 'Motivo da perda', 'created_at' => 'Criado em', 'won_at' => 'Data de ganho',
        'lost_at' => 'Data de perda', 'closed_at' => 'Data de fechamento', 'city' => 'Cidade', 'event_date' => 'Data do evento',
        'guests' => 'Convidados', 'event_type' => 'Tipo de evento', 'segment' => 'Segmento', 'source' => 'Origem', 'partner' => 'Parceiro',
        'label' => 'Etiqueta', 'notes' => 'Observações', 'person_id' => 'ID da pessoa de contato', 'stage_changed_at' => 'Última alteração de etapa',
        'event_date_alt' => 'Data de execução do evento', 'expected_viability' => 'Viabilidade prevista %',
    ];

    /** Nomes de etapa do Pipedrive => etapa do funil. */
    private const STAGE_SYNONYMS = [
        'novo' => ['recebimento de leads', 'recebimento', 'lead recebido', 'novo', 'novos leads', 'leads', 'entrada', 'lead in', 'qualificacao inicial'],
        'em_contato' => ['em contato qualificacao', 'em contato', 'qualificacao', 'contato', 'contato feito', 'em atendimento'],
        'reuniao' => ['reuniao agendada', 'reuniao', 'visita', 'visita agendada', 'reuniao visita'],
        'proposta_enviada' => ['proposta enviada', 'proposta', 'orcamento enviado', 'orcamento'],
        'follow_up' => ['follow up', 'followup', 'acompanhamento'],
        'negociacao' => ['negociacao fechamento', 'negociacao', 'fechamento', 'negociacao e fechamento'],
        'aguardando_assinatura' => ['aguardando assinatura', 'aguardando assinatura contratual', 'assinatura', 'contrato', 'aguardando contrato'],
        'nutricao' => ['nutricao', 'nutricao sem data', 'sem data', 'futuro'],
        'licitacao' => ['licitacoes', 'licitacao'],
        'ganho' => ['ganho', 'won', 'fechado ganho'],
        'perdido' => ['perdido', 'lost', 'fechado perdido'],
    ];

    private const SOURCE_SYNONYMS = [
        'whatsapp' => ['whatsapp', 'whatsap', 'whasapp', 'whastapp', 'whatsaap', 'wpp', 'zap'], 'instagram' => ['instagram', 'insta', 'direct'], 'trafego_pago' => ['trafego pago', 'trafego', 'anuncio', 'ads', 'facebook ads', 'google ads', 'meta ads', 'marketing'],
        'site' => ['site', 'website', 'formulario', 'google'], 'cerimonial' => ['cerimonial', 'cerimonialista', 'produtora', 'assessoria', 'eventos', 'viva', 'vision', 'celebra', 'hub'],
        'indicacao' => ['indicacao', 'indicado', 'referencia', 'boca a boca', 'parceiro', 'cliente'], 'direto' => ['direto', 'consultor', 'carteira', 'comercial', 'passiva', 'andre'],
        'prospeccao' => ['prospeccao', 'ativo', 'outbound'], 'sdr' => ['sdr'], 'licitacao' => ['licitacao', 'edital'], 'outro' => ['outro', 'outros'],
    ];

    public const LOST_GROUP = 'Histórico do Pipedrive';

    // ------------------------------------------------------------------ leitura do arquivo

    /** Lê CSV ou XLSX. Devolve [sheets => [nomes], sheet => nome usada, headers => [], rows => [[col => valor]]]. */
    public function read(string $path, string $originalName, ?string $sheet = null): array
    {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($ext === 'csv' || $ext === 'txt') {
            $table = $this->readCsv($path);
            $sheets = ['CSV'];
            $used = 'CSV';
        } else {
            [$sheets, $used, $table] = $this->readXlsx($path, $sheet);
        }
        $headers = array_map(fn ($h) => trim((string) $h), array_shift($table) ?? []);
        $rows = [];
        foreach ($table as $r) {
            if (!array_filter($r, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }
            $row = [];
            foreach ($headers as $i => $h) {
                if ($h !== '') {
                    $row[$h] = isset($r[$i]) ? (is_string($r[$i]) ? trim($r[$i]) : $r[$i]) : null;
                }
            }
            $rows[] = $row;
        }

        return ['sheets' => $sheets, 'sheet' => $used, 'headers' => array_values(array_filter($headers, fn ($h) => $h !== '')), 'rows' => $rows];
    }

    /** Arquivo "Pessoas" do Pipedrive: ID da pessoa => telefone e e-mail (para completar os negócios). */
    public function readContacts(string $path, string $originalName): array
    {
        $data = $this->read($path, $originalName);
        $headers = $data['headers'];
        $find = function (array $names) use ($headers) {
            foreach ($names as $n) {
                foreach ($headers as $h) {
                    if (self::fold($h) === $n) {
                        return $h;
                    }
                }
            }

            return null;
        };
        $idCol = $find(['id', 'id da pessoa', 'pessoa id']);
        if (!$idCol) {
            throw new \RuntimeException('O arquivo de pessoas não tem a coluna "ID".');
        }
        $phoneCols = array_values(array_filter([$find(['telefone celular', 'celular']), $find(['telefone trabalho']), $find(['telefone residencial']), $find(['telefone outros']), $find(['telefone', 'phone'])]));
        $emailCols = array_values(array_filter([$find(['e mail trabalho']), $find(['e mail residencial']), $find(['e mail outros']), $find(['e mail', 'email'])]));
        $out = [];
        foreach ($data['rows'] as $row) {
            $id = $row[$idCol] ?? null;
            if ($id === null || $id === '') {
                continue;
            }
            $phone = null;
            foreach ($phoneCols as $c) {
                if ($phone = $this->phone($row[$c] ?? null)) {
                    break;
                }
            }
            $email = null;
            foreach ($emailCols as $c) {
                $v = trim((string) ($row[$c] ?? ''));
                if ($v !== '' && filter_var(explode(',', $v)[0], FILTER_VALIDATE_EMAIL)) {
                    $email = trim(explode(',', $v)[0]);
                    break;
                }
            }
            $out[(string) (int) $id] = ['phone' => $phone, 'email' => $email];
        }

        return $out;
    }

    /** Arquivo "Anotações" do Pipedrive: ID do negócio => lista de anotações (texto, data, usuário). */
    public function readNotes(string $path, string $originalName): array
    {
        $data = $this->read($path, $originalName);
        $headers = $data['headers'];
        $find = function (array $names) use ($headers) {
            foreach ($names as $n) {
                foreach ($headers as $h) {
                    if (self::fold($h) === $n) {
                        return $h;
                    }
                }
            }

            return null;
        };
        $dealCol = $find(['id do negocio', 'negocio id', 'deal id']);
        $textCol = $find(['conteudo', 'content', 'anotacao', 'texto']);
        if (!$dealCol || !$textCol) {
            throw new \RuntimeException('O arquivo de anotações precisa das colunas "ID do negócio" e "Conteúdo".');
        }
        $dateCol = $find(['data adicionada', 'adicionado em', 'add time', 'criado em']);
        $userCol = $find(['usuario', 'user', 'criado por']);
        $users = [];
        $out = [];
        foreach ($data['rows'] as $row) {
            $deal = $row[$dealCol] ?? null;
            $text = trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>|<\/p>/i', "\n", (string) ($row[$textCol] ?? '')))));
            if ($deal === null || $deal === '' || $text === '') {
                continue;
            }
            $userName = $userCol ? trim((string) ($row[$userCol] ?? '')) : '';
            if ($userName !== '' && !array_key_exists($userName, $users)) {
                $users[$userName] = MarketRule::findUserByName($userName)?->id;
            }
            $out[(string) (int) $deal][] = [
                'text' => mb_substr($text, 0, 5000),
                'at' => $dateCol ? $this->date($row[$dateCol] ?? null) : null,
                'user_id' => $userName !== '' ? $users[$userName] : null,
            ];
        }
        foreach ($out as &$list) {
            usort($list, fn ($a, $b) => ($a['at']?->timestamp ?? 0) <=> ($b['at']?->timestamp ?? 0));
        }

        return $out;
    }

    private function readCsv(string $path): array
    {
        $raw = file_get_contents($path);
        if (!mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'Windows-1252');
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $first = strtok($raw, "\n") ?: '';
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : (substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',');
        $h = fopen('php://memory', 'r+');
        fwrite($h, $raw);
        rewind($h);
        $table = [];
        while (($r = fgetcsv($h, 0, $delimiter)) !== false) {
            $table[] = $r;
        }
        fclose($h);

        return $table;
    }

    /** XLSX sem biblioteca: ZipArchive + XML. Escolhe a aba pedida ou a primeira com colunas conhecidas. */
    private function readXlsx(string $path, ?string $wanted): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            throw new \RuntimeException('Não consegui abrir o arquivo. Envie o Excel (.xlsx) ou CSV exportado do Pipedrive.');
        }
        $wb = simplexml_load_string($zip->getFromName('xl/workbook.xml'));
        $rels = simplexml_load_string($zip->getFromName('xl/_rels/workbook.xml.rels'));
        $targets = [];
        foreach ($rels->Relationship as $rel) {
            $targets[(string) $rel['Id']] = ltrim(str_replace('/xl/', '', (string) $rel['Target']), '/');
        }
        $sheets = [];
        foreach ($wb->sheets->sheet as $s) {
            $rid = (string) $s->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
            $sheets[(string) $s['name']] = 'xl/' . ($targets[$rid] ?? 'worksheets/sheet1.xml');
        }
        $shared = [];
        if ($xml = $zip->getFromName('xl/sharedStrings.xml')) {
            $ss = simplexml_load_string($xml);
            foreach ($ss->si as $si) {
                $shared[] = isset($si->t) ? (string) $si->t : implode('', array_map(fn ($r) => (string) $r->t, iterator_to_array($si->r, false)));
            }
        }
        $dateStyles = $this->dateStyles($zip);
        $parse = function (string $file) use ($zip, $shared, $dateStyles): array {
            $xml = $zip->getFromName($file);
            if (!$xml) {
                return [];
            }
            $sx = simplexml_load_string($xml);
            $table = [];
            foreach ($sx->sheetData->row as $row) {
                $cells = [];
                foreach ($row->c as $c) {
                    $ref = (string) $c['r'];
                    $col = $this->colIndex(preg_replace('/\d+/', '', $ref));
                    $t = (string) $c['t'];
                    $v = isset($c->v) ? (string) $c->v : (isset($c->is->t) ? (string) $c->is->t : null);
                    if ($t === 's') {
                        $v = $shared[(int) $v] ?? '';
                    } elseif ($t === 'b') {
                        $v = $v === '1' ? 'Sim' : 'Não';
                    } elseif ($v !== null && $v !== '' && is_numeric($v) && in_array((int) $c['s'], $dateStyles, true)) {
                        $v = Carbon::create(1899, 12, 30)->addDays((int) floor((float) $v))->toDateString();
                    }
                    $cells[$col] = $v;
                }
                if ($cells) {
                    $max = max(array_keys($cells));
                    $table[] = array_map(fn ($i) => $cells[$i] ?? null, range(0, $max));
                }
            }

            return $table;
        };
        $names = array_keys($sheets);
        $used = $wanted && isset($sheets[$wanted]) ? $wanted : null;
        if (!$used) {
            foreach ($names as $n) {
                $t = $parse($sheets[$n]);
                $mapping = $this->detect(array_map(fn ($h) => (string) $h, $t[0] ?? []));
                if (isset($mapping['pipedrive_id']) || isset($mapping['stage']) || isset($mapping['status'])) {
                    $used = $n;
                    break;
                }
            }
            $used ??= $names[0];
        }
        $table = $parse($sheets[$used]);
        $zip->close();

        return [$names, $used, $table];
    }

    private function dateStyles(\ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/styles.xml');
        if (!$xml) {
            return [];
        }
        $sx = simplexml_load_string($xml);
        $custom = [];
        foreach ($sx->numFmts->numFmt ?? [] as $f) {
            $code = strtolower((string) $f['formatCode']);
            if (preg_match('/[dmy]/', preg_replace('/\[[^\]]*\]|"[^"]*"/', '', $code))) {
                $custom[] = (int) $f['numFmtId'];
            }
        }
        $builtin = [14, 15, 16, 17, 18, 19, 20, 21, 22, 45, 46, 47];
        $out = [];
        foreach ($sx->cellXfs->xf ?? [] as $i => $xf) {
            $id = (int) $xf['numFmtId'];
            if (in_array($id, $builtin, true) || in_array($id, $custom, true)) {
                $out[] = $i;
            }
        }

        return $out;
    }

    private function colIndex(string $letters): int
    {
        $n = 0;
        foreach (str_split(strtoupper($letters)) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }

        return max(0, $n - 1);
    }

    // ------------------------------------------------------------------ reconhecimento

    public static function fold(?string $v): string
    {
        $v = mb_strtolower(trim((string) $v));
        $a = preg_replace('/[^a-z0-9]+/', ' ', ServedCity::unaccent($v)) ?? '';
        // Exportação do Pipedrive prefixa as colunas: "Negócio - Título", "Pessoa - Telefone", "Observação - Conteúdo".
        $a = preg_replace('/^(negocio|deal|pessoa|person|observacao|note|lead|organizacao) /', '', trim($a)) ?? $a;

        return trim($a);
    }

    /** Campo do sistema => nome da coluna do arquivo. */
    public function detect(array $headers): array
    {
        $map = [];
        $taken = [];
        foreach (self::FIELDS as $field => $names) {
            foreach ($names as $n) {
                foreach ($headers as $h) {
                    if (!isset($taken[$h]) && self::fold($h) === $n) {
                        $map[$field] = $h;
                        $taken[$h] = true;
                        continue 3;
                    }
                }
            }
        }
        // "Pessoa - Nome" / "Pessoa - Telefone" quando o fold tirou o prefixo.
        foreach ($headers as $h) {
            $f = self::fold($h);
            if (!isset($taken[$h]) && !isset($map['person']) && in_array($f, ['nome', 'name'], true) && preg_match('/pessoa|person/i', $h)) {
                $map['person'] = $h;
                $taken[$h] = true;
            }
        }

        return $map;
    }

    public function stageFor(?string $raw, array $stageMap = []): ?string
    {
        $f = self::fold($raw);
        if ($f === '') {
            return null;
        }
        if (isset($stageMap[$raw]) && $stageMap[$raw] !== '') {
            return $stageMap[$raw];
        }
        foreach (self::STAGE_SYNONYMS as $stage => $names) {
            if (in_array($f, $names, true)) {
                return $stage;
            }
        }
        foreach (self::STAGE_SYNONYMS as $stage => $names) {
            foreach ($names as $n) {
                if (str_contains($f, $n) || str_contains($n, $f)) {
                    return $stage;
                }
            }
        }

        return null;
    }

    private function statusFor(?string $raw): ?string
    {
        $f = self::fold($raw);

        return match (true) {
            in_array($f, ['ganho', 'won', 'ganhos', 'fechado ganho'], true) => 'ganho',
            in_array($f, ['perdido', 'lost', 'perdidos', 'fechado perdido'], true) => 'perdido',
            in_array($f, ['aberto', 'open', 'em aberto', 'abertos'], true) => 'aberto',
            default => null,
        };
    }

    private function sourceFor(?string $raw): ?string
    {
        $f = self::fold($raw);
        if ($f === '') {
            return null;
        }
        foreach (self::SOURCE_SYNONYMS as $source => $names) {
            foreach ($names as $n) {
                if ($f === $n || str_contains($f, $n)) {
                    return $source;
                }
            }
        }

        return 'outro';
    }

    private function date($raw): ?Carbon
    {
        if ($raw === null || $raw === '' || $raw === '0000-00-00') {
            return null;
        }
        if (is_numeric($raw) && (float) $raw > 20000 && (float) $raw < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) floor((float) $raw));
        }
        $s = trim((string) $raw);
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d', 'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y', 'd/m/y', 'd-m-Y', 'm/d/Y', 'Y-m-d\TH:i:s'] as $fmt) {
            try {
                $d = Carbon::createFromFormat($fmt, $s);
                if ($d && $d->year > 1990) {
                    return $d;
                }
            } catch (\Throwable) {
            }
        }
        try {
            return Carbon::parse($s);
        } catch (\Throwable) {
            return null;
        }
    }

    private function money($raw): ?float
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            return (float) $raw;
        }
        $s = preg_replace('/[^\d,.\-]/', '', (string) $raw);
        if (str_contains($s, ',') && (!str_contains($s, '.') || strrpos($s, ',') > strrpos($s, '.'))) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '', $s);
        }

        return is_numeric($s) ? (float) $s : null;
    }

    // ------------------------------------------------------------------ prévia

    public function preview(array $rows, array $mapping, array $stageMap = [], array $ownerMap = [], array $contacts = [], array $notes = []): array
    {
        $withContact = 0;
        $withNotes = 0;
        $notesTotal = 0;
        $get = fn (array $row, string $field) => isset($mapping[$field]) ? ($row[$mapping[$field]] ?? null) : null;
        $stages = [];
        $owners = [];
        $reasons = [];
        $ids = [];
        $withoutId = 0;
        $unknownStages = [];
        foreach ($rows as $row) {
            $id = $get($row, 'pipedrive_id');
            $id ? $ids[] = (int) $id : $withoutId++;
            $pid = $get($row, 'person_id');
            if ($pid !== null && $pid !== '' && !empty($contacts[(string) (int) $pid]['phone'])) {
                $withContact++;
            }
            if ($id && !empty($notes[(string) (int) $id])) {
                $withNotes++;
                $notesTotal += count($notes[(string) (int) $id]);
            }
            [$stage, $rawStage] = $this->resolveStage($row, $get, $stageMap);
            $key = $stage ?? '?';
            $stages[$key] ??= ['stage' => $stage, 'label' => $stage ? (LeadDashboardService::STAGE_LABELS[$stage] ?? $stage) : 'Sem etapa reconhecida', 'count' => 0, 'value' => 0.0, 'raw' => []];
            $stages[$key]['count']++;
            $stages[$key]['value'] += $this->money($get($row, 'value')) ?? 0;
            if ($rawStage !== null && $rawStage !== '') {
                $stages[$key]['raw'][$rawStage] = ($stages[$key]['raw'][$rawStage] ?? 0) + 1;
                if (!$stage) {
                    $unknownStages[$rawStage] = true;
                }
            }
            $owner = trim((string) $get($row, 'owner'));
            if ($owner !== '') {
                $owners[$owner] ??= ['name' => $owner, 'count' => 0, 'user_id' => $this->ownerUser($owner, $ownerMap)?->id, 'user_name' => $this->ownerUser($owner, $ownerMap)?->name];
                $owners[$owner]['count']++;
            }
            $reason = trim((string) $get($row, 'lost_reason'));
            if ($reason !== '' && $stage === 'perdido') {
                $existing = $this->findReason($reason);
                $reasons[$reason] ??= ['name' => $reason, 'count' => 0, 'lost_reason_id' => $existing?->id, 'lost_reason_name' => $existing?->name];
                $reasons[$reason]['count']++;
            }
        }
        $existing = $ids ? Lead::withTrashed()->whereIn('pipedrive_id', $ids)->count() : 0;
        $order = array_flip(array_keys(LeadDashboardService::STAGE_LABELS));
        uasort($stages, fn ($a, $b) => ($order[$a['stage']] ?? 99) <=> ($order[$b['stage']] ?? 99));
        $stageOptions = array_map(fn ($k, $v) => ['value' => $k, 'label' => $v], array_keys(LeadDashboardService::STAGE_LABELS), LeadDashboardService::STAGE_LABELS);

        return [
            'total' => count($rows),
            'without_id' => $withoutId,
            'with_contact' => $withContact,
            'with_notes' => $withNotes,
            'notes_total' => $notesTotal,
            'has_phone' => isset($mapping['phone']),
            'existing' => $existing,
            'stages' => array_values(array_map(function ($s) {
                $s['value'] = round($s['value'], 2);
                $s['raw'] = array_map(fn ($name, $count) => ['name' => $name, 'count' => $count], array_keys($s['raw']), $s['raw']);

                return $s;
            }, $stages)),
            'unknown_stages' => array_keys($unknownStages),
            'owners' => array_values($owners),
            'lost_reasons' => array_values($reasons),
            'stage_options' => $stageOptions,
            'consultants' => User::where('is_employee', true)->orderBy('name')->get(['id', 'name']),
            'fields' => self::FIELD_LABELS,
        ];
    }

    /** @return array{0: ?string, 1: ?string} etapa do funil e o texto da etapa no arquivo */
    private function resolveStage(array $row, callable $get, array $stageMap): array
    {
        $status = $this->statusFor($get($row, 'status'));
        $rawStage = $get($row, 'stage');
        $rawStage = $rawStage === null ? null : (string) $rawStage;
        if ($status === 'ganho' || $status === 'perdido') {
            return [$status, $rawStage];
        }
        $stage = $this->stageFor($rawStage, $stageMap);
        if (!$stage && $rawStage === null) {
            // Sem coluna de etapa (ex.: aba resumida): status aberto cai em Recebimento de leads.
            $stage = $status === 'aberto' ? 'novo' : null;
        }

        return [$stage, $rawStage];
    }

    private function ownerUser(string $owner, array $ownerMap): ?User
    {
        static $cache = [];
        if (array_key_exists($owner, $ownerMap)) {
            return $ownerMap[$owner] ? User::find((int) $ownerMap[$owner]) : null;
        }
        if (!array_key_exists($owner, $cache)) {
            $cache[$owner] = ctype_digit($owner) ? null : MarketRule::findUserByName($owner);
        }

        return $cache[$owner];
    }

    private ?\Illuminate\Support\Collection $reasonCache = null;

    /** Acha o motivo de perda pelo nome do Pipedrive; se não existir, cria um no grupo "Histórico do Pipedrive" (uma vez só). */
    private function reasonId(string $name): int
    {
        $found = $this->findReason($name);
        if (!$found) {
            $found = LostReason::create(['name' => $name . ' (histórico do Pipedrive)', 'group' => self::LOST_GROUP]);
            $this->reasonCache?->push($found);
        }

        return $found->id;
    }

    private function findReason(string $name): ?LostReason
    {
        $all = $this->reasonCache ??= LostReason::get();
        $f = self::fold($name);
        $aliases = [
            'preco' => 'acima do orcamento do cliente', 'valor' => 'acima do orcamento do cliente', 'orcamento' => 'acima do orcamento do cliente',
            'data' => 'data indisponivel', 'concorrente' => 'fechou com concorrente', 'concorrencia' => 'fechou com concorrente',
            'contato invalido sem contato' => 'contato invalido', 'contato invalido' => 'contato invalido', 'sem contato' => 'sem resposta',
            'cancelado' => 'evento cancelado ou adiado sem data', 'adiado' => 'evento cancelado ou adiado sem data',
            'duplicado' => 'lead duplicado', 'cidade' => 'cidade nao atendida', 'licitacao' => 'licitacao nao vencida',
        ];
        $target = $aliases[$f] ?? $f;

        return $all->first(fn (LostReason $r) => self::fold($r->name) === $target)
            ?? $all->first(fn (LostReason $r) => self::fold($r->name) === $f . ' historico do pipedrive');
    }

    // ------------------------------------------------------------------ gravação

    public function import(array $rows, array $mapping, array $stageMap, array $ownerMap, ?int $byUserId, array $contacts = [], array $notes = []): array
    {
        $get = fn (array $row, string $field) => isset($mapping[$field]) ? ($row[$mapping[$field]] ?? null) : null;
        $rules = CommercialRule::current();
        $types = EventType::all();
        $created = 0;
        $updated = 0;
        $errors = [];
        Lead::disableAuditing();
        DB::transaction(function () use ($rows, $get, $rules, $types, $stageMap, $ownerMap, $byUserId, $contacts, $notes, &$created, &$updated, &$errors) {
            $position = 0;
            foreach ($rows as $i => $row) {
                $id = $get($row, 'pipedrive_id');
                if (!$id || !is_numeric($id)) {
                    $errors[] = 'Linha ' . ($i + 2) . ': sem ID do negócio.';
                    continue;
                }
                try {
                    $this->importRow($row, $i, $id, $get, $rules, $types, $stageMap, $ownerMap, $byUserId, $contacts, $notes, $position, $created, $updated, $errors);
                } catch (\Throwable $e) {
                    // Mensagem curta, sem o SQL (que traria dados do cliente para a tela).
                    $errors[] = 'Linha ' . ($i + 2) . ' (negócio ' . $id . '): não foi possível gravar. ' . $this->shortError($e);
                }
            }
        });
        Lead::enableAuditing();

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    private function shortError(\Throwable $e): string
    {
        $msg = $e->getMessage();
        if ($e instanceof \Illuminate\Database\QueryException) {
            $msg = $e->getPrevious()?->getMessage() ?? $msg;
            if (preg_match("/for column '([^']+)'/", $msg, $m)) {
                return 'Valor grande demais para o campo "' . $m[1] . '".';
            }

            return 'Erro no banco de dados (' . ($e->getCode() ?: '?') . ').';
        }

        return mb_substr(preg_replace('/\s+/', ' ', $msg) ?? '', 0, 160);
    }

    private function importRow(array $row, int $i, $id, callable $get, CommercialRule $rules, $types, array $stageMap, array $ownerMap, ?int $byUserId, array $contacts, array $notes, int &$position, int &$created, int &$updated, array &$errors): void
    {
        {
            {
                [$stage, $rawStage] = $this->resolveStage($row, $get, $stageMap);
                if (!$stage) {
                    $errors[] = 'Linha ' . ($i + 2) . ' (negócio ' . $id . '): etapa "' . $rawStage . '" não reconhecida.';

                    return;
                }
                $title = trim((string) $get($row, 'name'));
                $person = trim((string) $get($row, 'person'));
                $org = trim((string) $get($row, 'organization'));
                $name = $title ?: ($person ?: ($org ?: 'Negócio Pipedrive ' . $id));
                $createdAt = $this->date($get($row, 'created_at')) ?? now();
                $wonAt = $this->date($get($row, 'won_at'));
                $lostAt = $this->date($get($row, 'lost_at'));
                $closedAt = $stage === 'ganho' ? ($wonAt ?? $this->date($get($row, 'closed_at')) ?? $createdAt) : ($stage === 'perdido' ? ($lostAt ?? $this->date($get($row, 'closed_at')) ?? $createdAt) : null);
                $value = $this->money($get($row, 'value'));
                $owner = trim((string) $get($row, 'owner'));
                $user = $owner !== '' ? $this->ownerUser($owner, $ownerMap) : null;
                $typeId = $this->eventTypeId($get($row, 'event_type'), $get($row, 'segment'), $rules, $types);
                $reasonId = null;
                if ($stage === 'perdido') {
                    $reasonName = trim((string) $get($row, 'lost_reason')) ?: 'Sem motivo';
                    $reasonId = $this->reasonId($reasonName);
                }
                $guests = $get($row, 'guests');
                $label = trim((string) $get($row, 'label'));
                $labelFold = self::fold($label);
                $temperature = str_contains($labelFold, 'quente') ? 'quente' : (str_contains($labelFold, 'morno') ? 'morno' : (str_contains($labelFold, 'frio') ? 'frio' : null));
                $pid = $get($row, 'person_id');
                $contact = $pid !== null && $pid !== '' ? ($contacts[(string) (int) $pid] ?? null) : null;
                $viability = $get($row, 'expected_viability');
                $viability = is_numeric($viability) ? round((float) $viability, 2) : null;
                $stageAt = $this->date($get($row, 'stage_changed_at'));
                $observation = trim(implode("\n", array_filter([
                    mb_strlen($name) > 191 ? 'Título completo: ' . $name : null,
                    (string) $get($row, 'notes'),
                    $org && $org !== $name ? 'Organização: ' . $org : null,
                    $person && $person !== $name ? 'Contato: ' . $person : null,
                    $get($row, 'label') ? 'Etiqueta Pipedrive: ' . $get($row, 'label') : null,
                    $get($row, 'partner') ? 'Parceiro: ' . $get($row, 'partner') : null,
                    $rawStage && !in_array($stage, ['ganho', 'perdido'], true) ? null : ($rawStage ? 'Etapa no Pipedrive: ' . $rawStage : null),
                ])));

                $attrs = [
                    'pipedrive_id' => (int) $id,
                    'name' => mb_substr($name, 0, 191),
                    'phone' => $this->phone($get($row, 'phone')) ?? ($contact['phone'] ?? null),
                    'email' => mb_substr((string) ((trim((string) $get($row, 'email')) ?: null) ?? ($contact['email'] ?? '')), 0, 191) ?: null,
                    'city' => $this->city($get($row, 'city')),
                    'event_type_id' => $typeId,
                    'event_date' => ($this->date($get($row, 'event_date')) ?? $this->date($get($row, 'event_date_alt')))?->toDateString(),
                    'temperature' => $temperature,
                    'expected_viability' => $viability,
                    'guests' => is_numeric($guests) ? (int) $guests : null,
                    'estimated_value' => $value,
                    'final_value' => $stage === 'ganho' ? $value : null,
                    'source' => $this->sourceFor($get($row, 'source')),
                    'stage' => $stage,
                    'user_id' => $user?->id,
                    'lost_reason_id' => $reasonId,
                    'observation' => $observation ?: null,
                    'imported_at' => now(),
                    'imported_owner' => $owner !== '' && !ctype_digit($owner) ? mb_substr($owner, 0, 120) : null,
                ];

                $lead = Lead::withTrashed()->where('pipedrive_id', (int) $id)->first();
                if ($lead) {
                    if ($lead->trashed()) {
                        $lead->restore();
                    }
                    $fromStage = $lead->stage;
                    // Reimportar não desfaz o trabalho feito aqui: mantém o consultor escolhido na tela quando o arquivo
                    // não diz quem é, e mantém a etapa atual quando o arquivo só diz "aberto" (sem coluna de etapa).
                    if (!$user && $lead->user_id) {
                        unset($attrs['user_id']);
                    }
                    if ($stage === 'novo' && $rawStage === null && !in_array($fromStage, ['ganho', 'perdido'], true)) {
                        $stage = $fromStage;
                        $attrs['stage'] = $fromStage;
                    }
                    Lead::withoutEvents(fn () => $lead->forceFill($attrs + ['stage_changed_at' => $closedAt ?? $stageAt ?? $lead->stage_changed_at ?? $createdAt])->save());
                    if ($fromStage !== $stage) {
                        LeadStageChange::create(['lead_id' => $lead->id, 'from_stage' => $fromStage, 'to_stage' => $stage, 'user_id' => $byUserId, 'changed_at' => $closedAt ?? now()]);
                    }
                    $updated++;
                } else {
                    $lead = new Lead();
                    $lead->forceFill($attrs + ['position' => $position++, 'stage_changed_at' => $closedAt ?? $stageAt ?? $createdAt, 'created_at' => $createdAt, 'updated_at' => $closedAt ?? $stageAt ?? $createdAt]);
                    Lead::withoutEvents(fn () => $lead->save());
                    LeadStageChange::create(['lead_id' => $lead->id, 'from_stage' => null, 'to_stage' => 'novo', 'user_id' => $byUserId, 'changed_at' => $createdAt]);
                    if ($stage !== 'novo') {
                        LeadStageChange::create(['lead_id' => $lead->id, 'from_stage' => 'novo', 'to_stage' => $stage, 'user_id' => $byUserId, 'changed_at' => $closedAt ?? $stageAt ?? $createdAt]);
                    }
                    $created++;
                }
                // Anotações do Pipedrive viram registros de contato (uma vez só: compara pelo texto já gravado).
                if (!empty($notes[(string) (int) $id])) {
                    $have = $lead->comments()->pluck('comment')->map(fn ($c) => mb_substr($c, 0, 120))->flip();
                    foreach ($notes[(string) (int) $id] as $note) {
                        if (isset($have[mb_substr($note['text'], 0, 120)])) {
                            continue;
                        }
                        $c = new LeadComment(['lead_id' => $lead->id, 'user_id' => $note['user_id'] ?? $byUserId, 'comment' => $note['text']]);
                        $c->created_at = $note['at'] ?? $createdAt;
                        $c->updated_at = $note['at'] ?? $createdAt;
                        $c->save();
                    }
                }
            }
        }
    }

    /** Cidade como o Pipedrive manda ("Viçosa/MG", "viçosa", "belo_horizonte_(bh)") => "Viçosa", "Belo Horizonte". */
    private function city($raw): ?string
    {
        $s = $this->text($raw);
        if ($s === null) {
            return null;
        }
        $s = str_replace('_', ' ', $s);
        $s = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $s) ?? $s;
        $s = preg_replace('/\s*[\/\-,]\s*[A-Za-z]{2}\.?\s*$/u', '', $s) ?? $s;
        $s = preg_replace('/\s+[A-Z]{2}$/u', '', $s) ?? $s;
        $s = trim(preg_replace('/\s+/u', ' ', $s) ?? $s);
        if ($s === '') {
            return null;
        }
        $s = mb_convert_case(mb_strtolower($s), MB_CASE_TITLE, 'UTF-8');

        return preg_replace_callback('/\b(Da|De|Do|Das|Dos|E)\b/u', fn ($m) => mb_strtolower($m[0]), $s) ?? $s;
    }

    /** Texto limpo; valores de "vazio" do Pipedrive/planilhas ((sem cidade), None, -, N/A) viram nulo. */
    private function text($raw): ?string
    {
        $s = trim((string) $raw);
        if ($s === '' || in_array(mb_strtolower($s), ['none', 'null', '-', 'n/a', 'na', '(sem cidade)', 'sem cidade', '(vazio)'], true)) {
            return null;
        }

        return mb_substr($s, 0, 191);
    }

    private function phone($raw): ?string
    {
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }
        $s = preg_replace('/\s*[,;\/]\s*.*$/', '', $s) ?? $s; // só o primeiro telefone
        $digits = preg_replace('/\D/', '', $s);
        if (strlen($digits) === 11) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7));
        }
        if (strlen($digits) === 10) {
            return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
        }

        return mb_substr($s, 0, 30);
    }

    /** "Perfil do evento" do Pipedrive => nome do tipo de evento do sistema (parte do nome basta). */
    private const EVENT_TYPE_ALIASES = [
        '15 anos' => 'aniversario', 'festa de 15 anos' => 'aniversario', 'debutante' => 'aniversario', 'aniversario infantil' => 'aniversario',
        'aniversario de 1 ano' => 'aniversario', 'infantil' => 'aniversario', 'medicina' => 'formatura', 'curricular' => 'formatura',
        'parthenon med' => 'formatura', 'formatura ensino medio' => 'formatura', 'colacao' => 'formatura', 'corporativo' => 'comporativo',
        'empresarial' => 'comporativo', 'militar' => 'militar', 'outros' => 'diversos', 'outro' => 'diversos', 'evento social' => 'diversos',
        'social' => 'diversos', 'bodas' => 'bodas', 'noivado' => 'casamento', 'mini wedding' => 'casamento', 'elopement' => 'casamento',
        'cha' => 'diversos', 'batizado' => 'diversos', 'confraternizacao' => 'comporativo',
    ];

    /** Quando o perfil não bate de cara: parte do texto => tipo (ordem importa). */
    private const EVENT_TYPE_HINTS = [
        'casa' => 'casamento', 'wedding' => 'casamento', 'votos' => 'casamento', 'colacao' => 'formatura', 'curso' => 'formatura', 'medic' => 'formatura',
        'med ' => 'formatura', 'odonto' => 'formatura', 'curricular' => 'formatura', 'academ' => 'formatura', 'jaleco' => 'formatura', '3 ano' => 'formatura',
        'terceiro ano' => 'formatura', '3o ano' => 'formatura', 'confrat' => 'comporativo', 'aniver' => 'aniversario', 'outros' => 'diversos', 'baixo custo' => 'diversos', 'ensino medio' => 'formatura', 'residencia' => 'formatura', 'fomatura' => 'formatura', 'formatura' => 'formatura',
        'militar' => 'militar', 'espadim' => 'militar', 'corporativ' => 'comporativo', 'coquetel' => 'comporativo', 'congresso' => 'comporativo',
        'simposio' => 'comporativo', 'workshop' => 'comporativo', 'palestra' => 'comporativo', 'lancamento' => 'comporativo', 'leilao' => 'comporativo',
        'empresa' => 'comporativo', 'premiacao' => 'comporativo', 'solene' => 'comporativo', 'anivers' => 'aniversario', 'anos' => 'aniversario',
        'infantil' => 'aniversario', 'bebe' => 'aniversario', 'locacao' => 'locacao', 'camarote' => 'diversos', 'baile' => 'diversos', 'natal' => 'diversos',
        'ano novo' => 'diversos', 'fim de ano' => 'diversos', 'junina' => 'diversos', 'encontro' => 'diversos', 'reuniao' => 'diversos', 'familia' => 'diversos',
        'almoco' => 'diversos', 'festa' => 'diversos', 'evento' => 'diversos', 'pre' => 'diversos', 'ensaio' => 'diversos', 'sessao' => 'diversos',
    ];

    private function eventTypeId($rawType, $rawSegment, CommercialRule $rules, $types): ?int
    {
        $f = self::fold($rawType);
        if ($f !== '' && isset(self::EVENT_TYPE_ALIASES[$f])) {
            $alias = self::EVENT_TYPE_ALIASES[$f];
            $hit = $types->first(fn ($t) => str_contains(self::fold($t->name), $alias));
            if ($hit) {
                return (int) $hit->id;
            }
        }
        if ($f !== '') {
            $hit = $types->first(fn ($t) => self::fold($t->name) === $f) ?? $types->first(fn ($t) => str_contains($f, self::fold($t->name)) || str_contains(self::fold($t->name), $f));
            if ($hit) {
                return (int) $hit->id;
            }
        }
        if ($f !== '') {
            foreach (self::EVENT_TYPE_HINTS as $hint => $alias) {
                if (str_contains($f . ' ', $hint)) {
                    $hit = $types->first(fn ($t) => str_contains(self::fold($t->name), $alias));
                    if ($hit) {
                        return (int) $hit->id;
                    }
                }
            }
        }
        $seg = self::fold($rawSegment);
        if ($seg !== '') {
            foreach ($rules->segments ?? [] as $segment) {
                if (self::fold($segment['name']) === $seg || self::fold($segment['key']) === $seg) {
                    return isset($segment['event_type_ids'][0]) ? (int) $segment['event_type_ids'][0] : null;
                }
            }
        }

        return null;
    }

    /** Apaga de vez tudo que veio por importação (teste). */
    public function destroyImported(): int
    {
        $ids = Lead::withTrashed()->whereNotNull('imported_at')->pluck('id');
        if ($ids->isEmpty()) {
            return 0;
        }
        LeadStageChange::whereIn('lead_id', $ids)->delete();
        Lead::disableAuditing();
        foreach (Lead::withTrashed()->whereIn('id', $ids)->get() as $lead) {
            $lead->comments()->delete();
            $lead->activities()->delete();
            $lead->forceDelete();
        }
        Lead::enableAuditing();
        LostReason::withTrashed()->where('group', self::LOST_GROUP)
            ->whereNotIn('id', Lead::withTrashed()->whereNotNull('lost_reason_id')->select('lost_reason_id'))
            ->forceDelete();

        return $ids->count();
    }
}
