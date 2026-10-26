# Requêtes HTTP d'exemple

Fichiers `.http` exécutables avec le client HTTP de JetBrains ou l'extension REST Client de VS Code.

## Avant la première requête (pour tout le monde)

1. Créer une clé d'API dans le conteneur, avec le rôle le plus faible qui suffit :
   ```bash
   make sf c="app:api-client:create <votre-nom> --role=ROLE_ADMIN"
   ```
2. Copier `http-client.private.env.json.dist` vers `http-client.private.env.json` (ignoré par Git) et coller la clé dans `apiKey`.
3. Choisir l'environnement `dev`, `local-https`, `localhost` ou `dev-win` (`http-client.env.json`).

Toutes les requêtes envoient `Authorization: Bearer {{apiKey}}`. Sans clé valide : 401. Rôle insuffisant : 403.

## Ordre conseillé

| Fichier | Ce qu'il fait | Variable produite |
|---------|---------------|-------------------|
| `v1/system/health.http` | Santé de l'API, sans clé | — |
| `v1/holders/post.http` | Inscrit un porteur, le relit, teste un 404 | `holderId` |
| `v1/documents/store-document.http` | Dépose un document chiffré, teste un 422 | `documentId` |
| `v1/documents/consult-document.http` | Métadonnées, liste par porteur, téléchargement déchiffré | — |
| `v1/identity-documents/register-identity-document.http` | Enregistre une pièce d'identité | — |
| `v1/storage-spaces/*.http` | Espaces de stockage (`ROLE_ADMIN` pour créer et modifier) | `storageSpaceId` |
| `v1/audit/list-audit-events.http` | Journal d'audit (`ROLE_ADMIN`) | — |
