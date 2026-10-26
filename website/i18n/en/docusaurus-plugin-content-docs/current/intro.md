---
sidebar_position: 1
---

# Introduction

**Compliance Store** is an open source API that acts as a vault for sensitive personal documents: identity cards, passports, proofs of address. It registers the data subject, encrypts every document before storing it, checks its integrity on every consultation, applies the GDPR right to erasure and keeps a trail of every action.

Nobody uses it directly from a screen: other software (a back-office, an identity verification tool, a batch job) calls it, each with its own API key and its own rights.

## What this site contains

This site is not the exhaustive technical documentation of the repository (that lives in `docs/` at the root of the code, alongside the ADRs). It's a communication space:

- [What the application does](./fonctionnalites.md): the features, the roles, the protections and the current limits, without jargon.
- [Architecture](./architecture.md): the three layers of the code and the dependency rule.
- [Project story](./histoire.md): the moments that matter, by arc rather than by date.
- [Changelog](/blog): everything, in chronological order.

## Learn more

- [Source code on GitHub](https://github.com/lionelkouame/compliance-store)
- [October 2026 working documents](https://github.com/Hiro-hunter-dev-job/tontonlionelAPI/tree/main/docs/correctifs): detailed reports, plain-language overview and full technical documentation (in French)
- [Architecture Decision Records](https://github.com/lionelkouame/compliance-store/tree/main/docs/architecture/adr)
