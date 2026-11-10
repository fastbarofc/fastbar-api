# CLAUDE.md

## Fluxo de trabalho

Todo o desenvolvimento acontece na branch `develop`.

- Antes de qualquer alteração, troque para `develop` e rode `git pull origin develop`.
- Depois de cada alteração pronta, faça commit e `git push origin develop` imediatamente.
- Nunca faça push direto na `main` (ela é o que está em produção); a `main` só recebe mudanças por pull request da `develop`, após aprovação do André.
