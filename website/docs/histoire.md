---
sidebar_position: 4
---

# Histoire du projet

Le [journal de bord](/blog) raconte tout, dans l'ordre chronologique. Cette page fait l'inverse : elle sélectionne les articles qui comptent et les range par arc plutôt que par date, pour donner une lecture d'ensemble à quelqu'un qui découvre le projet.

Elle est mise à jour à la main à chaque étape marquante : ce n'est pas un flux automatique, c'est un choix éditorial.

## Genèse

Pourquoi ce projet existe, et pourquoi ce site en particulier.

- [Pourquoi ce site](/blog/pourquoi-ce-site) : l'objectif du journal de bord et la convention pour les prochains articles.

## Fondations

Les choix d'architecture qui posent le cadre : Clean Architecture, Domain-Driven Design, la règle de dépendance vers l'intérieur, les value objects partout, le chiffrement d'enveloppe dès le premier jour. Voir la page [Architecture](./architecture.md) pour la vue d'ensemble et les treize ADR du dépôt pour le détail.

*Les articles sur les choix de structuration du domaine viendront ici.*

## Épreuves

Les bugs, les refontes, les décisions qu'on assume ou qu'on regrette. C'est l'arc le plus honnête : celui qui montre le travail plutôt que le résultat poli.

- [Une journée de corrections : de l'environnement cassé à l'API sécurisée](/blog/corrections-prioritaires) : un conteneur qui redémarre en boucle, une base de test vide, neuf erreurs PHPStan, puis un audit complet et cinq faiblesses de fond corrigées dans l'ordre de priorité.

## Aujourd'hui

Où en est le projet, ce qui a changé récemment, ce qui reste à faire.

- L'API exige désormais une clé d'API et un rôle pour chaque opération, limite la taille et le type des dépôts, applique l'effacement RGPD jusqu'à la destruction cryptographique, sert la lecture et le téléchargement des documents avec contrôle d'intégrité, et tient un journal d'audit. Le détail est dans l'article [Une journée de corrections](/blog/corrections-prioritaires) et sur la page [Ce que fait l'application](./fonctionnalites.md).
- Ce qui reste à décider (rotation des clés, limitation de débit, rétention du journal, rattachement des pièces d'identité à un porteur, rôle réel des espaces de stockage) est listé en fin de cette même page.

---

Tous les articles, dans l'ordre où ils ont été écrits, restent disponibles sur le [journal de bord](/blog), filtrable par arc via les tags [Genèse](/blog/tags/genese), [Épreuves](/blog/tags/epreuves) et [Aujourd'hui](/blog/tags/aujourdhui) ; la page du tag Fondations apparaîtra avec son premier article.
