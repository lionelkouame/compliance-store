# ADR 0007: Declarative URI Variables Contract in API Platform State Providers via PHPDoc Shapes

* **Status**: Accepted
* **Date**: 2026-08-11
* **Authors**: Architecture Team & Lead Tech
* **Project**: Compliance Store

---

## Context

In **Compliance Store**, HTTP GET endpoints exposed via API Platform (`src/Infrastructure/ApiPlatform/Resource/`) delegate read operations to custom State Providers (`src/Infrastructure/ApiPlatform/State/`).

The signature of `ApiPlatform\State\ProviderInterface::provide()` is generic:
```php
public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?object
```

Because `$uriVariables` is typed as a native generic `array` (`array<string, mixed>` to static analysis tools like PHPStan):
1. Passing an element directly to a strictly-typed Use Case (e.g. `GetJurisdictionByCodeUseCase::execute(string $code)`) under `declare(strict_types=1)` triggers PHPStan type-mismatch errors (`mixed` passed where `string` expected).
2. Previously, developers wrote defensive runtime checks and temporary variables (e.g. `$rawCode = $uriVariables['code'] ?? ''; $code = \is_string($rawCode) ? $rawCode : '';`).
3. This defensive pattern adds noise to Providers, hides the expected route contract, and duplicates checks already performed by Symfony Routing at HTTP entry.

---

## Decision

We decide that **all API Platform State Providers MUST specify their expected URI variables declaratively using PHPDoc Array Shapes**:

### 1. Declarative Shape Annotation
Every State Provider consuming URI variables MUST document the exact array shape on its `provide()` method using PHPDoc syntax with optional key syntax (`key?: type`) to remain compatible with the default value `= []`:
```php
/**
 * @param array{code?: string} $uriVariables
 */
public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?JurisdictionResource
```

For endpoints supporting multiple route variables (e.g. lookup by ID or Code):
```php
/**
 * @param array{id?: string, code?: string} $uriVariables
 */
```

### 2. Clean Access via Coalesce Null Fallback
By declaring the shape contract:
* PHPStan validates type safety across the entire provider.
* Default `= []` parameter initialization matches the optional shape `array{code?: string}`.
* Providers access `$uriVariables['code'] ?? ''` cleanly without intermediate defensive variables or runtime `is_string` type checks.

```php
/**
 * @param array{code?: string} $uriVariables
 */
public function provide(Operation $operation, array $uriVariables = [], array $context = []): ?JurisdictionResource
{
    $jurisdiction = $this->useCase->execute($uriVariables['code'] ?? '');

    return null !== $jurisdiction ? JurisdictionResource::fromEntity($jurisdiction) : null;
}
```

---

## Consequences

### Positive
* **Self-Documenting Route Contracts**: The expected URI parameters are immediately visible on the `provide()` method signature during code reviews.
* **Clean & Readable Providers**: Eliminates verbose type-checking boilerplate (`is_string`, temporary variables, fallback strings).
* **100% PHPStan Compliant**: Fully satisfies strict static analysis (`declare(strict_types=1);`) without suppressing or ignoring errors.

### Negative
* Requires developers to maintain the PHPDoc `@param array{...}` shape when adding or renaming URI parameters on a route.
