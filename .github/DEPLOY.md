# Deploy (api-platform-common)

**Um workflow:** `.github/workflows/deploy.yml` — steps configure → deploy.

Preferencial: deploy pelo parent **api-community** (atualiza todos os submodules).

Secrets por ambiente (sem misturar):

| env | secrets |
|-----|---------|
| dev | `DEV_HOST`, `DEV_USER`, `DEV_PASS` |
| staging | `STAGING_HOST`, `STAGING_USER`, `STAGING_PASS` |
| master | `API_HOST`, `USER`, `CONTROLEONLINE`, `PORT` |
