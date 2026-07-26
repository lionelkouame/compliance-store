# Configuration du DNS Local (`compliance-store.loc`)

Ce document explique comment configurer et utiliser le nom de domaine local `compliance-store.loc` pour le développement de l'application **Compliance Store**.

---

## 🎯 Objectif

Par défaut, l'application est configurée pour répondre au nom de domaine de développement local **`compliance-store.loc`** (avec bascule possible sur `localhost`).

L'utilisation d'un nom de domaine local dédié présente plusieurs avantages :
* Évite les conflits de cookies/session avec d'autres projets fonctionnant sur `localhost`.
* Reflète un environnement plus proche de la production (nom de domaine FQDN).
* Facilite la configuration de certificats TLS locaux personnalisés (ex: via `mkcert`).

---

## 🛠️ Configuration du Résolveur DNS Host

Pour que votre système d'exploitation dirige les requêtes vers `compliance-store.loc` vers votre machine locale (`127.0.0.1`), suivez la méthode adaptée à votre système :

### 1. Via la commande Makefile (Recommandé sur Linux / macOS)

Exécutez simplement la commande Makefile dédiée à la racine du projet :

```bash
make setup-dns
```

Cette commande vérifie si `compliance-store.loc` est présent dans `/etc/hosts` et l'ajoute avec des privilèges `sudo` si nécessaire.

### 2. Modification manuelle du fichier Hosts

* **Linux / macOS (`/etc/hosts`)** :
  Ajoutez la ligne suivante dans `/etc/hosts` :
  ```text
  127.0.0.1 compliance-store.loc
  ```

* **Windows (`C:\Windows\System32\drivers\etc\hosts`)** :
  Ouvrez PowerShell en tant qu'administrateur et exécutez :
  ```powershell
  Add-Content -Path C:\Windows\System32\drivers\etc\hosts -Value "127.0.0.1 compliance-store.loc"
  ```

### 3. Alternative : Utilisation de Dnsmasq ou systemd-resolved

Si vous utilisez **Dnsmasq**, ajoutez dans votre fichier de configuration (`/etc/dnsmasq.conf` ou `/etc/dnsmasq.d/loc.conf`) :

```text
address=/compliance-store.loc/127.0.0.1
```

Puis redémarrez le service dnsmasq : `sudo systemctl restart dnsmasq`.

---

## 🐳 Configuration Docker & Caddy (FrankenPHP)

Le fichier `compose.yaml` est configuré avec les variables d'environnement suivantes :

```yaml
services:
  php:
    environment:
      SERVER_NAME: ${SERVER_NAME:-compliance-store.loc, localhost}, php:80
      DEFAULT_URI: ${DEFAULT_URI:-https://compliance-store.loc:${HTTPS_PORT:-443}}
      MERCURE_PUBLIC_URL: ${CADDY_MERCURE_PUBLIC_URL:-https://compliance-store.loc:${HTTPS_PORT:-443}/.well-known/mercure}
```

* **`SERVER_NAME`** : Caddy accepte à la fois `compliance-store.loc`, `localhost` et les requêtes internes `php:80`.
* **`DEFAULT_URI`** : Symfony génère par défaut ses URLs CLI avec `https://compliance-store.loc`.

---

## 🔒 Certificats TLS & HTTPS pour `compliance-store.loc`

Caddy génère automatiquement des certificats autosignés pour `compliance-store.loc`.

### Option A : Faire confiance au certificat racine de Caddy

1. Récupérez le certificat racine généré par Caddy et installez-le dans le magasin de confiance de votre hôte (voir la documentation [tls.md](tls.md)) :
   ```bash
   # Sur Linux :
   docker cp $(docker compose ps -q php):/data/caddy/pki/authorities/local/root.crt /usr/local/share/ca-certificates/root.crt && sudo update-ca-certificates
   ```

### Option B : Générer des certificats locaux avec `mkcert`

1. Installez `mkcert` et l'autorité locale (`mkcert -install`).
2. Générez les certificats pour `compliance-store.loc` :
   ```bash
   mkdir -p frankenphp/certs
   mkcert -cert-file frankenphp/certs/tls.pem -key-file frankenphp/certs/tls.key "compliance-store.loc" "localhost"
   ```
3. Configurez `compose.override.yaml` pour injecter les certificats personnalisés (voir [tls.md](tls.md)).

---

## 🧪 Validation & Test

Après le démarrage des conteneurs (`docker compose up -d`) et la configuration du DNS :

1. **Test HTTP (Ping / Curl)** :
   ```bash
   curl -I http://compliance-store.loc
   ```

2. **Test HTTPS (Swagger UI)** :
   Ouvrez votre navigateur sur :
   * **[https://compliance-store.loc/api](https://compliance-store.loc/api)** (ou `http://compliance-store.loc/api`)

3. **Clients HTTP IDE (JetBrains / Postman / VS Code)** :
   Utilisez le fichier de variables `tests/http/http-client.env.json` qui pointe désormais sur `http://compliance-store.loc` et `https://compliance-store.loc`.
