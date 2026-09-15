---
inclusion: always
---

# Stratégie de tests

PHPUnit `^13.3` — configuration `phpunit.dist.xml`, bootstrap `tests/bootstrap.php`.

```text
tests/
├── Unit/           miroir de src/ — Domain, Application, Infrastructure
│   ├── Domain/ValueObject/     invariants, cas limites, rejets
│   ├── Application/UseCase/    orchestration, ports bouchonnés
│   └── Infrastructure/         adaptateurs isolés
└── Api/            tests fonctionnels API Platform bout en bout
```

Convention de nommage : `<ClasseSousTest>Test.php`, dans le dossier miroir de la classe.

---

## Configuration stricte — à connaître avant d'écrire un test

`phpunit.dist.xml` échoue sur **dépréciation, notice et warning** :

```xml
failOnDeprecation="true"  failOnNotice="true"  failOnWarning="true"
```

Une dépréciation émise par du code de production **casse la suite**. Ne jamais la faire
taire : corriger l'appel, ou ouvrir la discussion dans la PR.

---

## Ce qu'on teste, par couche

| Couche | Ce qui doit être couvert | Doubles |
| :--- | :--- | :--- |
| `Domain/ValueObject` | invariant respecté, **et chaque motif de rejet** | aucun |
| `Domain/Entity` | transitions d'état, règles métier | aucun |
| `Application/UseCase` | orchestration, propagation d'erreur | ports bouchonnés |
| `Infrastructure` | traduction technique (chiffrement, mapping, stockage) | au plus près du réel |
| `Api` | contrat HTTP : code de statut, forme du corps, en-têtes | — |

**Un Value Object sans test de rejet n'est pas testé.** Son intérêt est précisément de
refuser les valeurs invalides ; vérifier seulement le cas nominal ne prouve rien.

---

## Lien avec les exigences

Chaque critère d'acceptation EARS d'une spec (`.kiro/specs/<key>/requirements.md`)
correspond à **au moins un test**. Le test cite l'exigence couverte :

```php
/**
 * Exigence 3.2 — dépôt refusé si aucun cadre légal applicable.
 */
public function testStoreIsRejectedWhenNoLegalFrameworkApplies(): void
```

C'est ce lien qui permet à un agent de savoir quand il a fini : toutes les exigences
sont couvertes, `make test` est vert.

---

## Test d'abord

Pour toute tâche issue d'un `tasks.md` : **écrire le test avant l'implémentation**.
Le test échoue d'abord pour la bonne raison, puis passe. Un test écrit après coup
valide ce que le code fait, pas ce qu'il devait faire.
