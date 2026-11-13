# Medir o desempenho com muitos dados

Receita aplicada em 12/11/2026. Serve para repetir depois de importar dados de verdade.
**Sempre numa cópia de teste do banco, nunca no banco de verdade.** Os scripts se recusam a rodar
em banco cujo nome não tenha `volume` ou `teste`.

## 1. Cópia de teste com volume

```
mariadb-dump -uroot --port=3307 fastbar_api > backup.sql
mariadb -uroot --port=3307 -e "create database fastbar_volume character set utf8mb4"
mariadb -uroot --port=3307 fastbar_volume < backup.sql
mariadb -uroot --port=3307 fastbar_volume < scripts/desempenho/encher-volume.sql
DB_DATABASE=fastbar_volume php artisan migrate
```

`encher-volume.sql` leva o banco do seeder para 18 mil eventos, 15 mil clientes, 144 mil parcelas a receber,
108 mil contas a pagar, 270 mil drinks de evento, 270 mil linhas de histórico, 3 mil colaboradores,
20 mil leads e 90 mil mensagens. Contas antigas ficam pagas; só os últimos 45 dias ficam em aberto.

## 2. Medir as rotas da API

```
DB_DATABASE=fastbar_volume CACHE_DRIVER=array php scripts/desempenho/medir-rotas.php | sort -rn | head -40
```

Mostra tempo, número de consultas ao banco e tamanho da resposta de cada rota GET.
Procurar: mais de 300 ms, consultas que crescem com o número de linhas, resposta acima de 500 KB.

- Rotas com parâmetro: passar como argumento, por exemplo `"events/5150" "clients?per_page=1000"`.
- `MEDIR_SQL=1` na frente mostra as consultas mais repetidas (acha "uma consulta por linha").
- `MEDIR_MD5=1` mostra a impressão digital da resposta: rodar antes e depois de uma correção
  e comparar prova que o resultado não mudou.

## 3. Medir as telas

Script em `fastbar-ui/scripts/desempenho/medir-telas.js` (instruções no começo do arquivo).

## O que foi encontrado e corrigido em 12/11/2026

| Onde | Antes | Depois | Correção |
|---|---|---|---|
| Limite de consultas | 60 por minuto | 600 por minuto por pessoa | `RouteServiceProvider` |
| DRE, ARM e situação do DRE | estourava 1 GB de memória | DRE do mês 0,5 s, ARM do ano 3 s | Período filtrado no banco; evento sem o texto do contrato (`DreService`) |
| Funil (lista de leads) | 5 consultas por lead | 19 no total | `activities.user` carregado junto, regras comerciais guardadas por 5 s |
| Alertas do Comercial | 591 s | 1,5 s | Evento sem contrato na agenda + índice em `events.date` |
| Agenda do Comercial | 8 s | 0,2 s | Idem |
| Clientes, Fornecedores, Locais, Colaboradores, Compromissos | 4 a 7 consultas por linha | fixo | `with([...])` nas listas |
| Histórico filtrado por evento | 1,8 s | 0,05 s | `auditable_id` comparado como texto (usa o índice) |
| Histórico geral | contagem lenta | total guardado por 1 minuto | `ActivityHistoryService` |
| Desempenho do planejamento | 0,5 s | 0,1 s | Idem (texto no `auditable_id`) |
| Relatório de coordenação, PDF de eventos | carregavam o contrato | sem o contrato | `Event::light()` |

Índices novos: migration `2026_11_12_230000_add_performance_indexes`.

## Ficou como está (para decidir depois)

- Funil com 20 mil leads: 80 s e 45 MB, porque a tela recebe todos os leads de todos os anos
  (inclusive ganhos e perdidos). Só melhora deixando de mandar os fechados antigos.
- Contas a pagar: ao abrir, baixa todos os fornecedores e colaboradores em blocos de 1000.
- Financeiro diário: desenha todas as contas em aberto na página (50 mil elementos com 2 mil contas).
- `charts/financial` dá erro 500 no MariaDB local (GROUP BY), já dava antes desta receita.
