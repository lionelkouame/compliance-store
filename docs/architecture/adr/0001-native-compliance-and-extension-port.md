# ADR 0001 : Socle réglementaire natif et Port d'extension Provider

* **Statut** : Accepté
* **Date** : 2026-07-24
* **Auteurs** : Équipe Architecture & Conformité
* **Projet** : Compliance Store

---

## Context (Contexte)

Dans les projets gérant des documents sensibles (CNI, passeports, justificatifs), la conformité réglementaire (RGPD, durées de rétention, masquage de données PII) est souvent dispersée ou déléguée aveuglément à des services externes (ex: Onfido, Veriff) ou des buckets S3 basiques.

Cette approche présente deux risques majeurs :
1. **Domaine anémique** : Compliance Store deviendrait un simple proxy passe-plat sans intelligence métier propre.
2. **Lock-in Fournisseur** : Impossible de changer de prestataire de vérification sans réécrire l'application.

---

## Decision (Décision retenue)

Nous décidons d'adopter une **stratégie de composition additive** basée sur la Clean Architecture :

1. **Socle Réglementaire Natif (Dans le Domaine)** :
   * Le domaine métier porte un **socle de conformité natif** entièrement configurable par l'équipe de conformité (sans déploiement de code).
   * Ce socle garantit les invariants légaux durs (durée maximale de rétention, obligation de chiffrement, masquage PII par défaut).

2. **Port d'Extension Provider (`ExternalComplianceGatewayInterface`)** :
   * Le domaine expose un port d'extension permettant de brancher des providers externes optionnels (ex: Onfido, Veriff, KYC externes).

3. **Règle d'Additivité ("Fail-Closed")** :
   * Un document doit valider **à la fois** le socle natif **ET** les règles du provider externe.
   * Le provider externe ne peut **jamais** affaiblir la sécurité ou les règles du socle natif, il ne peut que les renforcer.

---

## Heuristique de classement d'une règle

| Origine du changement | Emplacement de la règle | Exemple |
| :--- | :--- | :--- |
| Évolution légale / RGPD / Réglementation | **Domaine (Socle Natif)** | Réduction de la durée de conservation d'un passeport de 5 à 3 ans. |
| Changement de partenaire / Fournisseur technique | **Infrastructure (Provider)** | Vérification supplémentaire du score de fraude d'Onfido. |

---

## Consequences (Conséquences)

### Positives
* **Autonomie de l'équipe conformité** : Possibilité de faire évoluer le socle natif de manière dynamique.
* **Sécurité renforcée** : Les règles métier dures sont protégées contre les défaillances ou configurations laxistes des providers tiers.
* **Indépendance technique** : Changement de provider externe sans impact sur le domaine.

### Négatives / Défis
* Nécessite le développement d'un moteur de règles déclaratif (ex: basé sur `Symfony ExpressionLanguage`) dans la couche Domaine.
