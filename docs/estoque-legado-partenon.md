# Estoque legado do Partenon (removido)

Este repositório nasceu do sistema do Partenon, que tinha um módulo de estoque com setores, requisições e compras com aprovação. O Fastbar nunca usou esse módulo (ele ficava escondido pelas feature flags `stock` e `purchases` do front). Em novembro de 2026 ele foi removido para dar lugar ao estoque do Fastbar, que é mais simples e ligado ao menu de drinks dos eventos.

## O que existia

| Parte | Tabelas | Função no Partenon |
| --- | --- | --- |
| Estoques (setores) | `stocks` | Locais de estoque. Em produção do Fastbar foram renomeados para categorias, mas nunca receberam movimentação real. |
| Saldo | `stock_levels` | Saldo, preço médio e limites mínimo e máximo por produto e estoque, recalculados a partir das movimentações. |
| Movimentações | `stock_movements` | Entradas e saídas ligadas por morph a ordem de compra, requisição ou devolução. Quantidade inteira. |
| Pedido de compra | `purchase_requests`, `purchase_request_product` | Pedido feito por um setor, com aprovação. |
| Ordem de compra | `purchase_orders`, `purchase_order_product` | Compra com empresa, centro de custo e operação financeira. A entrada no estoque acontecia ao mudar o status para recebido. |
| Requisição | `requisitions`, `requisition_items` | Pedido de saída de um estoque para um evento ou outro estoque. |
| Devolução | `devolutions`, `devolution_items` | Retorno dos itens de uma requisição. |

O código removido junto: models, controllers, requests, resources, policies, factories e seeders dessas tabelas, o `StockService`, o `PurchaseRequestService` e o comando `RecalculateStockLevels`. No front saíram os módulos `stocks`, `stock-movements`, `purchases`, `purchase-orders`, `purchase-requests`, `requisitions` e `devolutions`, a aba Requisições do evento e a aba Estoques das Configurações.

Também foram apagadas as permissões `compras-*`, `devolucoes-*`, `requisicoes-*` e `configuracoes-estoques-*`. A permissão `estoque-visualizar` foi mantida e reaproveitada.

## Por que as tabelas foram apagadas

Em produção essas tabelas só tinham os dados do seed rodado pela CI em 15/09/2026 (produtos fictícios como "Batata Cozido 7989"). Manter as tabelas e os models criaria dois módulos de estoque com nomes parecidos, então a migration `2026_11_11_000003_drop_partenon_stock_tables` apaga tudo. As migrations antigas que criavam essas tabelas continuam no histórico porque já rodaram em produção.

Se algum dia for preciso consultar o modelo antigo, ele está no histórico do git antes do commit que removeu o módulo.

## O estoque atual do Fastbar

- Um único estoque. O saldo de cada produto é a soma das movimentações (`stock_movements`, nova tabela com quantidade decimal).
- Entradas por compra (`stock_purchases` e `stock_purchase_items`) e ajustes manuais com motivo (`stock_adjustments`).
- Saída e retorno por evento (`event_stocks` e `event_stock_items`). A previsão de cada produto é `ceil(consumo por convidado × convidados ÷ volume)`, com os produtos dos kits do evento e os componentes dos drinks.
- Limite mínimo e máximo por produto em `stock_limits`.
