# ADR 0016: Application Services for Logic Shared by Use Cases

* **Status**: Accepted
* **Date**: 2026-11-26
* **Author**: Lionel Kouamé
* **Project**: Compliance Store

---

## Context

`StoreDocumentUseCase` hashes the content, encrypts it, generates the document id, writes the
ciphertext to object storage and persists the `Document`, removing the stored content again if
the database write fails (review finding M5):

```php
$fileHash = FileHash::make($request->content);
$envelope = $this->cipher->encrypt($request->content);
$documentId = DocumentId::make($this->idGenerator->generate());
// ... Document::create(), storage->store(), documents->add() + compensation
```

Other use cases will need the same sequence (bulk archive import announced in ADR 0014, identity
documents with an attached scan...). Today `src/Application` only holds `UseCase/`, so there is
no place for logic shared by several use cases.

Options considered:

* **A use case calling another use case**: rejected. A use case is an entry point with its own
  request and response; chaining them couples entry points and mixes their contracts.
* **A Domain service**: rejected. The sequence holds no business rule; it orchestrates ports
  (cipher, id generator, storage, repository), which is the Application layer's job.
* **Copying the code**: rejected. The storage/database compensation is the most delicate part
  and must exist once.

## Decision

### 1. Logic shared by several use cases lives in an Application service

```text
src/Application/
├─ UseCase/<Feature>/<Action>/   # entry points (unchanged)
└─ Service/<Feature>/            # logic shared by several use cases
   └─ Document/DocumentVault.php
```

* Grouped **by feature** (`Service/Document/`), like `UseCase/`. No flat `Service/` folder, for
  the same reason ADR 0015 removed `Infrastructure/Service`: it turns into a catch-all.
* Named **after what it does** (`DocumentVault`, `DocumentArchiver`...), never `XxxService`,
  `XxxHelper` or `XxxManager`.
* A `final readonly` class **without an interface**: it has a single implementation and only
  depends on Domain ports, so unit tests pass it fakes of those ports.
* An Application service is created only once a **second** use case needing the logic is
  identified; logic with a single known consumer stays in its use case.

### 2. The whole sequence is extracted, compensation included

```php
final readonly class DocumentVault
{
    public function __construct(
        private CipherGatewayInterface $cipher,
        private StorageGatewayInterface $storage,
        private DocumentRepositoryInterface $documents,
        private GeneratorInterface $idGenerator,
    ) {}

    /** Encrypts, stores and persists a document; removes the stored content if persistence fails. */
    public function store(HolderId $holderId, string $content): Document
    {
        $fileHash = FileHash::make($content);
        $envelope = $this->cipher->encrypt($content);
        $document = Document::create(
            id: DocumentId::make($this->idGenerator->generate()),
            holderId: $holderId,
            fileHash: $fileHash,
            wrappedDataKey: $envelope->wrappedDataKey,
        );

        $this->storage->store($document->id(), $envelope->payload->value());
        try {
            $this->documents->add($document);
        } catch (\Throwable $failure) {
            $this->removeStoredContent($document->id());
            throw $failure;
        }

        return $document;
    }
}
```

`StoreDocumentUseCase` is reduced to: resolve the holder, call `DocumentVault::store()`, build
the response. Holder resolution stays in the use case: it is input validation specific to that
entry point.

### 3. Only use cases call Application services

Presentation (API Platform processors and providers) keeps calling use cases only. A Deptrac
rule can enforce it by splitting the Application layer into `UseCase` and `ApplicationService`
and forbidding `Infrastructure` from depending on `ApplicationService`.

## Consequences

### Positive
* The storage/database compensation is written and tested once.
* New document-producing use cases (archive import...) stay short and focused on their input.
* `src/Application` gets an explicit, feature-grouped place for shared orchestration.

### Negative
* One more indirection when reading `StoreDocumentUseCase`.
* The "second consumer" rule requires judgement: extracting too early adds a layer for nothing.

## Implementation

`DocumentVault` is introduced in `src/Application/Service/Document/` and used by
`StoreDocumentUseCase`, with no behaviour change on `POST /documents`. The archive import
announced in ADR 0014 is the identified second consumer.
