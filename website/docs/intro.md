---
sidebar_position: 1
---

# Introduction

**Compliance Store** est une API open source qui sert de coffre-fort pour des documents personnels sensibles : pièces d'identité, passeports, justificatifs. Elle enregistre la personne concernée, chiffre chaque document avant de le stocker, vérifie son intégrité à chaque consultation, applique le droit à l'effacement du RGPD et garde une trace de toutes les actions.

Elle ne s'utilise pas directement devant un écran : ce sont d'autres logiciels (un back-office, un outil de vérification d'identité, un batch) qui l'appellent, chacun avec sa propre clé d'API et ses propres droits.

## Ce que ce site contient

Ce site n'est pas la documentation technique exhaustive du dépôt (celle-ci vit dans `docs/` à la racine du code, aux côtés des ADR). C'est un espace de communication :

- [Ce que fait l'application](./fonctionnalites.md) : les fonctions, les rôles, les protections et les limites actuelles, sans jargon.
- [Architecture](./architecture.md) : les trois couches du code et la règle de dépendance.
- [Histoire du projet](./histoire.md) : les moments qui comptent, par arc plutôt que par date.
- [Journal de bord](/blog) : tout, dans l'ordre chronologique.

## Pour aller plus loin

- [Code source sur GitHub](https://github.com/lionelkouame/compliance-store)
- [Documents de travail d'octobre 2026](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/tree/main/docs/correctifs) : rapports détaillés, présentation générale et documentation technique complète
- [Décisions d'architecture (ADR)](https://github.com/lionelkouame/compliance-store/tree/main/docs/architecture/adr)
