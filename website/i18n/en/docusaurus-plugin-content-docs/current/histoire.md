---
sidebar_position: 4
---

# Project story

The [changelog](/blog) tells everything, in chronological order. This page does the opposite: it selects the posts that matter and groups them by arc rather than by date, to give newcomers an overview reading path.

It's updated by hand at each meaningful milestone: it's not an automatic feed, it's an editorial choice.

## Genesis

Why this project exists, and why this site in particular.

- [Why this site](/blog/why-this-site): the goal of the changelog and the convention for future posts.

## Foundations

The architectural choices that set the frame: Clean Architecture, Domain-Driven Design, the dependency rule pointing inward, value objects everywhere, envelope encryption from day one. See the [Architecture](./architecture.md) page for the overview and the thirteen ADRs in the repository for the details.

*Posts about domain structuring choices will land here.*

## Trials

Bugs, rewrites, decisions we own or regret. The most honest arc: the one that shows the work rather than the polished result.

- [One day of fixes: from a broken environment to a secured API](/blog/priority-fixes): a container restarting in a loop, an empty test database, nine PHPStan errors, then a full audit and five structural weaknesses fixed in priority order.

## Today

Where the project stands, what changed recently, what's left to do.

- The API now requires an API key and a role for every operation, limits the size and type of uploads, applies GDPR erasure down to cryptographic destruction, serves document reads and downloads with an integrity check, and keeps an audit trail. Details are in the post [One day of fixes](/blog/priority-fixes) and on the page [What the application does](./fonctionnalites.md).
- What remains to decide (key rotation, rate limiting, audit retention, linking identity documents to a holder, a real role for storage spaces) is listed at the end of that same page.

---

Every post, in the order it was written, stays available on the [changelog](/blog), filterable by arc via the [Genesis](/blog/tags/genese), [Trials](/blog/tags/epreuves) and [Today](/blog/tags/aujourdhui) tags; the Foundations tag page will appear with its first post.
