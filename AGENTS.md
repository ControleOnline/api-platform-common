## Escopo
- Modulo compartilhado da API.
- Reune infraestrutura comum: configs, device configs, uploads, imports, traducoes, listeners, filtros e servicos transversais.

## Quando usar
- Prompts sobre utilitarios compartilhados, configuracoes globais, importacao, upload, eventos comuns e funcionalidades reaproveitadas por varios modulos.

## Limites
- Nao mover para `common` regra de negocio que pertence claramente a um dominio especifico.
- `common` deve servir de base para os outros modulos, nao virar dono de regras de `orders`, `financial`, `people` ou `products`.
- O `DefaultEventListener` precisa preservar o estado anterior real da entidade no `preUpdate`, porque o contrato de `onEntityChanged` depende desse diff.
- `HydratorService` define o payload padrao interno das colecoes e itens. Quando algum endpoint customizado ou decorado precisar manter filtros/paginacao da API Platform, adapte o resultado para esse payload aqui em vez de criar tolerancia no frontend.
- Quando a API Platform serializar colecoes Hydra do fluxo padrao, a adaptacao para `member`, `totalItems`, `search` e `view` deve acontecer nos normalizers compartilhados de `common`, sem criar controllers por recurso so para isso.
