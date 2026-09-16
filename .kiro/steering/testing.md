---
inclusion: always
---

# Testing strategy

PHPUnit `^13.3` — configuration `phpunit.dist.xml`, bootstrap `tests/bootstrap.php`.

```text
tests/
├── Unit/           mirrors src/ — Domain, Application, Infrastructure
│   ├── Domain/ValueObject/     invariants, edge cases, rejections
│   ├── Application/UseCase/    orchestration, stubbed ports
│   └── Infrastructure/         isolated adapters
└── Api/            end-to-end API Platform functional tests
```

Naming convention: `<ClassUnderTest>Test.php`, in the folder mirroring the class.

---

## Strict configuration — know it before writing a test

`phpunit.dist.xml` fails on **deprecation, notice and warning**:

```xml
failOnDeprecation="true"  failOnNotice="true"  failOnWarning="true"
```

A deprecation raised by production code **breaks the suite**. Never silence it: fix the
call, or raise the discussion in the PR.

---

## What to test, per layer

| Layer | What must be covered | Doubles |
| :--- | :--- | :--- |
| `Domain/ValueObject` | invariant holds, **and every rejection reason** | none |
| `Domain/Entity` | state transitions, business rules | none |
| `Application/UseCase` | orchestration, error propagation | stubbed ports |
| `Infrastructure` | technical translation (encryption, mapping, storage) | as close to real as possible |
| `Api` | HTTP contract: status code, body shape, headers | — |

**A Value Object without a rejection test is not tested.** Its whole point is to refuse
invalid values; checking only the nominal case proves nothing.

---

## Link with requirements

Every EARS acceptance criterion of a spec (`.kiro/specs/<feature>/requirements.md`)
maps to **at least one test**. The test cites the requirement it covers:

```php
/**
 * Requirement 3.2 — storage refused when no legal framework applies.
 */
public function testStoreIsRejectedWhenNoLegalFrameworkApplies(): void
```

This link is what tells an agent it is done: every requirement is covered and
`make test` is green.

---

## Test first

For any task coming from a `tasks.md`: **write the test before the implementation**.
The test first fails for the right reason, then passes. A test written afterwards
validates what the code does, not what it was supposed to do.
