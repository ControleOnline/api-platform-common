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
- Para resumos e agregacoes de colecoes, prefira o padrao de entidade com `CollectionSummary`, `CollectionSummaryProvider` e `CollectionSummaryNormalizer` em vez de criar controller customizado so para calcular `summary`.
- Controllers customizados so entram quando a entidade e o fluxo padrao da API Platform realmente nao cobrirem o caso; para listagens internas, a prioridade e manter o comportamento no padrao de entidade/provider/normalizer.
- A politica global de logs e alertas de erro deve ficar centralizada em servicos compartilhados deste modulo, usando a empresa principal como dona da configuracao publica.
- Captura de excecao backend, persistencia em `log`, filtros de habilitacao e retencao nao devem ser implementados em varios pontos com regras duplicadas.
- O cron geral de manutencao e suas rotinas tecnicas devem usar o Scheduler do Symfony e ler a agenda a partir de configuracoes da empresa principal.
- Chaves tecnicas de `config` como logs, rotinas e credenciais centrais devem ser filtradas no backend e nunca podem ser gravadas fora da `defaultCompany`.

## Regras de traducao
- A traducao especifica da empresa selecionada deve prevalecer sobre qualquer fallback.
- Quando nao existir traducao propria para a empresa selecionada, a API deve considerar a traducao da empresa principal como fallback de leitura.
- Empresas que nao sao a principal precisam conseguir consultar a traducao da empresa principal como referencia para criar a propria sobrescrita.
- Somente usuarios com acesso a uma empresa podem criar ou alterar traducoes vinculadas a ela.
- A consulta no contexto de empresa secundaria nao deve permitir alterar a traducao da empresa principal indiretamente; a sobrescrita precisa ser gravada na propria empresa secundaria.
