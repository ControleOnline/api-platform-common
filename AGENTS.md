## Escopo
- Modulo compartilhado da API.
- Reune infraestrutura comum: configs, device configs, uploads, imports, traducoes, listeners, filtros e servicos transversais.

## Quando usar
- Prompts sobre utilitarios compartilhados, configuracoes globais, importacao, upload, eventos comuns e funcionalidades reaproveitadas por varios modulos.

## Limites
- Nao mover para `common` regra de negocio que pertence claramente a um dominio especifico.
- `common` deve servir de base para os outros modulos, nao virar dono de regras de `orders`, `financial`, `people` ou `products`.
