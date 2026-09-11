---
inclusion: always
---

## Conventions git & GitHub

### Commits — Conventional Commits

```
<type>(<portée optionnelle>): <description à l'impératif, en anglais>
```

| Type | Usage |
| :--- | :--- |
| `feat` | nouvelle capacité fonctionnelle |
| `fix` | correction de bug |
| `refactor` | réorganisation sans changement de comportement |
| `test` | ajout ou correction de tests seuls |
| `docs` | documentation, ADR, steering |
| `chore` | dépendances, outillage, CI |

Référence d'issue quand elle existe : `feat: #40 prevent domain leak through StorageKey`.

**Un commit = une tâche du `tasks.md` = un gate vert.** Ne jamais grouper deux tâches,
ne jamais commiter sur un gate rouge.

Le corps du message explique le **pourquoi**, pas le quoi — le diff dit déjà le quoi.

> Ce dépôt est **public**. Un message de commit ne cite jamais la roadmap privée,
> l'association, ni un arbitrage stratégique. Il cite une issue publique ou un ADR.

### Branches

```
features/<numéro-issue>-<slug>     features/34-us-refacto-infrastructure
fix/<slug>                         fix/document-read-empty-payload
```

Jamais de commit direct sur `main`.

### Pull requests

Le gabarit `.github/PULL_REQUEST_TEMPLATE.md` est obligatoire. En particulier :

- `Closes #<issue>` renseigné ;
- section « How was this tested? » : **coller la sortie de `make test`**, pas la décrire ;
- « No unrelated changes bundled in » : une PR = un périmètre.

La CI (`.github/workflows/ci.yaml`) rejoue tests + lint. Une PR rouge ne se discute pas.

### Issues

Deux gabarits : `epic.yml` (objectif métier, sans détail d'implémentation) et
`user_story.yml` (une capacité livrable). Les user stories se rattachent à leur epic
via **Add sub-issue**.

Une issue publique se rédige de façon **autoportante** : compréhensible par un
contributeur externe qui n'a accès ni à la roadmap ni à la spec privée.

### Git hooks

```bash
make setup-hooks     # active .githooks — à faire une fois par clone ou worktree
```

### Interdits sans validation humaine

```
✗ merge d'une PR          ✗ push sur main          ✗ push --force
✗ tag ou release          ✗ suppression de branche distante
```
