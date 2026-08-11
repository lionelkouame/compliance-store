# ADR 0006: Strict Non-Nullable Value Objects and Domain-Level Optionality Handling

* **Status**: Accepted
* **Date**: 2026-08-11
* **Authors**: Architecture Team & Lead Tech
* **Project**: Compliance Store

---

## Context

In **Compliance Store**, several Domain Entities contain attributes that are optional depending on the business context. For example, a `Jurisdiction` requires a `JurisdictionRegion`, but its `JurisdictionCountry` and `JurisdictionSubRegion` attributes are optional (e.g. `null` when a jurisdiction applies to an entire geographical region).

When converting raw scalar inputs from Application commands (e.g. `CreateJurisdictionCommand::$country`) into Domain Entities (`Jurisdiction`), developers must decide where and how optionality (`null` values) should be handled:

1. **Option A (VO-level nullability)**: Allowing Value Objects to accept `null` internally (e.g. `public ?string $value`) or adding static factory methods on VOs (e.g. `JurisdictionCountry::fromNullable(?string $val)`).
2. **Option B (Strict VOs + Entity-level nullability)**: Keeping Value Objects strictly non-nullable (enforcing single-purpose domain invariants), modeling optionality on the Entity signature (`?JurisdictionCountry`), and delegating conditional VO creation to the Use Case.

---

## Decision

We decide to adopt **Option B: Strict Non-Nullable Value Objects with Entity-level Nullability and Use Case Conditional Instantiation**.

### 1. Value Objects Are Strict and Non-Nullable
* A Value Object (`src/Domain/ValueObject/`) represents a **100% valid, non-null domain invariant**.
* Value Objects MUST NOT accept `null` in their constructors, nor carry nullable internal properties (`public string $value`, NEVER `public ?string $value`).
* Value Objects do not handle "absence of data" logic. If an instance of `JurisdictionCountry` exists, it is guaranteed to hold a valid ISO 3166-1 alpha-3 code.

### 2. Optionality Belongs to the Entity / Aggregate
* The concept of optionality (absence of an attribute) is a domain rule specific to the **Entity**, not to the Value Object itself.
* Entities model optional attributes using native PHP nullable typehints (`private ?JurisdictionCountry $country`).
* The domain meaning of `null` is documented on the Entity or VO (e.g. `"Null at the Jurisdiction level means whole region"`).

### 3. Conditional Instantiation Belongs to the Application Layer (Use Case)
* It is the responsibility of the Use Case (`src/Application/UseCase/`) to evaluate raw command inputs and conditionally instantiate Value Objects:

```php
$jurisdiction = Jurisdiction::create(
    id: $id,
    code: $code,
    label: new JurisdictionLabel($command->label),
    region: new JurisdictionRegion($command->region),
    country: null !== $command->country ? new JurisdictionCountry($command->country) : null,
    subRegion: null !== $command->subRegion ? new JurisdictionSubRegion($command->subRegion) : null,
    applicableFrameworks: ApplicableFrameworks::fromStrings(...$command->applicableFrameworks),
    active: $command->active,
);
```

---

## Consequences

### Positive
* **Guaranteed Invariant Safety**: Any instantiated Value Object is guaranteed to be valid and non-null. Code receiving a `JurisdictionCountry` never needs to perform null-checks on the VO's internal value.
* **Domain Model Reusability**: The same Value Object (e.g. `JurisdictionCountry`) can be reused across different Entities where it might be optional in one (`Jurisdiction`) and mandatory in another (`CompanyAddress`).
* **Explicit Domain Contracts**: Native PHP 8 nullable signatures (`?JurisdictionCountry`) explicitly declare optionality in Entity methods and constructors.

### Negative
* Requires explicit ternary checks (`null !== $command->property ? new ValueObject(...) : null`) in Use Cases when instantiating optional attributes.
