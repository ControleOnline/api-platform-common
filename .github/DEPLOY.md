# Deploy (api-platform-common)

Submodule de `api-community`. Deploy preferencial = push no parent.

| Workflow | Branch | Path remoto | Secrets |
|----------|--------|-------------|---------|
| `deploy-dev.yml` | `dev` | `/var/www/api-community-dev/modules/controleonline/common` | `DEV_HOST`, `DEV_USER`, `DEV_PASS` |
| `deploy-staging.yml` | `staging` | `/var/www/api-community/modules/controleonline/common` | `STAGING_HOST`, `STAGING_USER`, `STAGING_PASS` |
| `deploy-master.yml` | `master` | `~/sistemas/controleonline/api/modules/controleonline/common` | `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT` |

**Staging não usa `DEV_*`.**

Secrets devem existir neste repositório (ou org secrets com acesso a ele).
