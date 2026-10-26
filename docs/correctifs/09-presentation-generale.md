# Compliance Store expliqué à tout le monde

*Ce document s'adresse à toute personne qui veut comprendre ce que fait l'application sans lire une ligne de code : direction, métier, juridique, support, nouveaux arrivants. La version technique se trouve dans [10-documentation-technique.md](10-documentation-technique.md).*

---

## 1. Le problème que l'application résout

Beaucoup d'organisations doivent conserver des copies de documents personnels : une banque garde la pièce d'identité de ses clients, une assurance conserve des justificatifs, une plateforme vérifie l'identité de ses utilisateurs.

Ces documents posent trois problèmes :

1. **Ils sont sensibles.** Une fuite expose les personnes à l'usurpation d'identité.
2. **Ils sont réglementés.** Le RGPD impose de pouvoir les supprimer quand la personne le demande, et de savoir qui y a accédé.
3. **Ils doivent être fiables.** Il faut pouvoir prouver qu'un document n'a pas été modifié depuis son dépôt.

Compliance Store est un **coffre-fort numérique** conçu pour ces documents. Il ne s'utilise pas directement par une personne devant un écran : c'est un service que d'autres logiciels appellent (un back-office, un site, un outil de vérification).

---

## 2. L'image à retenir : la banque et ses coffres

Imaginez une banque qui propose des coffres individuels.

| Dans la banque | Dans Compliance Store |
|----------------|------------------------|
| Un client ouvre un dossier à son nom | On **enregistre un porteur** (la personne concernée) |
| Le client dépose une enveloppe scellée dans un coffre | On **dépose un document**, chiffré avant même d'arriver au coffre |
| La banque note l'empreinte du sceau | On enregistre l'**empreinte numérique** du document |
| Pour consulter, il faut une clé de coffre et la bonne habilitation | Il faut une **clé d'API** et le **rôle** suffisant |
| Si le sceau est brisé, la banque refuse de remettre l'enveloppe | Si le document a changé, l'application **refuse de le livrer** |
| Chaque passage au coffre est noté dans un registre | Chaque action est inscrite dans le **journal d'audit** |
| Le client peut demander la destruction de son dossier | La personne peut exercer son **droit à l'effacement** |

La différence avec une banque : ici, même l'employé qui range les enveloppes dans le coffre ne peut pas les lire. Le lieu de stockage ne reçoit jamais le document en clair.

---

## 3. Ce que l'application sait faire

### 3.1 Enregistrer une personne

Avant tout dépôt, on déclare la personne concernée, appelée **porteur** : son nom et sa date de naissance. L'application lui attribue un identifiant unique et la marque comme **active**.

### 3.2 Déposer un document

Un logiciel autorisé envoie un fichier (PDF, PNG ou JPEG, 10 Mo maximum) en indiquant à quel porteur il appartient. L'application :

1. vérifie que le porteur existe et est actif ;
2. vérifie que le fichier est bien du type annoncé en regardant son contenu, pas seulement son nom ;
3. calcule son **empreinte** (une signature mathématique unique du contenu) ;
4. le **chiffre** avec une clé créée pour ce seul document ;
5. met cette clé elle-même sous clé grâce à une **clé maître** que seule l'application possède ;
6. envoie le fichier chiffré au stockage et garde en base de données l'empreinte et la clé protégée.

Si l'enregistrement en base échoue après l'envoi au stockage, le fichier chiffré est supprimé pour ne pas laisser de trace orpheline.

### 3.3 Consulter un document

Avec le rôle adéquat, on peut :

- lire la **fiche** d'un document (à qui il appartient, son empreinte, sa date) ;
- **lister** les documents d'un porteur ;
- **télécharger** le contenu déchiffré.

Au téléchargement, l'application déchiffre le fichier puis recalcule son empreinte. Si elle ne correspond pas à celle enregistrée au dépôt, le document est considéré comme altéré et **n'est pas livré**. Chaque consultation est inscrite au journal d'audit avec le nom de celui qui l'a demandée.

### 3.4 Respecter le droit à l'effacement (RGPD, article 17)

Quand une personne demande la suppression de ses données, le processus se fait en deux temps, réservés aux administrateurs :

1. **Demande d'effacement** : le porteur passe au statut « effacement demandé ». Plus aucun document ne peut lui être ajouté.
2. **Anonymisation** : tous ses documents sont détruits (le fichier chiffré et sa clé), son nom est remplacé par « anonymized », sa date de naissance est effacée. Il ne reste que l'identifiant, le statut et les dates, pour pouvoir prouver que l'effacement a eu lieu.

L'ordre est strict : on ne peut pas anonymiser sans demande préalable, ni redemander l'effacement d'un porteur déjà traité.

### 3.5 Garder la trace de tout

Le **journal d'audit** enregistre, sans possibilité de modification ni de suppression :

- l'enregistrement d'un porteur ;
- le dépôt d'un document ;
- la consultation (téléchargement) d'un document ;
- la demande d'effacement ;
- l'anonymisation.

Chaque ligne indique l'action, l'objet concerné, le moment exact et **qui** a agi (le nom du logiciel client authentifié). Les administrateurs peuvent consulter ce journal, globalement ou pour une personne ou un document précis.

### 3.6 Deux modules complémentaires

- **Pièces d'identité** : on peut déclarer qu'un passeport ou une carte nationale existe (type, pays, numéro, date d'expiration). L'application refuse les doublons et les pièces déjà expirées.
- **Espaces de stockage** : on peut définir des emplacements logiques (code, nom) et les activer, désactiver ou archiver.

Ces deux modules sont **pour l'instant indépendants** du reste : une pièce d'identité n'est pas encore reliée à un porteur ni à un fichier, et un espace de stockage n'influence pas encore l'endroit où un document est écrit. Voir la section 6.

---

## 4. Qui a le droit de faire quoi

Aucune action n'est possible sans **clé d'API**, une longue chaîne secrète remise une seule fois à chaque logiciel client. L'application ne conserve qu'une empreinte de cette clé : même en lisant la base de données, on ne peut pas la retrouver. Une clé compromise se révoque immédiatement.

Chaque clé porte un **rôle** :

| Rôle | Ce qu'il permet | Pour qui, typiquement |
|------|-----------------|------------------------|
| **Lecteur** | Consulter les fiches des porteurs, des documents, des espaces de stockage | Un tableau de bord, un outil de reporting |
| **Opérateur** | Tout ce qui précède, plus enregistrer des porteurs et des pièces d'identité, déposer des documents, télécharger leur contenu | Le back-office métier, l'outil de vérification d'identité |
| **Administrateur** | Tout ce qui précède, plus gérer les espaces de stockage, traiter les demandes d'effacement, lire le journal d'audit | Le délégué à la protection des données, l'exploitation |

Un rôle supérieur inclut toujours les droits des rôles inférieurs.

Seules deux informations sont accessibles sans clé : l'état de santé du service et la description publique de l'interface.

---

## 5. Ce qui protège les documents, en langage simple

**Le chiffrement d'enveloppe.** Chaque document a sa propre clé, comme chaque enveloppe aurait son propre cadenas. Toutes ces petites clés sont elles-mêmes enfermées dans un coffre fermé par la clé maître. Voler le stockage ne sert à rien sans la base de données ; voler la base ne sert à rien sans la clé maître ; et la clé maître ne quitte jamais l'application.

**L'empreinte.** C'est l'équivalent d'une photo du sceau au moment du dépôt. Toute modification du fichier, même d'un seul caractère, donne une empreinte différente, et l'application le détecte.

**La destruction par la clé.** Lors de l'anonymisation, le fichier chiffré est supprimé du stockage et sa clé est effacée de la base. Même si une copie du fichier chiffré subsistait quelque part, elle serait illisible à jamais.

**Les limites de taille et de type.** Un fichier trop gros ou qui n'est pas réellement un PDF ou une image est refusé avant tout traitement, ce qui protège le service contre les abus.

**Le journal inviolable.** Le journal d'audit ne peut qu'être enrichi, jamais corrigé ou vidé par l'API.

---

## 6. Ce que l'application ne fait pas (encore)

Pour éviter tout malentendu, voici les limites actuelles, documentées dans les rapports d'audit :

- **Pas de durée de conservation automatique** : rien n'expire ni ne se supprime tout seul. L'effacement est toujours déclenché par un administrateur.
- **Les pièces d'identité ne sont reliées à rien** : on déclare qu'un passeport existe, sans dire à qui il appartient ni quel fichier le contient.
- **Les espaces de stockage sont décoratifs** : tous les documents vont au même endroit.
- **Les clés d'API n'expirent pas** : elles restent valables jusqu'à révocation manuelle.
- **Pas de limitation de débit** : un client autorisé peut appeler le service autant qu'il le souhaite.
- **Pas d'interface graphique** : seuls des logiciels dialoguent avec l'application.

Ces points sont listés comme décisions à prendre dans [07-audit.md](07-audit.md) (section 7.8) et [08-corrections-prioritaires.md](08-corrections-prioritaires.md) (section 8.8).

---

## 7. Le cycle de vie d'un document, du début à la fin

```text
  Porteur enregistré ──► Document déposé ──► Consultations ──► Demande d'effacement ──► Anonymisation
        (actif)          (chiffré, empreinte)   (vérifiées,          (plus aucun dépôt)     (documents détruits,
                                                 journalisées)                               fiche vidée)
```

À chaque étape, une ligne est ajoutée au journal d'audit avec l'identité du logiciel qui a agi.

---

## 8. En une phrase

Compliance Store reçoit des documents personnels, les rend illisibles pour quiconque n'est pas l'application elle-même, prouve qu'ils n'ont pas été modifiés, note qui les consulte, et sait les faire disparaître proprement quand la loi l'exige.
