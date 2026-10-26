---
sidebar_position: 2
---

# Ce que fait l'application

Cette page explique l'application à toute personne qui veut la comprendre sans lire le code. La version détaillée, avec le modèle de données et la référence de l'API, se trouve dans la [documentation technique](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/10-documentation-technique.md) du dépôt.

## Le problème résolu

Beaucoup d'organisations doivent conserver des copies de documents personnels. Ces documents sont **sensibles** (une fuite expose à l'usurpation d'identité), **réglementés** (le RGPD impose de pouvoir les supprimer et de savoir qui y accède) et doivent rester **fiables** (il faut prouver qu'ils n'ont pas été modifiés).

Compliance Store est un coffre-fort numérique conçu pour ces trois contraintes.

## L'image à retenir

| Dans une banque | Dans Compliance Store |
|-----------------|------------------------|
| Un client ouvre un dossier à son nom | On **enregistre un porteur** (la personne concernée) |
| Il dépose une enveloppe scellée dans un coffre | On **dépose un document**, chiffré avant d'arriver au stockage |
| La banque note l'empreinte du sceau | On enregistre l'**empreinte numérique** du document |
| Il faut une clé et la bonne habilitation pour consulter | Il faut une **clé d'API** et le **rôle** suffisant |
| Sceau brisé : la banque refuse de remettre l'enveloppe | Document altéré : l'application **refuse de le livrer** |
| Chaque passage au coffre est noté dans un registre | Chaque action est inscrite au **journal d'audit** |
| Le client peut faire détruire son dossier | La personne exerce son **droit à l'effacement** |

La différence avec une banque : ici, même celui qui range les enveloppes ne peut pas les lire. Le stockage ne reçoit jamais un document en clair.

## Les fonctions, étape par étape

### Enregistrer une personne

On déclare le **porteur** avec son nom et sa date de naissance. Il reçoit un identifiant unique et le statut *actif*.

### Déposer un document

Un logiciel autorisé envoie un fichier (PDF, PNG ou JPEG, 10 Mo au plus) en indiquant le porteur. L'application vérifie que le porteur est actif, contrôle le type réel du fichier en regardant son contenu, calcule son empreinte, le chiffre avec une clé propre à ce document, protège cette clé avec une clé maître, puis envoie le fichier chiffré au stockage. Si l'enregistrement en base échoue ensuite, le fichier chiffré est supprimé pour ne rien laisser d'orphelin.

### Consulter un document

Avec le rôle adéquat, on lit la fiche d'un document, on liste ceux d'un porteur, ou on télécharge le contenu déchiffré. Au téléchargement, l'empreinte est recalculée : si elle diffère de celle du dépôt, le document n'est **pas livré**. Chaque téléchargement est journalisé avec le nom du demandeur.

### Effacer (RGPD, article 17)

Deux étapes réservées aux administrateurs :

1. **Demande d'effacement** : le porteur ne peut plus recevoir de document.
2. **Anonymisation** : ses documents sont détruits (fichiers et clés), son nom devient `anonymized`, sa date de naissance est effacée. Il ne reste que l'identifiant, le statut et les dates, pour prouver que l'effacement a eu lieu.

### Tout tracer

Le journal d'audit enregistre, sans modification ni suppression possible : l'enregistrement d'un porteur, le dépôt, la consultation, la demande d'effacement, l'anonymisation. Chaque ligne porte l'action, l'objet, l'instant et **qui** a agi.

### Deux modules annexes

- **Pièces d'identité** : déclaration d'un passeport ou d'une carte nationale (type, pays, numéro, expiration), sans doublon ni pièce expirée.
- **Espaces de stockage** : emplacements logiques à activer, désactiver ou archiver.

Ces deux modules sont pour l'instant **indépendants** du reste (voir les limites ci-dessous).

## Qui a le droit de faire quoi

Rien n'est possible sans **clé d'API**. Elle est remise une seule fois, et l'application n'en garde qu'une empreinte : même en lisant la base, on ne peut pas la retrouver. Une clé compromise se révoque immédiatement.

| Rôle | Permet | Typiquement |
|------|--------|-------------|
| **Lecteur** | Consulter porteurs, fiches de documents, espaces de stockage | Tableau de bord, reporting |
| **Opérateur** | Tout ce qui précède, plus enregistrer porteurs et pièces, déposer et télécharger des documents | Back-office métier |
| **Administrateur** | Tout ce qui précède, plus espaces de stockage, effacement RGPD, journal d'audit | Délégué à la protection des données, exploitation |

Seuls l'état de santé du service et la description publique de l'interface sont accessibles sans clé.

## Ce qui protège les documents

- **Chiffrement d'enveloppe** : une clé par document, toutes ces clés enfermées par une clé maître qui ne quitte jamais l'application. Voler le stockage ne sert à rien sans la base ; voler la base ne sert à rien sans la clé maître.
- **Empreinte** : une photo du sceau au moment du dépôt. Un seul caractère modifié et l'application le détecte.
- **Destruction par la clé** : à l'anonymisation, fichier et clé disparaissent. Une copie résiduelle serait illisible à jamais.
- **Limites de taille et de type** : un fichier trop gros ou déguisé est refusé avant tout traitement.
- **Journal en ajout seul** : il ne peut qu'être enrichi.

## Ce que l'application ne fait pas encore

- Pas de durée de conservation automatique : l'effacement est toujours déclenché par un administrateur.
- Les pièces d'identité ne sont reliées ni à un porteur ni à un fichier.
- Les espaces de stockage n'influencent pas encore l'endroit où un document est écrit.
- Les clés d'API n'expirent pas.
- Pas de limitation de débit.
- Pas d'interface graphique.

Ces points sont listés comme décisions à prendre dans les rapports [d'audit](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/07-audit.md) et de [corrections prioritaires](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/blob/main/docs/correctifs/08-corrections-prioritaires.md).

## En une phrase

Compliance Store reçoit des documents personnels, les rend illisibles pour quiconque n'est pas l'application elle-même, prouve qu'ils n'ont pas été modifiés, note qui les consulte, et sait les faire disparaître proprement quand la loi l'exige.
