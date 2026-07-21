[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/controleonline/api-platform-common/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/controleonline/api-platform-common/?branch=master)

# common

## Identidade operacional do device

- Requisicoes contextualizadas por equipamento usam os headers `DEVICE` e `DEVICE-TYPE`.
- O tipo de configuracao tambem pode ser informado explicitamente como `deviceType`; o parametro generico `type` permanece reservado aos filtros do recurso solicitado.
- A visao de aplicativo `POS` usa o tipo operacional canonico `PDV`.

## Categorias

- Leituras internas de `Category` passam por `CategoryService::securityFilter()` e sao escopadas por `company`.
- O catalogo anonimo usa `GET /shop/categories` e `GET /shop/categories/{id}`.
- As rotas publicas exigem `PeopleDomain.domainType=SHOP`, aceitam somente a empresa do dominio ou empresas publicadas na configuracao e retornam apenas o contexto `products`.


`composer require controleonline/common:dev-master`


Add Service import:
config\services.yaml

```yaml
imports:
    - { resource: "../modules/controleonline/common/config/services/services.yaml" }    
```
