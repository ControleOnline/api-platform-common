# Deploy (api-platform-common)

Submodule de `api-community`. **Deploy preferencial** = push no parent `api-community` (dev/staging/master),
que já faz `submodule foreach` no branch correto.

| Workflow | Branch | Path remoto |
|----------|--------|-------------|
| `deploy-dev.yml` | `dev` | `/var/www/api-community-dev/modules/controleonline/common` |
| `deploy-staging.yml` | `staging` | `/var/www/api-community/modules/controleonline/common` |
| `deploy-master.yml` | `master` | `~/sistemas/controleonline/api/modules/controleonline/common` |

Secrets necessários neste repo (ou org):
- Dev/Staging: `DEV_HOST`, `DEV_USER`, `DEV_PASS`
- Master: `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT`

Se os secrets estiverem vazios, o job falha imediatamente (não usa dados de outro ambiente).
