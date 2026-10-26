# 5. Dépôt Git et Dependabot

## 5.1 Situation

Le dépôt d'origine `lionelkouame/compliance-store` n'accorde que le droit de lecture au compte GitHub de l'utilisateur (Hiro) (`push: false`). Un push direct sur son `main` est impossible. Le travail a donc été poussé sur le dépôt personnel `tontonlionelAPI` de l'utilisateur, initialement vide.

## 5.2 Identité Git

Le dépôt local portait une identité générique (`imagine-dev <imagine-dev@noreply.invalid>`). Elle a été remplacée, pour ce dépôt uniquement (`git config` sans `--global`), par l'identité GitHub du compte, avec l'adresse noreply qui relie les commits au profil sans exposer d'adresse personnelle :

```
git config user.name "Hiro"
git config user.email "<adresse noreply GitHub du compte>"
```

## 5.3 Commits

Deux commits séparés, pour distinguer l'infrastructure du code. Le hook `pre-commit` a exécuté `make test` sur chacun, avec succès.

| Commit | Contenu | Fichiers |
|--------|---------|----------|
| `726d17b` `fix(dev-stack): run on current FrankenPHP image and DBAL 4.5` | Documents 2 et 3 | `frankenphp/Caddyfile.dev`, `compose.override.yaml`, `composer.lock` |
| `77465e6` `fix(holder): resolve PHPStan errors and failing document upload` | Document 4 | 9 fichiers `src/`, 2 fichiers `tests/`, `CHANGELOG.md` |

Les messages ont été réécrits une fois (`git filter-branch --msg-filter`) pour retirer une ligne de co-auteur ajoutée par l'outillage, puis les branches ont été forcées avec `--force-with-lease`. Le contenu des arbres est identique avant et après la réécriture.

## 5.4 Branches poussées

| Remote | URL |
|--------|-----|
| `origin` | `https://github.com/lionelkouame/compliance-store.git` (lecture seule) |
| `perso` | dépôt personnel `tontonlionelAPI` sur le compte GitHub de l'utilisateur |

| Branche sur `perso` | Commit de tête | Contenu |
|---------------------|----------------|---------|
| `fix/holder-phpstan-and-dev-stack` | `77465e6` | Historique d'origine + les deux correctifs. Branche propre, destinée à une éventuelle pull request vers le propriétaire. |
| `main` | `cb43859` | Idem + suppression de `.github/dependabot.yaml`. Branche de travail personnelle. |

En local, la branche `perso-main` suit `perso/main`. La branche `main` locale reste alignée sur `origin/main` (`675654f`).

## 5.5 Dependabot

### Ce que fait `.github/dependabot.yaml`

Dependabot est le robot GitHub qui ouvre des pull requests de mise à jour des dépendances. Le fichier du projet surveille chaque semaine trois écosystèmes (images Docker, paquets Composer, actions GitHub), préfixe les commits par `chore`, attend 7 jours après une sortie avant de la proposer, et exclut `doctrine/orm >= 3.7` (exclusion devenue inutile depuis la montée de DBAL, voir document 3).

### Pourquoi il s'est activé sur le dépôt personnel

GitHub active Dependabot dès qu'il trouve ce fichier. Au premier push, il a analysé `composer.lock` et créé six branches et six pull requests en quelques minutes (api-platform/core 5.0.4, phpstan 2.3.1, framework-bundle 8.1.9, css-selector 8.1.6, http-client 8.1.9, super-linter 9).

### Désactivation

GitHub ne propose ni interrupteur ni API pour couper les mises à jour de version : la documentation officielle indique de supprimer le fichier. C'est ce qui a été fait, sur `main` du dépôt personnel uniquement, dans le commit `cb43859`. Les six pull requests ont été fermées et leurs branches supprimées. Les alertes de sécurité et les correctifs automatiques de sécurité étaient déjà inactifs sur ce dépôt.

La branche `fix/holder-phpstan-and-dev-stack` conserve le fichier : si elle sert un jour à une pull request vers le dépôt d'origine, celui-ci garde sa configuration intacte.

## 5.6 Pour proposer ces correctifs au propriétaire

Deux voies :

1. Le propriétaire accorde le droit d'écriture au compte GitHub de l'utilisateur (Hiro), et la branche `fix/holder-phpstan-and-dev-stack` est poussée sur `origin` puis ouverte en pull request.
2. Un fork de `lionelkouame/compliance-store` est créé depuis l'interface GitHub, la branche `fix/holder-phpstan-and-dev-stack` y est poussée, et une pull request est ouverte vers `main` du dépôt d'origine. Le template `.github/PULL_REQUEST_TEMPLATE.md` demande un résumé, le type de changement (ici « Bug fix » et « Tech »), la méthode de test (`make test`) et une checklist.

Dans les deux cas, le commit `cb43859` ne doit pas être inclus.
