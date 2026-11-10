# CLAUDE.md

## Fluxo de trabalho

Todo o desenvolvimento acontece na branch `develop`. Antes de qualquer alteração, troque para `develop` e rode `git pull origin develop`. Depois de cada alteração pronta, NÃO envie sozinho: pergunte à pessoa se pode enviar a alteração (ela não vai lembrar de pedir, então a pergunta é obrigatória ao final de cada mudança). Com o OK, faça commit e `git push origin develop` na hora. Nunca faça push direto na `main` (ela é o que está em produção); a `main` só recebe mudanças por pull request da `develop`, quando o André pedir para levar tudo para produção.
