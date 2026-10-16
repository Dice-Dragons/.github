# XenForo Staging Server Deployment — Konzept & Vorlage

Dieses Dokument beschreibt die Architektur und Bereitstellung einer automatisierten Staging-Deployment-Pipeline für alle Add-ons der Organisation **Dice & Dragons**, sobald der Staging-Server vollständig eingerichtet ist.

---

## 🏗️ Architektur-Überblick

Sobald ein Release auf GitHub veröffentlicht wird (oder optional ein Push auf einen `staging`-Branch erfolgt):
1. **GitHub Actions** baut das Add-on Paket oder synchronisiert die Quelldateien.
2. Überträgt die Dateien per **SCP** (`appleboy/scp-action`) auf den Staging-Server in das XenForo-Add-on-Verzeichnis (`src/addons/cv6/<AddonDir>/`).
3. Führt per **SSH** (`appleboy/ssh-action`) die XenForo CLI-Befehle zur Aktualisierung und Cache-Bereinigung aus:
   ```bash
   php cmd.php xf:addon-upgrade cv6/<AddonDir> --quiet
   php cmd.php xf:rebuild-php-class-cache
   ```

---

## 🔑 Erforderliche GitHub Organization Secrets

In den GitHub Organisations-Einstellungen (**Dice-Dragons** -> *Settings* -> *Secrets and variables* -> *Actions*) werden folgende Secrets hinterlegt:

| Secret Name | Beschreibung | Beispielwert |
| :--- | :--- | :--- |
| `STAGING_SSH_HOST` | Hostname oder IP-Adresse des Staging-Servers | `staging.dice-dragons.de` |
| `STAGING_SSH_PORT` | SSH Port (Standard: 22) | `22` |
| `STAGING_SSH_USER` | Deployment-Benutzer auf dem Server | `deploy` oder `www-data` |
| `STAGING_SSH_KEY` | Privater SSH-Schlüssel (ed25519 ohne Passphrase) | `-----BEGIN OPENSSH PRIVATE KEY...` |
| `STAGING_FORUM_PATH` | Absoluter Web-Pfad zur XenForo-Installation | `/var/www/staging/forum` |

---

## 📄 Zentraler Reusable Workflow Vorlage

Pfad bei Aktivierung: `.github/workflows/staging-deploy.yml` in `Dice-Dragons/.github`:

```yaml
name: "Staging Deployment"

on:
  workflow_call:
    inputs:
      addon_id:
        description: "XenForo AddOn ID (z. B. cv6/Bump)"
        required: true
        type: string
      addon_dir:
        description: "Verzeichnisname unter src/addons/cv6/ (z. B. Bump)"
        required: true
        type: string
    secrets:
      STAGING_SSH_HOST:
        required: true
      STAGING_SSH_PORT:
        required: false
      STAGING_SSH_USER:
        required: true
      STAGING_SSH_KEY:
        required: true
      STAGING_FORUM_PATH:
        required: true

jobs:
  deploy:
    name: "Deploy to Staging Server"
    runs-on: ubuntu-latest
    steps:
      - name: Checkout Code
        uses: actions/checkout@v7

      - name: Prepare AddOn Payload
        run: |
          mkdir -p payload/src/addons/cv6/${{ inputs.addon_dir }}
          rsync -av --exclude='.git*' --exclude='.github' --exclude='_docs' --exclude='_releases' --exclude='*.bak' ./ payload/src/addons/cv6/${{ inputs.addon_dir }}/

      - name: Transfer Files via SCP
        uses: appleboy/scp-action@v0.1.7
        with:
          host: ${{ secrets.STAGING_SSH_HOST }}
          port: ${{ secrets.STAGING_SSH_PORT || 22 }}
          username: ${{ secrets.STAGING_SSH_USER }}
          key: ${{ secrets.STAGING_SSH_KEY }}
          source: "payload/src/addons/cv6/${{ inputs.addon_dir }}/*"
          target: "${{ secrets.STAGING_FORUM_PATH }}/src/addons/cv6/${{ inputs.addon_dir }}/"
          strip_components: 4

      - name: Execute XenForo CLI Upgrade via SSH
        uses: appleboy/ssh-action@v1.0.3
        with:
          host: ${{ secrets.STAGING_SSH_HOST }}
          port: ${{ secrets.STAGING_SSH_PORT || 22 }}
          username: ${{ secrets.STAGING_SSH_USER }}
          key: ${{ secrets.STAGING_SSH_KEY }}
          script: |
            cd ${{ secrets.STAGING_FORUM_PATH }}
            php cmd.php xf:addon-upgrade ${{ inputs.addon_id }} --quiet
            php cmd.php xf:rebuild-php-class-cache
```

---

## 🚀 Einbindung im Add-on (Sobald bereit)

In den Add-on Repositories (z. B. `src/addons/cv6/Bump/.github/workflows/deploy-staging.yml`):

```yaml
name: Deploy Staging

on:
  release:
    types: [published]
  workflow_dispatch:

jobs:
  staging:
    uses: Dice-Dragons/.github/.github/workflows/staging-deploy.yml@main
    with:
      addon_id: "cv6/Bump"
      addon_dir: "Bump"
    secrets: inherit
```
