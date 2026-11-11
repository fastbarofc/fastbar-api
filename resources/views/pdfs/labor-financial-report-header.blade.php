<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Arial', sans-serif; margin: 0; color: #2f2b26; font-size: 11px; }
        table { border-collapse: collapse; }
        .header { width: 100%; border-bottom: 2px solid #aa8534; margin-bottom: 8px; }
        .header td { padding: 0 0 4px; vertical-align: middle; }
        .header td.logo { width: 130px; }
        .header td.logo img { height: 48px; }
        .header h1 { font-size: 17px; margin: 0; letter-spacing: 1px; text-align: center; }
        .header .sub { font-size: 10px; color: #6c665c; text-align: center; margin-top: 2px; }
        .idbox { width: 110px; border: 2px solid #2f2b26; text-align: center; }
        .idbox .lbl { background: #2f2b26; color: #fff; font-weight: bold; font-size: 10px; padding: 2px 0; letter-spacing: 1px; }
        .idbox .val { font-size: 20px; font-weight: bold; padding: 2px 0; }
        .info { width: 100%; margin-bottom: 4px; }
        .info td { border: 1px solid #b9ad94; padding: 5px 7px; font-size: 13px; }
        .info td.v { width: 85px; white-space: nowrap; }
        .info td.k { background: #f6f0e2; font-weight: bold; text-align: right; width: 70px; font-size: 11px; white-space: nowrap; }
        body { margin: 0; }
        .decl-h { font-size: 12px; font-weight: bold; text-align: center; margin: 4px 0 2px; letter-spacing: 0.5px; }
        .decl-p { font-size: 11px; text-align: justify; margin: 0; }
        #decl-coord { display: none; }
        .coord-box { border: 1px solid #b9ad94; padding: 4px 8px; font-size: 11px; text-align: justify; }
        .coord-box b.t { display: block; text-align: center; font-size: 11px; margin-bottom: 2px; }
        .pg { font-size: 10px; color: #6c665c; text-align: center; margin-top: 2px; }
    </style>
    <script>
        // wkhtmltopdf manda page/topage na URL do cabeçalho: escreve "1 de 10".
        function paginas() {
            var v = {}, q = document.location.search.substring(1).split('&');
            for (var i in q) { var z = q[i].split('=', 2); v[z[0]] = decodeURIComponent(z[1] || ''); }
            var el = document.getElementById('pg');
            if (el && v.page) el.textContent = 'Página ' + v.page + ' de ' + v.topage;
            // A capa (página 1) tem a declaração dos coordenadores; a da equipe sai da página 2 em diante.
            var d = document.getElementById('decl');
            var c = document.getElementById('decl-coord');
            if (v.page === '1') { if (d) d.style.display = 'none'; if (c) c.style.display = 'block'; }
        }
    </script>
</head>
<body onload="paginas()">
@php $eventDate = $event->date ? \Carbon\Carbon::parse($event->date)->format('d/m/Y') : ''; @endphp

<table class="header">
    <tr>
        <td class="logo"><b style="font-size:20px;">Fastbar</b></td>
        <td>
            <h1>RELATÓRIO FINANCEIRO DE EVENTOS</h1>
            <div class="sub">emitido em {{ $date }}</div>
            <div class="pg" id="pg"></div>
        </td>
        <td style="width:110px">
            <table class="idbox"><tr><td class="lbl">ID</td></tr><tr><td class="val">{{ $event->id }}</td></tr></table>
        </td>
    </tr>
</table>
<table class="info">
    <tr>
        <td class="k">EVENTO:</td><td>{{ $event->name }}</td>
        <td class="k">LOCAL:</td><td>{{ $event->eventPlace?->name }}</td>
        <td class="k">DATA:</td><td class="v">{{ $eventDate }}</td>
        <td class="k">HORÁRIO:</td><td class="v">{{ $event->time ? substr($event->time, 0, 5) : '' }}</td>
    </tr>
    <tr>
        <td class="k">CLIENTE:</td><td>{{ $event->client?->name }}</td>
        <td class="k">CIDADE:</td><td>{{ $city }}</td>
        <td class="k">DURAÇÃO:</td><td class="v">{{ $event->duration ? rtrim(rtrim(number_format((float) $event->duration, 1, ',', ''), '0'), ',') . 'h' : '' }}</td>
        <td class="k">VALOR BASE:</td><td class="v">{{ $baseValue !== null ? 'R$ ' . number_format((float) $baseValue, 2, ',', '.') : '' }}</td>
    </tr>
</table>
<div id="decl">
    <div class="decl-h">DECLARAÇÃO DE RECEBIMENTO</div>
    <p class="decl-p">
        Declaro para os devidos fins que participei do evento acima descrito, no qual recebi da empresa Organização Baratella e Baltazar Eventos Eireli,
        inscrita no CNPJ nº 01.005.782/0001-14, em moeda corrente a importância abaixo discriminada. Declaro ainda que o trabalho foi prestado na
        qualidade de autônomo e de forma eventual.
    </p>
</div>
<div id="decl-coord">
    <div class="decl-h">DECLARAÇÃO DE RECEBIMENTO</div>
    <div class="coord-box">
        <b class="t">DECLARAÇÃO DE RECEBIMENTO DO RELATÓRIO <i>PÓS EVENTO DE COORDENAÇÃO</i></b>
        Declaro que recebi do Maitre responsável pelo evento o <b>Relatório Pós Evento de Coordenação</b> e comprometo-me a realizar o preenchimento devido e
        depositá-lo na <b>Caixa de Relatórios</b> localizada na portaria do Fastbar até as <b>10h da segunda-feira que sucede ao evento</b>, sob pena de
        não receber o valor referente à Coordenação em caso de descumprimento.
    </div>
</div>

</body>
</html>
