# ADR 0003 : Découplage d'API Platform et de la Clean Architecture via State Processors / Providers

* **Statut** : Accepté
* **Date** : 2026-07-24
* **Auteurs** : Équipe Architecture
* **Projet** : Compliance Store

---

## Context (Contexte)

Par défaut, API Platform encourage l'annotation directe des Entités Doctrine (`#[ApiResource]` et `#[ORM\Column]`) dans le même fichier. 

Dans le cadre de **Compliance Store**, cette approche standard présente trois inconvénients majeurs :
1. **Violation du DDD & Clean Architecture** : La couche Domaine deviendrait couplée au framework HTTP (API Platform) et au système de persistance (Doctrine ORM).
2. **Contournement des règles de sécurité** : L'écriture directe en base de données par API Platform sauterait le pipeline de vérification de conformité, de masquage PII et de chiffrement (`CipherPort`).
3. **Rigidité des Contrats d'API** : Impossible de faire évoluer le modèle de réponse HTTP indépendamment de la structure interne des données.

---

## Decision (Décision retenue)

Nous décidons de séparer strictement la couche d'Exposition HTTP (API Platform) du Domaine métier :

1. **Entités Domaine Pures (`src/Domain/Entity/`)** :
   * Classes PHP pures sans aucun attribut `#[ApiResource]` ni annotation Doctrine.
   * Contiennent uniquement la logique métier, les invariants et le comportement.

2. **Ressources d'Exposition API (`src/Infrastructure/ApiPlatform/Resource/`)** :
   * Des DTOs légers portant les attributs `#[ApiResource]` pour définir les endpoints REST et la documentation OpenAPI/Swagger.

3. **Délégation aux Use Cases via State Processors & Providers** :
   * **Lectures (`GET`)** : Gérées par un `StateProviderInterface` (`src/Infrastructure/ApiPlatform/State/`) qui appelle le `ConsultDocumentUseCase`.
   * **Écritures (`POST`/`DELETE`)** : Gérées par un `StateProcessorInterface` qui transmet l'entrée au `StoreDocumentUseCase`.

---

## Architecture du Flux d'Exposition

```text
Requête HTTP (JSON/REST)
       │
       ▼
[DocumentResource DTO] (Infrastructure/ApiPlatform/Resource)
       │
       ▼
[DocumentStateProcessor / Provider] (Infrastructure/ApiPlatform/State)
       │
       ▼
[StoreDocumentUseCase / ConsultDocumentUseCase] (Application/UseCase)
       │
       ▼
[Domain Entity & Business Rules] (Domain)
       │
       ▼
[CipherPort & StorageGateway] (Infrastructure)
```

---

## Consequences (Conséquences)

### Positives
* **Domaine 100% Pur** : Aucune dépendance vers Symfony, API Platform ou Doctrine dans le domaine.
* **Garantie de Sécurité** : Impossible d'enregistrer un document sans passer par le Use Case (qui exécute la redaction PII et le chiffrement Libsodium).
* **Évolutivité de l'API** : Facilité de créer des versions d'API (v1, v2) avec des DTOs différents sans impacter la logique métier.

### Négatives
* Nécessite la création de DTOs et de classes `StateProcessor`/`StateProvider` au lieu d'utiliser le générateur automatique CRUD d'API Platform.
