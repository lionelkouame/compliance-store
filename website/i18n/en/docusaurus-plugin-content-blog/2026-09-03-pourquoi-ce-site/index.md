---
slug: why-this-site
title: Why this site
authors: [lionel]
tags: [genese]
---

Compliance Store moves forward in small iterations: targeted refactors, generic fields replaced with value objects, architectural choices that need to be documented so they aren't forgotten. Until now, that story only lived in commit messages and the repository's ADRs.

This site has one simple goal: make that story readable for someone who hasn't followed the project day to day.

{/* truncate */}

## What you'll find here

- **Short, up-to-date documentation** (see the [Documentation](/docs/intro) section) that gives an overview of the project and its architecture, without replacing the detailed ADRs in the repository.
- A **changelog** (this section, the blog) that tells, as it happens, the story of refactors, technical decisions, and key moments: why we removed a given value object, why we changed a validation approach, and so on.

## What this site isn't

It's not the reference technical documentation: that continues to live in `docs/` at the root of the code repository, alongside the ADRs (Architecture Decision Records) that track every architectural decision in detail. This site gives a simplified view and tells the story around it.

## Upcoming posts

To stay consistent, future posts will follow this convention:

- **Folder**: `blog/YYYY-MM-DD-slug-in-kebab-case/index.md` (one folder per post, so images can live alongside it).
- **Minimal front matter**:

```yaml
---
slug: my-post
title: Post title
authors: [lionel]
tags: [epreuves]   # or genese, fondations, aujourdhui, or a new tag added in blog/tags.yml
---
```

- A `{/* truncate */}` line after a short summary, so the blog list only shows the teaser.

Tags aren't just for sorting: they mark a post's place in the project's story — **genesis** (the starting intentions), **foundations** (the architectural choices that set the frame), **trials** (bugs, rewrites, decisions we own or regret), **today** (milestone recaps). The condensed, curated version of that thread lives on the [Project story](/docs/histoire) page.
