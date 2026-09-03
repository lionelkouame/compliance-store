---
slug: pourquoi-ce-site
title: Pourquoi ce site
authors: [lionel]
tags: [genese]
---

Compliance Store avance par petites itérations : des refactos ciblées, des value objects qui remplacent des champs génériques, des choix d'architecture qu'il faut documenter pour ne pas les oublier. Jusqu'ici, cette histoire vivait uniquement dans les messages de commit et les ADR du dépôt.

Ce site a un objectif simple : rendre cette histoire lisible pour quelqu'un qui n'a pas suivi le projet au jour le jour.

{/* truncate */}

## Ce qu'on y trouvera

- **Une documentation courte et à jour** (section [Documentation](/docs/intro)) qui donne une vue d'ensemble du projet et de son architecture, sans remplacer les ADR détaillées du dépôt.
- **Un journal de bord** (cette section, le blog) qui raconte au fil de l'eau les refactos, les décisions techniques et les moments clés : pourquoi on a supprimé tel value object, pourquoi on a changé telle approche de validation, etc.

## Ce que ce site n'est pas

Ce n'est pas la documentation technique de référence : celle-ci continue de vivre dans `docs/` à la racine du dépôt de code, avec les ADR (Architecture Decision Records) qui tracent chaque décision d'architecture en détail. Ce site en donne une vue simplifiée et raconte le contexte autour.

## Prochains articles

Pour rester cohérent, les prochains articles suivront cette convention :

- **Dossier** : `blog/AAAA-MM-JJ-slug-en-kebab-case/index.md` (un dossier par article, pour pouvoir y ajouter des images à côté).
- **Front matter minimal** :

```yaml
---
slug: mon-article
title: Titre de l'article
authors: [lionel]
tags: [epreuves]   # ou genese, fondations, aujourdhui, ou une nouvelle étiquette ajoutée dans blog/tags.yml
---
```

- Une ligne `{/* truncate */}` après un court résumé, pour que la liste du blog n'affiche que l'accroche.

Les tags ne servent pas juste à trier : ils marquent la place de l'article dans le récit du projet — **genèse** (les intentions de départ), **fondations** (les choix d'architecture qui posent le cadre), **épreuves** (bugs, refontes, décisions qu'on assume ou qu'on regrette), **aujourd'hui** (bilans d'étape). La version condensée et mise en scène de ce fil se trouve sur la page [Histoire du projet](/docs/histoire).
