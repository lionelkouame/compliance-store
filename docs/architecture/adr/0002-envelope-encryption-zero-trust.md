# ADR 0002 : Chiffrement Enveloppe et Zero Trust Storage

* **Statut** : Accepté
* **Date** : 2026-07-24
* **Auteurs** : Équipe Architecture & Sécurité
* **Projet** : Compliance Store

---

## Context (Contexte)

Les documents stockés par Compliance Store sont hautement confidentiels (CNI, passeports, fiches de paie). Le stockage technique (MinIO, AWS S3, Azure Blob) ne doit jamais recevoir ni conserver un document en clair (*Zero Trust Storage*).

De plus, si un document doit être masqué (redaction PII) ou analysé par OCR, cette opération doit se faire sur le binaire en clair **uniquement en mémoire RAM** avant d'être chiffrée.

---

## Decision (Décision retenue)

1. **Chiffrement systématique avant écriture (`CipherPort`)** :
   * Aucun octet n'est envoyé au `StorageGatewayInterface` sans avoir été au préalable chiffré par l'application via `sodium_crypto_secretbox` (ou KMS/Vault).

2. **Chiffrement Enveloppe (Envelope Encryption)** :
   * Chaque document est chiffré avec une clé de données unique (Data Key).
   * La Data Key est elle-même chiffrée par une clé maître (Master Key / KMS).

3. **Placement dans le Pipeline** :
   * Ingestion : `Réception` $\rightarrow$ `Vérification Conformité` $\rightarrow$ `Redaction / Masquage PII (mémoire)` $\rightarrow$ `Chiffrement (CipherPort)` $\rightarrow$ `Stockage (StorageGateway)`.
   * Consultation : `Lecture Stockage (Chiffré)` $\rightarrow$ `Déchiffrement (mémoire)` $\rightarrow$ `Redaction Dynamique (si accès partiel)` $\rightarrow$ `Restitution HTTP`.

---

## Consequences (Conséquences)

### Positives
* **Confidentialité totale** : Même en cas de fuite du bucket S3 ou des disques durs, les données restent totalement illisibles sans la clé master KMS.
* **Redaction préservée** : Permet d'appliquer le biffage réglementaire sur le document avant son chiffrement final.

### Négatives
* Gestion et rotation sécurisée des clés maîtres (KMS / variables d'environnement sécurisées).
