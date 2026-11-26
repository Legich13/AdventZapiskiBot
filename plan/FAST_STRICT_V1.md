# FAST_STRICT/1 — immutable planning protocol

Status: **normative and immutable after merge**.

This is the complete closed-world contract for tracked Jobgram implementation
plans. `MUST`, `MUST NOT`, `SHOULD` and `MAY` are normative. The operator guide
in `plan/README.md` explains daily use but cannot change this protocol.

## 1. Authority and immutability

- Protocol ID is exactly `FAST_STRICT/1`.
- Protocol path is exactly `plan/FAST_STRICT_V1.md`.
- Canonical plan path is exactly `plan/<feature-slug>-fast-tdd/`.
- Root `ARCHITECTURE.md` is the only architecture authority. A plan references
  stable `ARCH-*` IDs and never defines an exception.
- `PLAN.json` is the immutable contract of one plan after Stage `000`.
- `STATUS.json` is the mutable execution ledger owned by the orchestrator.
- Completed plans remain tracked historical evidence, not runtime or product
  specifications.
- `workPlan/` is ignored historical local material and is not a source or
  automatic migration input.
- After this protocol is merged it MUST NOT be edited. Any semantic change uses
  a new file and ID such as `FAST_STRICT/2`; existing plans remain on version 1.

Authority order:

1. `ARCHITECTURE.md`;
2. `plan/FAST_STRICT_V1.md`;
3. frozen plan-local `PLAN.json`;
4. evolving plan-local `STATUS.json` and its hashed evidence;
5. plan-local Markdown projections.

## 2. Canonical directory and closed-world files

A plan directory contains only the following top-level entries:

```text
plan/<feature-slug>-fast-tdd/
├── README.md
├── PLAN.json
├── STATUS.json
├── TASKS.md
├── ARCHITECTURE_CONFORMANCE.md
├── DECISIONS.md
├── TRACEABILITY.md
├── OWNERSHIP.md                         # when the plan uses 2–3 parallel writers
├── evidence/
│   ├── command-log.md
│   ├── baseline.sha256                  # from Stage 001
│   ├── final.sha256                     # from Stage 006
│   └── EVID-<TOKEN>.md                  # only after that evidence exists
└── reviews/
    ├── round-<n>-security-privacy-data.md
    ├── round-<n>-architecture-api-ponytail.md
    └── round-<n>-spec-tdd-ux-a11y.md
```

`reviews/` and its flat `round-<n>-*.md` files exist only after review evidence
is produced.
Unknown top-level entries, unknown evidence/review files, editor backups, raw
logs, patches and helper scripts are invalid.

Filesystem rules:

- Names are ASCII and case-sensitive; case-colliding entries are invalid.
- Only regular files/directories are allowed. Symlinks, sockets, devices and
  FIFOs are invalid.
- Repository paths are slash-separated, relative, have no empty, `.` or `..`
  segment, never escape repository root and never resolve through a symlink.
- Exact paths are required. Globs and directory leases are invalid.
- Each JSON file is at most 1,048,576 bytes, UTF-8 without BOM, parses through
  native PHP JSON decoding as one object and accepts no unknown key at any
  level described below. IDs and values declared unique by this protocol may
  not repeat.
- Security policy forbids secrets, tokens, cookies, credentials, private keys,
  personal data and unredacted raw command output. Authors and the
  `REV-SECURITY-PRIVACY-DATA` reviewer enforce content safety; the validator
  enforces the closed path/type/hash and redacted-evidence contract.

## 3. IDs, timestamps, paths and ordering

Normative formats:

| Value | Format |
|---|---|
| slug | `^[a-z0-9]+(?:-[a-z0-9]+)*$` |
| plan ID | `^[a-z0-9]+(?:-[a-z0-9]+)*-fast-tdd$` |
| requirement | `^REQ-[0-9]{3}$` |
| acceptance | `^AC-[0-9]{3}$` |
| task | `^TASK-[0-9]{3}$` |
| check | `^CHECK-[0-9]{3}$` |
| gate | `^GATE-[A-Z0-9]+(?:-[A-Z0-9]+)*$` |
| evidence | `^EVID-[A-Z0-9]+(?:-[A-Z0-9]+)*$` |
| blocker | `^BLOCK-[0-9]{3}$` |
| architecture rule | `^ARCH-[A-Z0-9]+(?:-[A-Z0-9]+)*-[0-9]{3}$` |
| SHA-256 | `^[a-f0-9]{64}$` |
| stage | `000` through `007` |
| timestamp | RFC 3339 UTC, for example `2026-08-20T10:30:00Z` |

IDs are unique in their collection. ID/reference arrays are sorted by ID; path
arrays and manifest lines are bytewise sorted by path. Skill arrays preserve
declared application order. Timestamps describe observations, not validity, and
MUST NOT be rewritten merely to make a diff.

The directory basename and `planId` MUST both equal
`PLAN.json.slug + "-fast-tdd"`. Revision 1 uses
`plan/<feature-slug>-fast-tdd/`; revision 2 uses
`plan/<feature-slug>-v2-fast-tdd/`; revision N uses
`plan/<feature-slug>-vN-fast-tdd/`. Consequently PLAN `slug` is the directory
slug (`<feature-slug>` at revision 1 and `<feature-slug>-vN` later).
`revision` starts at `1` and increases by exactly one when a frozen plan is
superseded; `supersedes` names the immediately prior canonical planId.

## 4. `PLAN.json` top-level closed contract

The object contains exactly these keys, in any JSON member order:

| Key | Type |
|---|---|
| `protocolVersion` | string |
| `planId` | string |
| `slug` | string |
| `revision` | positive integer |
| `supersedes` | string or `null` |
| `title` | non-empty string |
| `goal` | non-empty observable string |
| `createdAt` | RFC 3339 UTC string |
| `architecture` | object from §5 |
| `protocol` | object from §5 |
| `scope` | object from §5 |
| `requirements` | non-empty array from §6 |
| `tasks` | non-empty array from §7 |
| `gates` | non-empty array from §8 |
| `reviewAxes` | exact array from §9 |

`protocolVersion` is exactly `FAST_STRICT/1`. `supersedes` is `null` for
revision 1 and the prior canonical planId (for example `foo-fast-tdd`) for a
replacement revision.
A frozen PLAN is never edited in place: material scope, skill, ownership,
architecture, requirement, check or gate changes produce a new revision and
make evidence from the superseded revision ineligible for reuse. Old evidence
remains immutable historical material.

## 5. PLAN identity objects

### `architecture`

Exactly:

```json
{
  "path": "ARCHITECTURE.md",
  "sha256": "<64 lowercase hex>",
  "ruleIds": ["ARCH-MOD-001", "ARCH-PLAN-001"]
}
```

- `path` is exactly `ARCHITECTURE.md`.
- `sha256` is the root architecture hash at Stage 000 freeze.
- `ruleIds` is sorted, unique, non-empty and every ID exists in that file.
- While STATUS is `draft`, `active` or `blocked`, the current root architecture
  bytes MUST match `sha256`; drift requires resolution and a new PLAN revision
  after freeze. For a `complete` or `superseded` plan, the validator checks the
  stored hash format, the matching conformance projection and every
  still-resolvable stable `ruleIds` entry, but does not demand that today's root
  architecture bytes retain the historical hash.
- There is deliberately no deviation/exception field. Architecture changes are
  accepted at root before freeze or the plan is blocked.

### `protocol`

Exactly:

```json
{
  "path": "plan/FAST_STRICT_V1.md",
  "sha256": "<64 lowercase hex>"
}
```

The hash is the immutable protocol used to create the plan.
Every STATUS state, including `complete` and `superseded`, requires this path
and hash to match the current bytes of `plan/FAST_STRICT_V1.md`; V1 semantic
edits are forbidden by §1.

### `scope`

Exactly:

```json
{
  "in": ["Explicitly authorized outcome."],
  "out": ["Explicit non-goal."]
}
```

Both arrays are non-empty, unique lists. `in` establishes authorization;
`out` prevents opportunistic expansion.

## 6. PLAN requirement contract

Each `requirements[]` element contains exactly:

| Key | Contract |
|---|---|
| `id` | unique `REQ-NNN` |
| `type` | `functional`, `non_functional` or `constraint` |
| `statement` | one non-empty testable statement |
| `acceptance` | non-empty array of closed acceptance objects |

Each acceptance object contains exactly:

```json
{
  "id": "AC-001",
  "statement": "An observable pass condition."
}
```

Acceptance IDs are globally unique across the plan. Each requirement and
acceptance is referenced by at least one task. No task invents a requirement or
acceptance absent from this collection.

## 7. PLAN task and skill contracts

Every `tasks[]` element contains exactly:

| Key | Type and rule |
|---|---|
| `id` | unique `TASK-NNN` |
| `stage` | `000`…`007` |
| `title` | non-empty string |
| `objective` | independently verifiable outcome |
| `agentRole` | non-empty exact role |
| `executionMode` | `read_only_subagent`, `orchestrator`, `writer` or `reviewer` |
| `dependsOn` | sorted unique task IDs |
| `ownedPaths` | sorted unique complete write leases |
| `createPaths` | sorted unique new-file leases |
| `deletePaths` | sorted unique deletion leases |
| `verificationMode` | `tdd`, `contract` or `inspection` |
| `checks` | non-empty array of closed check objects |
| `skills` | non-empty ordered array of closed skill objects |
| `requirementIds` | sorted unique existing requirement IDs |
| `acceptanceIds` | sorted unique existing acceptance IDs |

A check contains exactly:

```json
{
  "id": "CHECK-001",
  "kind": "tdd",
  "command": "make dev-test-filter filter=ExampleTest",
  "expected": "Non-zero because the requested behavior is absent."
}
```

`kind` is exactly `tdd`, `contract` or `inspection` and MUST equal the task's
`verificationMode`. `command` is a non-empty canonical Docker/Make command or,
only for `inspection`, a precise read-only inspection instruction. `expected`
states the observable result, including RED/GREEN intent where applicable.
Check IDs are globally unique.

A skill contains exactly:

```json
{
  "id": "ponytail:ponytail",
  "applyAt": ["analysis", "design", "verification"],
  "purpose": "Keep the solution minimal while satisfying the frozen contract.",
  "expectedOutcome": "No unnecessary layer, dependency or file is introduced."
}
```

- `applyAt` is a non-empty unique ordered subset of exactly `analysis`, `design`,
  `red`, `green`, `refactor`, `verification`, `review`, `handoff`.
- `id` is an exact available skill package name, not a category or alias.
- `purpose` says why this task needs the skill.
- `expectedOutcome` says how successful application is observed.
- Every task includes a `ponytail:ponytail` skill object.
- A required unavailable skill blocks the task. Silent fallback is invalid.

Path rules:

- `ownedPaths` is the task's complete write lease. `createPaths` and
  `deletePaths` are disjoint subsets of `ownedPaths`.
- `createPaths` MUST be missing in the baseline; `deletePaths` and other
  `ownedPaths` MUST exist in the baseline.
- A path is leased to only one task unless an explicit dependency serializes
  the tasks and both are executed by the orchestrator; parallel overlap is
  always invalid.
- Plan control/evidence/review paths are reserved to the orchestrator and never
  appear in task path arrays.
- The task dependency graph is acyclic. A dependency is in an earlier stage, or
  earlier by ID in the same stage.

TDD rules:

- Production behavior, executable configuration and migrations use
  `verificationMode=tdd`; machine-contract changes may use `contract`. Such a
  task starts at Stage 002, remains `in_progress` when RED closes that stage,
  uses the same check ID and command for GREEN at Stage 003, and completes after
  Stage 004 refactor/focused QA.
- `contract` is for machine contracts that cannot reasonably begin with a
  behavior test, and still requires a failing contract check before correction.
- `inspection` is limited to analysis, review and orchestrator verification that
  does not implement product behavior. Its check may be an exact executable
  gate command or a precise read-only inspection procedure; it MUST NOT replace
  `tdd` or `contract` for a production behavior or machine-contract change.
- RED fails for the intended missing behavior, not syntax, infrastructure or an
  unrelated test. GREEN is the smallest passing change. REFACTOR preserves it.

Stage 000 analysis is fixed:

- it may contain additional read-only subagent tasks, but includes at least
  three;
- every Stage 000 task has `executionMode=read_only_subagent`;
- the required roles `business_system`, `architecture_codebase` and `qa_risk`
  occur at least once and their STATUS executors are distinct;
- each has no write/create/delete path;
- each declares exact skills including `ponytail:ponytail`;
- each produces an analysis evidence item before Stage 000 can close. Baseline
  is deliberately not created until Stage 001 preflight.

Stage 001 preflight has zero or one PLAN task. When present, it is one
`executionMode=orchestrator`, `verificationMode=inspection` task with exactly
one check. Its command is the precise preflight/baseline inspection procedure,
not `plan:validate phase=001` itself: validation of a result cannot be the
evidence that produced that result. The single baseline evidence item binds to
that task/check and therefore serves as its check evidence. When the PLAN has no
Stage 001 task, baseline evidence is plan-wide with both references `null`.

## 8. PLAN gate contract

Each `gates[]` element contains exactly:

```json
{
  "id": "GATE-QUALITY",
  "stage": "005",
  "command": "make quality",
  "evidenceId": "EVID-GATE-QUALITY"
}
```

- `id` is unique.
- `stage` is `000`…`007` and identifies the stage this gate closes.
- `command` is one canonical non-interactive Docker/Make command.
- `evidenceId` is unique and names the STATUS evidence produced by that command.
- Every plan includes `GATE-PRECOMMIT` at Stage `005` with literal command
  `make pre-commit` and `GATE-QUALITY` at Stage `005` with literal command
  `make quality`; scope-specific gates may be added.
- Host PHP and Python are not valid gate commands.
- A stage with declared gates closes only through evidence with the exact
  command, exit code `0` and matching ID. Stage readiness also enforces its task,
  evidence and manifest invariants when no PLAN gate is declared.
- Human security policy permits only read-only or ordinary test/build gate
  commands. Authors and reviewers reject destructive, publishing or
  remote-write commands; the validator treats command text as an opaque exact
  string and does not implement a shell-semantics parser.

## 9. PLAN review-axis contract

`reviewAxes` contains exactly these three ID/title pairs, once each:

| ID | Exact title |
|---|---|
| `REV-SECURITY-PRIVACY-DATA` | `Security / Privacy / Data / PostgreSQL` |
| `REV-ARCHITECTURE-API-PONYTAIL` | `Architecture / API / Ponytail` |
| `REV-SPEC-TDD-UX-A11Y` | `Spec / TDD / UX / a11y` |

Each element contains exactly:

```json
{
  "id": "REV-ARCHITECTURE-API-PONYTAIL",
  "title": "Architecture / API / Ponytail",
  "skills": [
    {
      "id": "architect-review",
      "applyAt": ["review"],
      "purpose": "Verify architecture conformance.",
      "expectedOutcome": "Every applicable ARCH-* rule is satisfied."
    },
    {
      "id": "ponytail:ponytail",
      "applyAt": ["review"],
      "purpose": "Apply minimality while reviewing the complete snapshot.",
      "expectedOutcome": "The review adds no speculative requirement or machinery."
    },
    {
      "id": "ponytail:ponytail-review",
      "applyAt": ["review"],
      "purpose": "Audit the snapshot specifically for removable over-engineering.",
      "expectedOutcome": "Needless dependencies, layers, files and abstractions are reported."
    }
  ]
}
```

`title` is non-empty. `skills` uses the exact §7 skill-object contract and every
axis includes both `ponytail:ponytail` and `ponytail:ponytail-review` as distinct
exact skill IDs. Reviewers are independent from task writers and all axes
inspect the same frozen snapshot round/hash.

For the initial verdicts of every review round, all three reviewers work from
the frozen bundle without receiving another axis report, verdict or summary.
The orchestrator waits until all three initial verdicts are recorded before it
shares reports, assigns remediation or permits any snapshot writer to resume.

## 10. `STATUS.json` top-level closed contract

The object contains exactly:

| Key | Type |
|---|---|
| `protocolVersion` | exactly `FAST_STRICT/1` |
| `planId` | exact PLAN planId |
| `planRevision` | exact PLAN revision |
| `planSha256` | current frozen PLAN hash |
| `orchestratorId` | non-empty identity |
| `overallState` | `draft`, `active`, `blocked`, `complete`, `superseded` |
| `currentStage` | `000`…`007` |
| `supersededBy` | string or `null` |
| `updatedAt` | RFC 3339 UTC |
| `stages` | closed stage array |
| `tasks` | closed task-status array |
| `activeWriters` | closed writer array |
| `evidence` | closed evidence array |
| `gateResults` | closed gate-result array |
| `snapshot` | exact snapshot object |
| `reviews` | exact three review-status objects |
| `blockers` | closed blocker array |

Unknown keys are invalid, including convenience summaries or timestamps not
listed above.

## 11. STATUS nested contracts

### Stage status

`stages` has exactly eight elements in order `000`…`007`. Each contains exactly:

```json
{"id": "000", "state": "pending"}
```

`state` is `pending`, `in_progress`, `blocked` or `complete`.

### Task status

There is exactly one element per PLAN task and no extra item:

```json
{
  "id": "TASK-001",
  "state": "pending",
  "executorId": null,
  "usedSkills": [],
  "unavailableSkills": [],
  "skippedSkills": [],
  "evidenceIds": [],
  "handoffEvidenceId": null
}
```

- `state` is `pending`, `in_progress`, `blocked` or `complete`.
- `executorId` is nullable only while the task is pending.
  `handoffEvidenceId` is nullable until its Stage 007 handoff evidence is
  produced, including for work already completed in an earlier stage.
- `usedSkills` is an ordered list of exact skill IDs.
- `unavailableSkills` and `skippedSkills` contain only closed objects
  `{"id":"skill-id","reason":"non-empty reason"}`.
- `evidenceIds` is a sorted unique list of existing STATUS evidence IDs.

A task completes its assigned/cross-stage work only with an executor, every and
only PLAN skill ID in PLAN order, no unavailable/skipped skill and all required
check evidence. Stage 007 adds every task's handoff evidence ID; overall
completion requires all of them. Any required unavailable/skipped skill makes
the task and overall execution blocked; replacement by a similar skill is
invalid.

### Active writer

Each `activeWriters[]` element contains exactly:

```json
{"writerId": "writer-backend", "taskIds": ["TASK-004"]}
```

At most three distinct writers are active. Every referenced task is
`in_progress`, has `executionMode=writer`, has the same `executorId`, has
dependencies complete and has a write surface disjoint from every other active
writer. `OWNERSHIP.md` is required before this array reaches two entries and is
retained as historical ownership evidence after those writers finish.

`activeWriters` is empty through the Stage 001 exit because no product write may
start before the baseline is accepted. Stages 002–005 may contain up to three
valid writers; closing Stage 001 is a one-time precondition and MUST NOT be
reapplied by `phase=current` after execution enters Stage 002. Writers finish
and the array is empty before Stage 006 snapshot/review begins.

### Evidence

Each `evidence[]` element contains exactly:

```json
{
  "id": "EVID-RED-EXAMPLE",
  "stage": "002",
  "taskId": "TASK-005",
  "checkId": "CHECK-005",
  "kind": "red",
  "path": "plan/example-fast-tdd/evidence/EVID-RED-EXAMPLE.md",
  "sha256": "<64 lowercase hex>",
  "command": "make dev-test-filter filter=ExampleTest",
  "exitCode": 1,
  "observedAt": "2026-08-20T10:30:00Z"
}
```

`taskId` and `checkId` may be `null` for plan-wide evidence; all other keys are
non-null. `kind` is exactly one of `analysis`, `baseline`, `red`, `green`, `qa`,
`gate`, `snapshot`, `handoff`. `path` exists, is a repository-relative path
inside the canonical plan, names an allowed regular Markdown/manifest file and
matches `sha256`. `command` is the exact
PLAN check/gate command when referenced. RED uses a non-zero intended failure;
analysis/baseline/green/qa/snapshot/handoff use zero. Gate evidence uses zero
for `passed` and non-zero for `failed`. Evidence is immutable once referenced.

The single baseline evidence path is exactly
`plan/<feature-slug>-fast-tdd/evidence/baseline.sha256`. If PLAN contains the
single Stage 001 task defined in §7, baseline `taskId` and `checkId` MUST name
that task and its only check, and `command` MUST equal the check's inspection
procedure. This one record is both the plan-wide baseline proof and that task's
check evidence. If PLAN has no Stage 001 task, both references are `null` and
the command is the exact recorded baseline inspection procedure. No second
baseline or synthetic handoff is used to complete Stage 001.

Every Markdown evidence path is exactly
`plan/<feature-slug>-fast-tdd/evidence/<evidence-id>.md` with the same
case-sensitive ID. `command-log.md` and `final.sha256` are not
`evidence[].path` values; the final manifest is linked through `snapshot`.

### Gate result

There is exactly one entry per PLAN gate:

```json
{"id": "GATE-001", "state": "pending", "evidenceId": null}
```

`state` is `pending`, `passed` or `failed`. `evidenceId` is nullable while
pending; otherwise it references the gate's exact PLAN evidence ID. `passed`
requires exact command and exit code `0`; `failed` requires non-zero.

### Snapshot

`snapshot` contains exactly:

```json
{
  "state": "pending",
  "round": 0,
  "manifestPath": null,
  "manifestSha256": null,
  "bundleSha256": null
}
```

- `state` is `pending`, `frozen` or `invalidated`.
- `round` is `0` while pending and a positive monotonically increasing integer
  after the first freeze.
- `manifestPath`, `manifestSha256`, `bundleSha256` are nullable while pending;
  frozen requires repo-relative
  `plan/<feature-slug>-fast-tdd/evidence/final.sha256` and both hashes.
- `manifestSha256` is SHA-256 of that manifest's exact bytes;
  `bundleSha256` is computed only by §14.
- Invalidated retains the last round/hashes for audit but cannot be reviewed or
  completed. A new snapshot increments `round`.

### Review status

There is exactly one entry for each fixed review axis:

```json
{
  "axisId": "REV-SECURITY-PRIVACY-DATA",
  "reviewerId": null,
  "verdict": "pending",
  "round": 0,
  "snapshotSha256": null,
  "evidencePath": null,
  "evidenceSha256": null,
  "usedSkills": [],
  "unavailableSkills": [],
  "skippedSkills": [],
  "b0": 0,
  "b1": 0,
  "b2": 0
}
```

- `verdict` is `pending`, `go` or `fix_required`.
- Reviewer/snapshot/evidence fields are nullable only while pending.
- Non-pending `round` equals snapshot round and all axes use the same
  `snapshotSha256`, which equals `snapshot.bundleSha256`.
- Reviewer is not the orchestrator, task executor or another axis reviewer.
- `usedSkills` lists every and only axis skill ID in PLAN order;
  `unavailableSkills` and `skippedSkills` use the exact closed reason objects
  from task status. A non-pending review has no unavailable/skipped skill.
- `b0`, `b1`, `b2` are non-negative counts of open blocker, major and minor
  findings. `go` requires `b0=0` and `b1=0`. A non-zero `b2` is allowed only
  with rationale and owner in the hashed review; otherwise use `fix_required`.
- Evidence paths are exactly
  `plan/<feature-slug>-fast-tdd/reviews/round-<round>-security-privacy-data.md`,
  `plan/<feature-slug>-fast-tdd/reviews/round-<round>-architecture-api-ponytail.md`
  and
  `plan/<feature-slug>-fast-tdd/reviews/round-<round>-spec-tdd-ux-a11y.md` for
  their respective axes, and each matches its hash.

### Blocker

Each blocker contains exactly:

```json
{
  "id": "BLOCK-001",
  "taskId": "TASK-004",
  "reason": "Required skill is unavailable.",
  "openedAt": "2026-08-20T10:30:00Z"
}
```

`taskId` may be `null` for a plan-wide blocker. IDs are unique. Reasons are
redacted and actionable. STATUS has no implicit or closed blocker field:
resolution is evidenced by removing the blocker only when the underlying
condition is actually resolved.

## 12. STATUS consistency and lifecycle rules

- `planId`, revision and hash match PLAN; protocol versions match.
- Exactly one stage is `in_progress` when overall state is `active`; earlier
  stages are complete and later stages pending. `currentStage` names it.
- `draft` is allowed only at Stage 000 before its gate passes.
- `blocked` requires at least one blocker and a blocked stage/task; no new task
  begins while blocked.
- `complete` requires current Stage 007 and every stage/task/gate complete or
  passed, no active writer/blocker/unavailable/skipped skill, a frozen snapshot,
  three independent `go` reviews for that exact round/hash, zero B0/B1 and
  valid handoff evidence for every task. B2 may remain only with documented
  rationale and owner in review evidence.
- `superseded` requires `supersededBy`; a non-superseded state requires it null.
  The value is the exact next canonical revision ID derived from the current
  plan (`foo-fast-tdd` → `foo-v2-fast-tdd`, `foo-v2-fast-tdd` →
  `foo-v3-fast-tdd`). That package MUST exist in `plan/`, its PLAN revision is
  exactly current revision + 1 and its `supersedes` points back to the current
  planId. Conversely, every PLAN with non-null `supersedes` requires that
  immediate prior package to exist with `STATUS.supersededBy` pointing forward.
  Arbitrary labels, skipped revisions and one-way links are invalid for both a
  single-plan validation and `--all`.
- A task becomes in-progress only when all dependencies are complete.
- A stage closes when its phase-specific invariants and declared gates pass.
  A Stage 002 `tdd|contract` task remains `in_progress` after valid RED, crosses
  Stage 003 for same-check/same-command GREEN and completes after Stage 004
  refactor/focused QA. Inspection tasks complete inside their assigned stage.
- Any byte change to a frozen snapshot input sets snapshot `invalidated`, resets
  review verdicts to pending and returns execution to the owning stage.
- Orchestrator is the only writer of PLAN, STATUS, plan docs and evidence index.
- STATUS updates describe real completed work; pre-filling future evidence,
  skills, gates or reviews is invalid.

## 13. SHA manifests

`evidence/baseline.sha256` is required to close Stage 001.
`evidence/final.sha256` is required from Stage 006.

Each non-empty UTF-8 line is exactly one of:

```text
<64 lowercase hex><two ASCII spaces><repository-relative path>
MISSING<two ASCII spaces><repository-relative path>
```

No comments, blank lines, CRLF, duplicate paths or extra whitespace are allowed.
Lines are bytewise sorted by path. The path set equals the union of all task
`ownedPaths`; create/delete subsets do not add duplicate entries.

Baseline:

- `createPaths` are `MISSING`;
- every other path in the `ownedPaths` union has its actual hash.

Final:

- `deletePaths` are `MISSING`;
- every other path in the `ownedPaths` union has its actual hash.

Phase 001 compares current files with baseline. Phases 002–005 permit the exact
declared writes and do not require current files to match final. Phase 006 and
phase 007 before completion compare current files with final. A completed plan
validates stored manifest and bundle integrity but does not compare future
product-file bytes, so later legitimate plans do not corrupt historical
evidence.

## 14. Snapshot bundle

The frozen snapshot covers exactly the material reviewers inspect. Build a
canonical UTF-8 input with LF endings, no blank lines and exactly these record
shapes:

```text
PLAN <planSha256>
DOC <plan-relative Markdown path> <sha256>
MANIFEST <sha256-of-evidence/final.sha256>
EVIDENCE <evidence ID> <sha256>
```

Rules:

- exactly one PLAN line;
- DOC lines for README, TASKS, ARCHITECTURE_CONFORMANCE, DECISIONS,
  TRACEABILITY and OWNERSHIP only when required;
- exactly one MANIFEST line for the bytes of `evidence/final.sha256`; baseline
  is verified separately and is not part of this line;
- EVIDENCE lines for every immutable pre-review STATUS evidence item whose kind
  is `analysis`, `baseline`, `red`, `green`, `qa` or `gate`, sorted by evidence
  ID; `snapshot`, `handoff` and review evidence are excluded to avoid cycles;
- records are grouped in the shown order; DOC paths and EVIDENCE IDs are
  bytewise sorted;
- one ASCII space separates fields and the final line ends with LF;
- `snapshot.bundleSha256` is SHA-256 of these exact bytes;
- STATUS and review outputs are excluded to avoid self-reference;
- review evidence records the bundle hash via `snapshotSha256`.

No zip/tar, generated JSON manifest or `.files` sidecar is part of the protocol.

## 15. Human projections and command log

Required Markdown headings:

- `README.md`: `Goal`, `Scope`, `Non-goals`, `Operator Notes`;
- `TASKS.md`: task order, exact paths, checks and full skill objects in readable
  form;
- `ARCHITECTURE_CONFORMANCE.md`: root hash, rule IDs and literal
  `Deviations: none`;
- `DECISIONS.md`: accepted decisions and rejected alternatives;
- `TRACEABILITY.md`: REQ → AC → TASK → CHECK → GATE mapping;
- `OWNERSHIP.md`: active writer/task/path leases and orchestrator reservation;
- `evidence/command-log.md`: chronological redacted command/evidence index.

Human projections explain but cannot add scope, requirements, tasks, exceptions,
paths, skills or gates absent from PLAN. They are projections, not placeholders:

- `TASKS.md` names every PLAN task ID exactly once in PLAN order and, for each
  task, shows stage, role/mode, dependencies, owned/create/delete paths,
  requirement/acceptance references, every check ID with its exact command and
  expected result, and every complete skill object (`id`, `applyAt`, `purpose`,
  `expectedOutcome`). Ranges such as “TASK-001 through TASK-007” are invalid.
- `TRACEABILITY.md` contains explicit rows linking every acceptance criterion
  through an existing requirement, task and check to its declared gate or
  named stage-readiness gate. Every REQ, AC, TASK, CHECK and PLAN GATE ID appears
  in at least one truthful row; an ID range or prose claim is not traceability.
- `ARCHITECTURE_CONFORMANCE.md` records the frozen root hash and every PLAN
  architecture rule ID, followed by literal `Deviations: none`.
- `evidence/command-log.md` names every produced evidence ID exactly once in
  observation order with timestamp, command/procedure, exit code, path and hash.

Validation checks these durable headings, exact IDs, values and links, not prose
length or line counts.

Every `EVID-*.md` contains the exact evidence ID, stage, task/check or explicit
`none`, exact command/procedure, separate expected and observed results, numeric
exit code, redaction statement and limitations. Generic text such as “results
are recorded” is invalid. Raw output and secrets remain outside the repository;
only their redacted summary and immutable external/log hash may be cited.

## 16. Exact skills and Ponytail full mode

- Every task and review axis contains exact skill objects with ID, application
  moments, purpose and expected outcome.
- The orchestrator reads each selected skill's complete `SKILL.md` before its
  assignment. The executor applies skills at every declared `applyAt`.
- Every task and axis contains `ponytail:ponytail`, including Stage 000
  analyses. Every review axis additionally contains
  `ponytail:ponytail-review`. The first enforces the smallest solution satisfying
  frozen requirements; the second performs the explicit deletion-focused review.
  No speculative dependency, layer, interface, generator or duplicated doc is
  accepted.
- STATUS records actual exact IDs in `usedSkills`; unavailable/skipped lists
  always exist, including when empty.
- A required unavailable or skipped skill blocks execution. No alias, similar
  skill or undocumented manual fallback is allowed.
- After Stage 000 freeze, a newly discovered skill need requires a new PLAN
  revision, not a STATUS edit.
- Handoff reports used, unavailable and skipped skills explicitly.

## 17. Orchestration, parallelism and TDD

- One orchestrator owns control and evidence files.
- Stage 000 has at least the three fixed independent read-only analyses.
- At most three writers are active; path leases are pairwise disjoint.
- Writers edit only their exact lease and return evidence to the orchestrator.
- Read overlap is allowed; write overlap is serialized by dependencies.
- Reviewers are independent of orchestrator/writers and never modify snapshot.
- Until all three initial verdicts for a round exist, reviewers do not receive
  another axis report or verdict and remediation does not start.
- Production work follows RED → GREEN → REFACTOR:
  focused intended failure, smallest passing implementation, then simplification
  with behavior green.
- Docker/Make is the only runtime for plan checks and implementation gates.
  Host PHP and Python planning surfaces are prohibited.
- Commands never publish, mutate Git history or perform destructive cleanup
  without separate explicit user authorization.

## 18. Stages 000–007

| Stage | Entry and required work | Exit gate |
|---|---|---|
| `000` NORMALIZE/FREEZE | Normalize the request; read sources; run the three fixed independent analyses; close scope/questions; freeze PLAN with architecture/protocol hashes, REQ/AC, DAG, exact paths/checks/skills. | PLAN and human projections agree; ≥3 analysis evidence items; no unresolved question or plan-local architecture exception. |
| `001` PREFLIGHT/BASELINE | Validate frozen contract, tool/skill availability, ownership and exact path states; create `baseline.sha256` before any product write. When PLAN has its single Stage 001 task, baseline binds to and completes that task's only inspection check. | Baseline matches the complete write surface; writers remain inactive only through this exit; Stage 001 readiness passes. |
| `002` RED | Apply task skills; add focused failing test/contract inside the exact lease; run and hash the intended failure. | Every production change has non-zero RED evidence caused by missing requested behavior. |
| `003` GREEN | Implement the smallest solution inside exact leases and run the same command used for RED. | Same-command GREEN exits 0 without scope or architecture expansion. |
| `004` REFACTOR/FOCUSED QA | Refactor while green; run focused QA and capture Ponytail evidence that needless complexity was removed/avoided. | Focused behavior and affected checks pass; no required skill is unavailable/skipped. |
| `005` INTEGRATION/FULL GATES | Integrate writers; update durable code-adjacent/product/API docs and generated artifacts; run broad gates including `make quality` and `make pre-commit`. | Traceability is closed, generated drift is absent and every integration gate passes. |
| `006` SNAPSHOT/REVIEW | Finish writers, create `final.sha256` and canonical bundle, freeze a round, then run three fixed independent reviews without cross-report visibility. Remediation waits for all three initial verdicts; changed bytes require a higher round and all axes again. | One final round/hash has three independent `go` reviews, exact skills and B0=B1=0; any B2 has recorded rationale/owner. |
| `007` INTEGRITY/HANDOFF | Run final `phase=007` and current integrity; produce per-task handoff evidence and factual changed-path/check/skill/risk summary. | STATUS is engineering-complete: all stages/tasks/gates complete/passed, snapshot frozen, no writer/blocker/skill exception. |

Stages advance only in order and never skip. A blocker freezes advancement.
A review finding that changes implementation returns to the owning stage,
invalidates the snapshot and requires a higher snapshot round plus all reviews.

## 19. Validator public interface

Artisan contract:

```text
php artisan plan:validate {plan?} {--all} {--phase=current} {--root=}
```

Docker-first operator commands:

```bash
make dev-plan-check plan=plan/<feature-slug>-fast-tdd phase=000
make dev-plan-check plan=plan/<feature-slug>-fast-tdd phase=current
make dev-plan-check-all
```

Rules:

- `--phase` accepts exactly
  `current|000|001|002|003|004|005|006|007`.
- `current` applies the strictest gate implied by STATUS.
- For `draft`, `active` or `blocked`, `current` derives that gate from
  `currentStage` and verifies all prior-stage invariants too. For `complete`, it
  applies Stage 007 completion/historical-integrity rules. For `superseded`, it
  applies structural and stored-integrity rules without comparing current
  product bytes.
- Numeric phase checks readiness to close exactly that stage. It never mutates
  or rewinds STATUS.
- `--all` accepts only `--phase=current`, cannot be combined with `plan`, and
  validates every canonical tracked plan.
- Exactly one selector (`plan` or `--all`) is required.
- `--root` is the absolute read-only mounted repository root.
- Exit `0`: selected plans valid; `1`: integrity violation; `2`: CLI/root error.
- `--all` with zero canonical plans returns `0`.
- Validator never executes plan commands or writes files.

Diagnostics are deterministic and sorted by plan ID first, then code, relative
path, JSON pointer and message. The plan ID is derived from canonical path for
sorting; the service error shape remains code/path/pointer/message. Output is
exactly:

```text
ERROR <code> <relative-path><json-pointer>: <message>
SUMMARY plans=N errors=N
```

In CLI output, `<json-pointer>` means a literal `#` followed by the internal
RFC 6901 pointer. Thus `/tasks/0/skills` is rendered as
`plan/example-fast-tdd/PLAN.json#/tasks/0/skills`; the document root is rendered
as `plan/example-fast-tdd/PLAN.json#`.

Stable diagnostics (no alias codes):

| Code | Meaning |
|---|---|
| `FPV-CLI-001` | root is not absolute/readable or lacks `backend/artisan` |
| `FPV-CLI-002` | selector XOR violated: both missing or both `plan` and `--all` present |
| `FPV-CLI-003` | phase is outside `current|000..007` |
| `FPV-CLI-004` | target syntax is not canonical `plan/<slug>-fast-tdd` |
| `FPV-CLI-005` | canonical target directory does not exist |
| `FPV-CLI-006` | `--all` is combined with a non-current phase |
| `FPV-SYS-001` | `ARCHITECTURE.md`, `plan/README.md` or `plan/FAST_STRICT_V1.md` is missing |
| `FPV-SYS-002` | declared protocol version is unsupported |
| `FPV-SYS-003` | unexpected plan-root entry or noncanonical plan directory |
| `FPV-PATH-001` | unsafe or non-canonical path |
| `FPV-PATH-002` | path escapes repository/plan root |
| `FPV-PATH-003` | symlink or forbidden filesystem type |
| `FPV-PATH-004` | case collision or wrong-case canonical name |
| `FPV-FILE-001` | required plan file is missing or is not a regular file |
| `FPV-FILE-002` | required Markdown projection/evidence is invalid |
| `FPV-FILE-003` | undeclared file or directory exists |
| `FPV-JSON-001` | JSON exceeds the bounded input size |
| `FPV-JSON-002` | JSON encoding or parse failure |
| `FPV-JSON-003` | unknown or missing closed-contract key |
| `FPV-JSON-004` | value has the wrong JSON type/enum/format |
| `FPV-JSON-005` | duplicate ID or value declared unique by the contract |
| `FPV-PLAN-001` | plan identity, slug, revision or supersession invalid |
| `FPV-PLAN-002` | PLAN, protocol or architecture hash invalid |
| `FPV-PLAN-003` | requirement/acceptance/task/check coverage incomplete |
| `FPV-PLAN-004` | dependency graph/stage order invalid |
| `FPV-PLAN-005` | owned/create/delete path contract invalid |
| `FPV-PLAN-006` | exact skill object/Ponytail policy invalid |
| `FPV-PLAN-007` | fixed review-axis contract invalid |
| `FPV-PLAN-008` | gate shape, command or evidence mapping invalid |
| `FPV-STATUS-001` | STATUS identity/revision/hash mismatch |
| `FPV-STATUS-002` | stage/current/overall-state consistency invalid |
| `FPV-STATUS-003` | task state/executor/evidence consistency invalid |
| `FPV-STATUS-004` | used/unavailable/skipped skill evidence invalid |
| `FPV-STATUS-005` | active-writer count, lease or ownership invalid |
| `FPV-STATUS-006` | blocker/state consistency invalid |
| `FPV-EVID-001` | evidence closed shape/reference invalid |
| `FPV-EVID-002` | evidence file/path/hash invalid |
| `FPV-EVID-003` | RED proof is absent or not the intended non-zero failure |
| `FPV-EVID-004` | same-command GREEN proof is absent or non-zero |
| `FPV-EVID-005` | focused QA/Ponytail or gate evidence invalid |
| `FPV-MANIFEST-001` | phase-required baseline/final manifest missing |
| `FPV-MANIFEST-002` | manifest line format/order/duplicate invalid |
| `FPV-MANIFEST-003` | manifest path union differs from PLAN `ownedPaths` |
| `FPV-MANIFEST-004` | manifest/current/bundle hash invalid for phase |
| `FPV-REVIEW-001` | fixed review axis/round evidence invalid |
| `FPV-REVIEW-002` | reviewer independence invalid |
| `FPV-REVIEW-003` | reviews do not share the frozen snapshot |
| `FPV-REVIEW-004` | verdict/B0/B1 invalid; B2 alone never forbids GO |
| `FPV-COMPLETE-001` | Stage 007 engineering Definition of Done incomplete |

The validator accumulates independent errors, redacts suspicious values and
does not stop at the first malformed artifact when safe continuation is possible.

## 20. Canonical copy-paste JSON

Replace zero hashes, example timestamps, IDs, paths and commands with real
values. The examples are complete closed shapes: do not add convenience keys.

### `PLAN.json`

```json
{
  "protocolVersion": "FAST_STRICT/1",
  "planId": "example-fast-tdd",
  "slug": "example",
  "revision": 1,
  "supersedes": null,
  "title": "Example delivery",
  "goal": "Deliver one observable example behavior.",
  "createdAt": "2026-08-20T10:00:00Z",
  "architecture": {
    "path": "ARCHITECTURE.md",
    "sha256": "0000000000000000000000000000000000000000000000000000000000000000",
    "ruleIds": ["ARCH-MOD-001", "ARCH-PLAN-001"]
  },
  "protocol": {
    "path": "plan/FAST_STRICT_V1.md",
    "sha256": "0000000000000000000000000000000000000000000000000000000000000000"
  },
  "scope": {
    "in": ["Implement and verify the public example behavior."],
    "out": ["Unrelated refactors and new dependencies."]
  },
  "requirements": [
    {
      "id": "REQ-001",
      "type": "functional",
      "statement": "The public example behavior is available.",
      "acceptance": [
        {
          "id": "AC-001",
          "statement": "The focused public-interface check passes."
        }
      ]
    }
  ],
  "tasks": [
    {
      "id": "TASK-001",
      "stage": "000",
      "title": "Analyze business and system requirements",
      "objective": "Close scope, requirements and acceptance gaps.",
      "agentRole": "business_system",
      "executionMode": "read_only_subagent",
      "dependsOn": [],
      "ownedPaths": [],
      "createPaths": [],
      "deletePaths": [],
      "verificationMode": "inspection",
      "checks": [
        {
          "id": "CHECK-001",
          "kind": "inspection",
          "command": "Inspect the request, domain sources and traceability.",
          "expected": "No unresolved requirement or acceptance gap."
        }
      ],
      "skills": [
        {
          "id": "business-task-to-delivery-plan",
          "applyAt": ["analysis", "design"],
          "purpose": "Translate the requested outcome into delivery-ready requirements.",
          "expectedOutcome": "Requirements and acceptance are complete and testable."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["analysis", "design"],
          "purpose": "Keep scope and solution minimal.",
          "expectedOutcome": "No speculative requirement or work item is added."
        }
      ],
      "requirementIds": ["REQ-001"],
      "acceptanceIds": ["AC-001"]
    },
    {
      "id": "TASK-002",
      "stage": "000",
      "title": "Analyze architecture and codebase",
      "objective": "Prove exact placement and architecture conformance.",
      "agentRole": "architecture_codebase",
      "executionMode": "read_only_subagent",
      "dependsOn": [],
      "ownedPaths": [],
      "createPaths": [],
      "deletePaths": [],
      "verificationMode": "inspection",
      "checks": [
        {
          "id": "CHECK-002",
          "kind": "inspection",
          "command": "Inspect ARCH-* rules and current implementation boundaries.",
          "expected": "Paths and dependencies conform without a local exception."
        }
      ],
      "skills": [
        {
          "id": "architect-review",
          "applyAt": ["analysis", "design"],
          "purpose": "Verify dependency direction and architecture ownership.",
          "expectedOutcome": "Applicable ARCH-* rules and exact paths are identified."
        },
        {
          "id": "codebase-design",
          "applyAt": ["analysis", "design"],
          "purpose": "Choose the smallest coherent module interface.",
          "expectedOutcome": "The plan uses existing deep boundaries."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["analysis", "design"],
          "purpose": "Reject speculative architecture.",
          "expectedOutcome": "No needless layer, interface or dependency is proposed."
        }
      ],
      "requirementIds": ["REQ-001"],
      "acceptanceIds": ["AC-001"]
    },
    {
      "id": "TASK-003",
      "stage": "000",
      "title": "Analyze QA and delivery risk",
      "objective": "Close TDD, failure-mode and gate gaps.",
      "agentRole": "qa_risk",
      "executionMode": "read_only_subagent",
      "dependsOn": [],
      "ownedPaths": [],
      "createPaths": [],
      "deletePaths": [],
      "verificationMode": "inspection",
      "checks": [
        {
          "id": "CHECK-003",
          "kind": "inspection",
          "command": "Inspect RED/GREEN proof, edge cases and quality gates.",
          "expected": "Each acceptance condition has a deterministic verification path."
        }
      ],
      "skills": [
        {
          "id": "tdd",
          "applyAt": ["analysis", "design", "verification"],
          "purpose": "Design valid RED/GREEN and acceptance evidence.",
          "expectedOutcome": "Failures prove missing behavior and gates prove delivery."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["analysis", "design"],
          "purpose": "Use the cheapest durable verification.",
          "expectedOutcome": "No redundant test layer or gate is introduced."
        }
      ],
      "requirementIds": ["REQ-001"],
      "acceptanceIds": ["AC-001"]
    },
    {
      "id": "TASK-004",
      "stage": "001",
      "title": "Run preflight and freeze baseline",
      "objective": "Prove tools, skills, ownership and path state before product writes.",
      "agentRole": "orchestrator",
      "executionMode": "orchestrator",
      "dependsOn": ["TASK-001", "TASK-002", "TASK-003"],
      "ownedPaths": [],
      "createPaths": [],
      "deletePaths": [],
      "verificationMode": "inspection",
      "checks": [
        {
          "id": "CHECK-004",
          "kind": "inspection",
          "command": "Inspect runtime and exact skills, then verify evidence/baseline.sha256 against the complete ownedPaths union.",
          "expected": "The baseline is exact, tools and skills are available, and no product writer has started."
        }
      ],
      "skills": [
        {
          "id": "code-review",
          "applyAt": ["verification"],
          "purpose": "Verify the exact baseline and ownership surface.",
          "expectedOutcome": "Every authorized path has one unambiguous initial state."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["verification"],
          "purpose": "Keep the write surface minimal.",
          "expectedOutcome": "No speculative file is leased."
        }
      ],
      "requirementIds": ["REQ-001"],
      "acceptanceIds": ["AC-001"]
    },
    {
      "id": "TASK-005",
      "stage": "002",
      "title": "Deliver the example through RED, GREEN and refactor",
      "objective": "Prove intended RED, make the same command GREEN, then complete focused QA.",
      "agentRole": "backend_writer",
      "executionMode": "writer",
      "dependsOn": ["TASK-004"],
      "ownedPaths": ["backend/app/Example.php", "backend/tests/Feature/ExampleTest.php"],
      "createPaths": ["backend/app/Example.php", "backend/tests/Feature/ExampleTest.php"],
      "deletePaths": [],
      "verificationMode": "tdd",
      "checks": [
        {
          "id": "CHECK-005",
          "kind": "tdd",
          "command": "make dev-test-filter filter=ExampleTest",
          "expected": "Stage 002: non-zero only because behavior is absent; Stage 003: the unchanged command exits 0; Stage 004: it remains green after refactor."
        }
      ],
      "skills": [
        {
          "id": "laravel-development",
          "applyAt": ["design", "green", "refactor"],
          "purpose": "Use existing Laravel boundaries.",
          "expectedOutcome": "The behavior is idiomatic and correctly placed."
        },
        {
          "id": "laravel:tdd-with-pest",
          "applyAt": ["red", "green", "refactor", "verification"],
          "purpose": "Enforce same-command RED/GREEN/REFACTOR.",
          "expectedOutcome": "The focused behavior is proven without weakening the test."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["design", "red", "green", "refactor", "verification"],
          "purpose": "Deliver the minimum adequate implementation.",
          "expectedOutcome": "Focused QA finds no speculative abstraction, dependency or file."
        }
      ],
      "requirementIds": ["REQ-001"],
      "acceptanceIds": ["AC-001"]
    },
    {
      "id": "TASK-006",
      "stage": "005",
      "title": "Integrate and run full gates",
      "objective": "Synchronize durable artifacts and prove full quality and pre-commit readiness.",
      "agentRole": "orchestrator",
      "executionMode": "orchestrator",
      "dependsOn": ["TASK-005"],
      "ownedPaths": [],
      "createPaths": [],
      "deletePaths": [],
      "verificationMode": "inspection",
      "checks": [
        {
          "id": "CHECK-006",
          "kind": "inspection",
          "command": "make quality",
          "expected": "Exit 0."
        },
        {
          "id": "CHECK-007",
          "kind": "inspection",
          "command": "make pre-commit",
          "expected": "Exit 0."
        }
      ],
      "skills": [
        {
          "id": "laravel:quality-checks",
          "applyAt": ["verification"],
          "purpose": "Prove integrated repository gates.",
          "expectedOutcome": "Quality and pre-commit pass without suppression."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["verification"],
          "purpose": "Prevent integration scope expansion.",
          "expectedOutcome": "Only durable required artifacts changed."
        }
      ],
      "requirementIds": ["REQ-001"],
      "acceptanceIds": ["AC-001"]
    },
    {
      "id": "TASK-007",
      "stage": "007",
      "title": "Prepare integrity-backed handoff",
      "objective": "Report result, paths, checks, exact skills and known risks.",
      "agentRole": "orchestrator",
      "executionMode": "orchestrator",
      "dependsOn": ["TASK-006"],
      "ownedPaths": [],
      "createPaths": [],
      "deletePaths": [],
      "verificationMode": "inspection",
      "checks": [
        {
          "id": "CHECK-008",
          "kind": "inspection",
          "command": "make dev-plan-check plan=plan/example-fast-tdd phase=007",
          "expected": "Exit 0 and an evidence-backed engineering handoff."
        }
      ],
      "skills": [
        {
          "id": "writing-plans",
          "applyAt": ["handoff"],
          "purpose": "Keep handoff traceable to the frozen plan.",
          "expectedOutcome": "Changed paths, checks and limits are explicit."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["handoff"],
          "purpose": "Keep handoff concise and factual.",
          "expectedOutcome": "No duplicated protocol or speculative claim is reported."
        }
      ],
      "requirementIds": ["REQ-001"],
      "acceptanceIds": ["AC-001"]
    }
  ],
  "gates": [
    {
      "id": "GATE-PRECOMMIT",
      "stage": "005",
      "command": "make pre-commit",
      "evidenceId": "EVID-GATE-PRECOMMIT"
    },
    {
      "id": "GATE-QUALITY",
      "stage": "005",
      "command": "make quality",
      "evidenceId": "EVID-GATE-QUALITY"
    }
  ],
  "reviewAxes": [
    {
      "id": "REV-SECURITY-PRIVACY-DATA",
      "title": "Security / Privacy / Data / PostgreSQL",
      "skills": [
        {
          "id": "secret-scanner",
          "applyAt": ["review"],
          "purpose": "Detect leaked credentials and unsafe evidence.",
          "expectedOutcome": "No secret, personal data or unsafe persistence is present."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["review"],
          "purpose": "Reject unnecessary security machinery.",
          "expectedOutcome": "Controls are sufficient and minimal."
        },
        {
          "id": "ponytail:ponytail-review",
          "applyAt": ["review"],
          "purpose": "Audit security review scope for removable over-engineering.",
          "expectedOutcome": "Needless security layers and dependencies are reported."
        }
      ]
    },
    {
      "id": "REV-ARCHITECTURE-API-PONYTAIL",
      "title": "Architecture / API / Ponytail",
      "skills": [
        {
          "id": "architect-review",
          "applyAt": ["review"],
          "purpose": "Verify architecture and public-contract conformance.",
          "expectedOutcome": "All referenced ARCH-* rules are satisfied."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["review"],
          "purpose": "Apply minimality while reviewing architecture and API.",
          "expectedOutcome": "The review adds no speculative architecture."
        },
        {
          "id": "ponytail:ponytail-review",
          "applyAt": ["review"],
          "purpose": "Audit the snapshot for removable architectural complexity.",
          "expectedOutcome": "Needless layers, interfaces and dependencies are reported."
        }
      ]
    },
    {
      "id": "REV-SPEC-TDD-UX-A11Y",
      "title": "Spec / TDD / UX / a11y",
      "skills": [
        {
          "id": "tdd",
          "applyAt": ["review"],
          "purpose": "Verify requirements and valid RED/GREEN evidence.",
          "expectedOutcome": "Acceptance is observable and regression-protected."
        },
        {
          "id": "ponytail:ponytail",
          "applyAt": ["review"],
          "purpose": "Reject redundant test and UX layers.",
          "expectedOutcome": "Coverage is sufficient without duplication."
        },
        {
          "id": "ponytail:ponytail-review",
          "applyAt": ["review"],
          "purpose": "Audit verification and UX artifacts for removable complexity.",
          "expectedOutcome": "Redundant tests, wrappers and process artifacts are reported."
        }
      ]
    }
  ]
}
```

### Initial `STATUS.json`

This example assumes Stage 000 has not yet closed. Add actual evidence and change
states only after work occurs.

```json
{
  "protocolVersion": "FAST_STRICT/1",
  "planId": "example-fast-tdd",
  "planRevision": 1,
  "planSha256": "0000000000000000000000000000000000000000000000000000000000000000",
  "orchestratorId": "orchestrator",
  "overallState": "draft",
  "currentStage": "000",
  "supersededBy": null,
  "updatedAt": "2026-08-20T10:00:00Z",
  "stages": [
    {"id": "000", "state": "in_progress"},
    {"id": "001", "state": "pending"},
    {"id": "002", "state": "pending"},
    {"id": "003", "state": "pending"},
    {"id": "004", "state": "pending"},
    {"id": "005", "state": "pending"},
    {"id": "006", "state": "pending"},
    {"id": "007", "state": "pending"}
  ],
  "tasks": [
    {"id": "TASK-001", "state": "pending", "executorId": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "evidenceIds": [], "handoffEvidenceId": null},
    {"id": "TASK-002", "state": "pending", "executorId": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "evidenceIds": [], "handoffEvidenceId": null},
    {"id": "TASK-003", "state": "pending", "executorId": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "evidenceIds": [], "handoffEvidenceId": null},
    {"id": "TASK-004", "state": "pending", "executorId": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "evidenceIds": [], "handoffEvidenceId": null},
    {"id": "TASK-005", "state": "pending", "executorId": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "evidenceIds": [], "handoffEvidenceId": null},
    {"id": "TASK-006", "state": "pending", "executorId": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "evidenceIds": [], "handoffEvidenceId": null},
    {"id": "TASK-007", "state": "pending", "executorId": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "evidenceIds": [], "handoffEvidenceId": null}
  ],
  "activeWriters": [],
  "evidence": [],
  "gateResults": [
    {"id": "GATE-PRECOMMIT", "state": "pending", "evidenceId": null},
    {"id": "GATE-QUALITY", "state": "pending", "evidenceId": null}
  ],
  "snapshot": {
    "state": "pending",
    "round": 0,
    "manifestPath": null,
    "manifestSha256": null,
    "bundleSha256": null
  },
  "reviews": [
    {"axisId": "REV-SECURITY-PRIVACY-DATA", "reviewerId": null, "verdict": "pending", "round": 0, "snapshotSha256": null, "evidencePath": null, "evidenceSha256": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "b0": 0, "b1": 0, "b2": 0},
    {"axisId": "REV-ARCHITECTURE-API-PONYTAIL", "reviewerId": null, "verdict": "pending", "round": 0, "snapshotSha256": null, "evidencePath": null, "evidenceSha256": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "b0": 0, "b1": 0, "b2": 0},
    {"axisId": "REV-SPEC-TDD-UX-A11Y", "reviewerId": null, "verdict": "pending", "round": 0, "snapshotSha256": null, "evidencePath": null, "evidenceSha256": null, "usedSkills": [], "unavailableSkills": [], "skippedSkills": [], "b0": 0, "b1": 0, "b2": 0}
  ],
  "blockers": []
}
```

For a frozen Stage 006 round, replace the initial `snapshot` value with this
same closed shape and real hashes:

```json
{
  "state": "frozen",
  "round": 1,
  "manifestPath": "plan/example-fast-tdd/evidence/final.sha256",
  "manifestSha256": "0000000000000000000000000000000000000000000000000000000000000000",
  "bundleSha256": "0000000000000000000000000000000000000000000000000000000000000000"
}
```

### Produced baseline evidence element

When the PLAN declares the canonical Stage 001 orchestration task, its one
baseline record is also that task's check evidence:

```json
{
  "id": "EVID-BASELINE",
  "stage": "001",
  "taskId": "TASK-004",
  "checkId": "CHECK-004",
  "kind": "baseline",
  "path": "plan/example-fast-tdd/evidence/baseline.sha256",
  "sha256": "0000000000000000000000000000000000000000000000000000000000000000",
  "command": "Inspect runtime and exact skills, then verify evidence/baseline.sha256 against the complete ownedPaths union.",
  "exitCode": 0,
  "observedAt": "2026-08-20T10:20:00Z"
}
```

### Produced RED evidence element

```json
{
  "id": "EVID-RED-EXAMPLE",
  "stage": "002",
  "taskId": "TASK-005",
  "checkId": "CHECK-005",
  "kind": "red",
  "path": "plan/example-fast-tdd/evidence/EVID-RED-EXAMPLE.md",
  "sha256": "0000000000000000000000000000000000000000000000000000000000000000",
  "command": "make dev-test-filter filter=ExampleTest",
  "exitCode": 1,
  "observedAt": "2026-08-20T10:30:00Z"
}
```

The completed STATUS uses the same exact top-level and nested shapes; it is not a
different schema. Populate real immutable evidence, set every stage/task/gate to
complete/passed, freeze the final snapshot, record three independent `go`
reviews for one round/hash, clear writers/blockers/skill exceptions, and only
then set `overallState=complete`, `currentStage=007`.
