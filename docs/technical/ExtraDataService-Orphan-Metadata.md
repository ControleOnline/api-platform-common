# ExtraDataService — metadata Doctrine órfã (fail-closed)

Documentação técnica da entrega **api-community#88** (hotfix de produção), lado `api-platform-common`.

## Papel do serviço

`ControleOnline\Service\ExtraDataService` resolve entidades a partir de `ExtraFields` / `ExtraData` (lookup por contexto + nome de campo + código). É usado por integrações, inclusive o webhook iFood (`resolveProviderByMerchantId` / `resolveWebhookSecrets`).

Método crítico:

```text
getEntityByExtraData(string $context, string $fieldName, string $code, object|string $entity): ?object
```

Após localizar o registro de extra-data, o serviço faz `EntityRepository::find($entityId)`.

## Problema

Se o ClassMetadata Doctrine da entidade alvo (ou de associação hidratada no grafo, ex.: `Document` ligado a `People`) estiver **órfão** — referencia propriedade que não existe mais na classe — o `find()` dispara `ReflectionException` / `MappingException` no `wakeupReflection`. O request HTTP inteiro vira 500.

Caso concreto em produção: `Document::$vehicle` (ver página canônica em people).

## Correção

`getEntityByExtraData` engole exceções de mapping e devolve `null`:

```php
try {
    return $this->manager->getRepository($class->getName())->find($extraData->getEntityId());
} catch (\ReflectionException
    | \Doctrine\Persistence\Mapping\MappingException
    | \Doctrine\ORM\Mapping\MappingException $exception) {
    // Stale Doctrine metadata (e.g. Document::$vehicle) must not 500 webhooks.
    return null;
}
```

Regras:

- **Fail-closed no lookup**: melhor `null` (caller usa fallback) do que 500.
- Não loga payload de webhook nem segredo.
- Não altera o contrato de sucesso (quando a entidade existe e a metadata está ok).
- Não mascara erros de negócio distintos (argumentos vazios, extra-field inexistente) — esses já retornavam `null` antes.

Teste automatizado: `ExtraDataServiceTest::testGetEntityByExtraDataReturnsNullWhenDoctrineMetadataIsOrphan`.

## Visão do módulo

| Contexto | Comportamento |
| --- | --- |
| `api-platform-common` | Utilitário transversal de extra-data; não conhece iFood |
| Callers (ex.: integration iFood) | Devem tratar `null` como “não resolvido” e seguir fallback / recusa controlada |
| Entidades de domínio (`Document`, etc.) | Continuam responsáveis por manter ClassMetadata alinhado à classe |

## Links

| Destino | Link |
| --- | --- |
| Página canônica (people / Document) | https://github.com/ControleOnline/api-platform-people/wiki/Document-Vehicle-Metadata-Compatibility |
| Issue | https://github.com/ControleOnline/api-community/issues/88 |
| Home deste módulo | https://github.com/ControleOnline/api-platform-common/wiki |
| Wiki API | https://github.com/ControleOnline/api-community/wiki |

Cópia versionada no Git (quando existir): `docs/technical/ExtraDataService-Orphan-Metadata.md`
