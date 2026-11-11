# Propriedades de aparência dos módulos — preparação local

Base do pacote: controleonline/common v1.0.9, commit fdc43540fb9266a1570f11c79fdf84d871b2bf94, o mesmo instalado na API local. A fonte está em um checkout separado; o vendor da API não foi alterado nem apontado para esta branch.

## Contrato

| Entidade | Propriedades JSON | Colunas opcionais VARCHAR(50) |
| --- | --- | --- |
| Category | textColor, iconColor | text_color, icon_color |
| Routes | textColor, iconColor, iconBackgroundColor | text_color, icon_color, icon_background_color |

color e icon existentes não são renomeados nem copiados. Valores ausentes não alteram o cadastro; null/string vazia limpa apenas a propriedade opcional para usar o tema. Referências $token e cores CSS válidas são aceitas, com até 50 caracteres; CSS global como inherit/currentColor e entradas malformadas são recusados.

MenuConfigService envia os novos campos no catálogo e nos menus de execução. MenuRepository conserva todos os filtros atuais de empresa, pessoa, perfil, appType e menuType; acrescenta somente colunas de apresentação. As operações menu-config mantêm ROLE_SUPER. Os novos atributos de entidade têm apenas grupo menu:read, sem habilitar escrita pelos endpoints genéricos de categorias/rotas.

A migração Version20261110220000 apenas acrescenta cinco colunas com DEFAULT NULL. Não há atualização de registros nem exclusão de cores. O rollback estrutural é recusado para não apagar personalizações; voltar o aplicativo antigo pode manter as colunas opcionais. Nenhum método up/down ou runner de migração foi executado nesta preparação.

## Aplicação

Com a chave ligada, cada propriedade cadastrada vale em sua própria finalidade; sem valor, usa o tema. Desligada, todas as propriedades opcionais são ignoradas e o tema permanece preservado. Fundo do ícone da rota vale somente na grade, onde esse fundo já existe. Cor do ícone de categoria não introduz ícones em cabeçalhos/grupos que atualmente não os mostram; é consumida onde já há representação desse ícone, como na prévia da categoria.

No app, ausência dos campos na resposta de uma API antiga significa prévia local, explicitamente identificada, e eles não são enviados para gravação. A presença de chaves com null habilita a persistência correspondente para ADMIN/ROLE_SUPER. Nenhuma propriedade desses cadastros é armazenada em theme.colors.

## Testes sem banco

O bootstrap tests/appearance-bootstrap.php aceita COMMON_API_AUTOLOAD apontando para um vendor/autoload.php existente e substitui apenas o mapeamento das classes em teste pela fonte local. Não inicia o kernel da API. EntityManager, repositórios e conexão são simulados. O teste da migração inspeciona o texto/colunas planejadas sem chamar up/down ou executar SQL.

Execução a partir deste checkout:

```sh
COMMON_API_AUTOLOAD=../api-community/vendor/autoload.php php ../api-community/vendor/bin/phpunit --no-configuration --bootstrap tests/appearance-bootstrap.php tests/Service/MenuAppearancePropertiesTest.php tests/Service/MenuConfigServiceTest.php
```

Resultado: 15 testes, 125 asserções aprovadas; quatro depreciações preexistentes de tipos nullable em entidades carregadas. Verificação sintática dos seis arquivos PHP de produto/migração aprovada. Nenhuma conexão com banco real foi usada.

## Ativação futura, somente após autorização

1. Revisar/publicar uma versão estável do pacote comum com o código e a migração; escolher a versão disponível naquele momento.
2. Atualizar a dependência estável na API, preservando os demais pins. Manter o tráfego suspenso enquanto o novo mapeamento e a migração são instalados/aplicados: as consultas novas dependem das colunas novas.
3. Autorizar e aplicar explicitamente a migração nos tenants corretos antes de liberar a API com esse código. Não usar schema:update nem gerar alterações alheias a estes cinco campos.
4. Publicar o app e validar leitura/gravação/recarregamento autorizado e chave ligada/desligada, preservando vínculos e dados existentes.

Os commits desta preparação são somente locais e pertencem à mesma task ControleOnline/app-community#1496. Não foram publicados pacotes, alterados composer.json/composer.lock da API, executadas migrações, gravados dados ou feitos pushes. As futuras integrações em dev/staging dependem de autorização de publicação e da ordem de ativação descrita acima.

## Integração de fonte em dev/staging — task #1496

Após a preparação local descrita acima, foi autorizada a publicação das branches e integração do código em dev/staging, mantendo a mesma task. Isso não autoriza ativar o novo mapeamento nos servidores, executar a migração ou gravar dados. Os merges de integração deste pacote/API devem conter `[skip ci] [skip-db-migrations]` para não disparar deploy, bootstrap ou migrações automáticas. O app permanece compatível com a API anterior, mostrando como prévia os campos ainda não suportados.

Não criar tag estável nem atualizar o pin `controleonline/common` da API nesta etapa. A liberação efetiva das novas propriedades depende da autorização posterior da migração e da ativação coordenada do pacote, com o tráfego suspenso até o schema estar pronto. Nenhum aceite desta integração de fonte equivale a homologação de persistência em banco.
