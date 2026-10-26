---
slug: corrections-prioritaires
title: "Une journée de corrections : de l'environnement cassé à l'API sécurisée"
authors: [hiro]
tags: [epreuves, aujourdhui]
---

Le 24 octobre 2026, le projet a été repris sur une machine neuve. Le conteneur PHP redémarrait en boucle, la base de test était vide et PHPStan comptait neuf erreurs. En fin de journée, l'API exigeait une clé pour chaque appel, refusait les fichiers trop gros ou déguisés, savait effacer une personne jusqu'à la destruction de ses clés de chiffrement, servait le téléchargement des documents avec contrôle d'intégrité et tenait un journal d'audit. Voici comment.

{/* truncate */}

## Le matin : faire tourner ce qui existait

Trois obstacles avant d'écrire la moindre ligne de métier.

**Le conteneur PHP ne démarrait pas.** L'image FrankenPHP embarquait une version récente de Mercure qui n'acceptait plus deux directives du `Caddyfile.dev`. Correction du fichier, alignement sur le template amont, et le conteneur est resté debout.

**La base de test était vide.** `doctrine:schema:update` échouait à cause d'une incompatibilité entre Doctrine DBAL 4.4 et le bridge Symfony 8.1, qui exigeait DBAL 4.5. Une montée de version ciblée, 29 tests en erreur redevenus verts.

**PHPStan comptait neuf erreurs**, toutes autour de l'agrégat `Holder` et de `DocumentProcessor`. Les corriger a révélé que le porteur n'était pas entièrement persisté : la date de naissance manquait en base. Une migration plus tard, l'entité et sa table disaient la même chose.

## L'après-midi : un audit, puis cinq faiblesses de fond

Une fois l'outillage sain, relecture de chaque dossier du projet. Une soixantaine de corrections ponctuelles en sont sorties (secrets dans Compose, Caddyfile de production, types Doctrine, documentation). Mais surtout, cinq faiblesses structurelles, traitées dans l'ordre de leur gravité.

### 1. N'importe qui pouvait tout faire

Aucune authentification. Pour un service qui stocke des pièces d'identité, c'était la priorité absolue. Choix retenu, tracé dans l'ADR 0013 : des **clés d'API Bearer** dont seule l'empreinte SHA-256 est conservée, trois rôles hiérarchiques (`ROLE_READER`, `ROLE_OPERATOR`, `ROLE_ADMIN`), chaque opération déclarant le rôle qu'elle exige. Deux commandes console créent et révoquent les clés. Sans clé : 401. Rôle insuffisant : 403.

### 2. Un fichier de n'importe quelle taille et de n'importe quel type

Trois barrières désormais : Caddy refuse les corps de plus de 16 Mio avant que PHP ne les lise, PHP plafonne les envois, et l'application vérifie la taille configurée et le **type réel** du fichier en inspectant son contenu. Un texte renommé en `.png` est refusé avec une erreur 422 explicite.

### 3. Un droit à l'effacement qui ne s'appliquait pas vraiment

L'entité savait changer de statut, mais rien n'était persisté et les documents restaient en place. Désormais, deux opérations d'administration : la demande d'effacement, puis l'anonymisation qui supprime chaque fichier chiffré du stockage, efface sa clé en base, remplace le nom par `anonymized` et vide la date de naissance. C'est de la **destruction cryptographique** : même une copie résiduelle du fichier chiffré serait illisible.

### 4. Un fichier écrit avant la ligne en base

Si l'insertion échouait après l'envoi au stockage, un fichier orphelin subsistait. Le cas d'usage de dépôt compense maintenant l'échec en supprimant l'objet, puis relance l'erreur d'origine.

### 5. Une API qui écrivait mais ne lisait pas

On pouvait déposer un document, jamais le relire. Ajout de la lecture des métadonnées, de la liste par porteur et du **téléchargement déchiffré**, qui recalcule l'empreinte SHA-256 et refuse de livrer un contenu altéré. Et un **journal d'audit** en ajout seul, qui enregistre qui a enregistré, déposé, consulté, demandé l'effacement ou anonymisé quoi.

## Le soir : écrire pour les autres

Chaque chantier a sa documentation dans `docs/correctifs/` du dépôt de travail : huit rapports, un journal de bord horodaté, puis une [présentation pour tout public](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/09-presentation-generale.md) et une [documentation technique](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/10-documentation-technique.md). Le README principal a été refondu, et une liste de ce que chaque membre de l'équipe doit faire après avoir récupéré les changements (reconstruire l'image, migrer, créer sa clé) a été ajoutée.

## Les chiffres

Deptrac 0 violation, PHPStan 0 erreur au niveau maximal, 159 tests PHPUnit et 381 assertions, tous verts, avant chaque commit grâce au hook pre-commit.

## Ce qui reste à décider

Le travail a volontairement laissé ouvertes les décisions qui appartiennent au propriétaire du projet : expiration et rotation des clés d'API, rotation de la clé maître, limitation de débit, rétention et export du journal d'audit, rattachement des pièces d'identité à un porteur, rôle réel des espaces de stockage, durée de conservation des documents. Elles sont listées dans le rapport de [corrections prioritaires](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/08-corrections-prioritaires.md), section 8.8.
