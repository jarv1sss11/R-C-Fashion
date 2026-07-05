# PROPOSAL_CHANGES.md

Tracks how the academic project proposal changes as Version 2 development proceeds. This is a living diff between the original proposal document and what's actually being built — for viva/defense purposes.

Status: **Template — chapter-level detail pending the actual proposal chapters.**
Last updated: 2026-07-02

> Note: the document supplied so far (`MAIN PROPOSAL NAMED (MACHANJE-169890-189735).docx`) is the *project roadmap/instructions* brief, not the chaptered academic proposal (Ch.1 Introduction, Ch.2 Literature Review, etc.) implied by the structure below. The chapter-by-chapter tables are scaffolded per the brief's required format and will be filled in once the original proposal chapters are available to diff against.

---

## Overarching change (applies across all chapters)

| Old | New | Reason |
|---|---|---|
| Version 1: general online clothing marketplace (previous Laravel implementation) | Version 2: intelligent fashion **recommendation** platform for Kenyan fashion discovery — marketplace is downstream of recommendations | Product pivot: the recommendation engine is now the thesis of the project, not a bolt-on feature. Complete restart, no code/schema/UI reused. |

---

## Chapter 1 — (Introduction / Problem Statement)

| Old | New | Reason |
|---|---|---|
| TBD | TBD | TBD |

## Chapter 2 — (Literature Review / Related Work)

| Old | New | Reason |
|---|---|---|
| TBD | TBD | TBD |

## Chapter 3 — (Methodology / System Design)

| Old | New | Reason |
|---|---|---|
| TBD | TBD | Will document: Laravel 12 stack choice, hybrid deterministic (no-external-AI) recommendation approach, MySQL schema direction — once those are formally decided per [PROJECT_DECISIONS.md](PROJECT_DECISIONS.md). |

## Chapter 4 — (Implementation)

| Old | New | Reason |
|---|---|---|
| TBD | TBD | TBD |

## Chapter 5 — (Results / Testing)

- Implementation screenshots: **required**, not yet captured — first candidates will be the three guest pages once Phase 3 is built and approved.
- Testing tables: **required**, not yet started — will follow Phase 10 (Testing, Documentation & Deployment).
- Updated modules list: to be populated per phase as each is approved and built.

## Chapter 6 — (Conclusion / Future Work)

- Updated conclusions: TBD
- Updated future work: TBD
- Updated recommendations: TBD

---

## How this document gets updated

Every time a phase in [MASTER_BLUEPRINT.md](MASTER_BLUEPRINT.md) §9 completes and is approved, the relevant chapter table above gets a new row (or the TBDs get replaced), plus a corresponding entry in [PROJECT_EVOLUTION.md](PROJECT_EVOLUTION.md).

## Future Improvements

- Once the official proposal document is supplied, restructure the chapter headings above to match its actual chapter titles/numbering rather than the generic placeholders currently used.
- Consider adding a "diff summary" table at the very top once there are enough chapter-level changes to need an at-a-glance view for the viva.
