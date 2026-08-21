# Production deployment

The pilot runs as a Docker Compose stack on the Ubuntu VM. PostgreSQL and Redis are isolated from the host network; Nginx is exposed on port 8080 for direct LAN access and Cloudflare Tunnel can reach the same service over the Compose edge network.

## VM prerequisites

- Give the VM a DHCP reservation before production use.
- Install Docker Engine with the Compose plugin and Git.
- Create `/opt/aps-transport`, owned by the unprivileged deployment account.
- Copy `.env.example` to `.env`, replace every placeholder, and set a generated `APP_KEY`.
- Never commit `.env`, SSH keys, Cloudflare tokens, or backup credentials.
- Allow the deployment account to operate only this Docker host. Restrict SSH by key and firewall.

The N54L's two CPU cores and 8 GB RAM are adequate for a small pilot, but image builds happen in GitHub Actions so the server only pulls release images.

## GitHub configuration

Create a protected `production` environment with required reviewers. Add environment secrets `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_PATH`, and `DEPLOY_SSH_KEY`. Protect `main`, require the CI workflow, and disallow force-pushes.

After CI publishes a commit, manually run **Deploy production**, entering that full commit SHA. The deployment applies migrations, replaces containers, checks `/up`, and restores the previous release tag if health verification fails.

## Initial start

```sh
cd /opt/aps-transport
docker compose pull
docker compose run --rm app php artisan migrate --force --seed
docker compose --profile cloudflare up -d
docker compose ps
curl -fsS http://127.0.0.1:8080/up
```

Remove `BOOTSTRAP_ADMIN_PASSWORD` from `.env` after the account exists and recreate the app containers. The seeded account must enable 2FA/passkey before pilot use.

## Backups and recovery

The backup container writes daily custom-format PostgreSQL dumps plus SHA-256 files to `./backups`. Configure a separate NAS/cloud agent on the VM to copy that directory with encryption; backup creation alone is not off-site protection.

At least once before the pilot, restore the latest dump into a temporary PostgreSQL database, validate the checksum, run the application smoke test against it, and record the outcome. Persistent document/photo storage must also be copied from the `transport-storage` volume to NAS and cloud storage. A database-only backup is incomplete.

## Cloudflare and LAN

Create the tunnel in Cloudflare and point its HTTP service to `http://web:8080`. Keep Cloudflare Access in front of the public hostname. LAN users may use the VM's reserved IP and port 8080 initially; use an internal DNS name and trusted TLS certificate before treating direct LAN access as final production configuration.
