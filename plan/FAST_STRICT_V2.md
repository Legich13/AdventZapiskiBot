# FAST_STRICT/2

## 1. Status and compatibility

`FAST_STRICT/2` is the normative protocol for every new Jobgram tracked plan.
`FAST_STRICT/1` and all existing V1 plan directories are immutable history: they
are not migrated, completed artificially, or changed to satisfy V2.

V2 inherits the closed file layout, JSON shapes, identifiers, ownership rules,
eight stages, TDD evidence, review axes, hashing rules, security rules, and
diagnostic meanings from [`FAST_STRICT_V1.md`](FAST_STRICT_V1.md), except where
this document explicitly replaces them. A V2 plan uses:

```json
{
  "protocolVersion": "FAST_STRICT/2",
  "protocol": {
    "path": "plan/FAST_STRICT_V2.md",
    "sha256": "<sha256 of this file>"
  }
}
```

`PLAN.json` remains immutable after execution starts. `STATUS.json` remains the
append-only execution projection. Product behavior and architecture remain
defined by product code, tests, `ARCHITECTURE.md`, profile documentation, and
`AGENTS.md`; a completed plan is delivery evidence, not a product specification.

## 2. Verification policy

The verification unit is a completed behavior or contract. A task does not
create a separate full-suite cycle for every function, file, or small edit.
Every executable check, generator, migration, and runtime action uses the
existing root Make target. Direct `php artisan`, `composer`, `pnpm`, `vitest`,
`playwright`, or `docker compose` calls are forbidden when Make already exposes
the action.

| Stage | Required verification |
|---|---|
| `000` analysis | Read affected code, contracts, consumers, and the approved plan. A full baseline suite is not required. |
| `001` discovery/baseline | Record the declared write surface and baseline manifest. Run only discovery checks needed by the task. |
| `002` RED | Add or change the test for the intended behavior and record the expected failure from that focused test or directly related set. |
| `003` GREEN | Implement the behavior and run the same focused command. |
| `004` refactor/QA | Run focused checks for the completed behavior and directly affected existing consumers. |
| `005` final integration | Finish code, docs, and generated files; prepare `final.sha256`; run `make quality`, then `make pre-commit`. |
| `006` review | Freeze one snapshot and run the three independent review axes against its diff and existing evidence. Reviewers do not run their own full suites. |
| `007` handoff | Validate plan integrity and report existing evidence. Do not rerun product tests only to write the report. |

Focused scope can be large when a shared API, authentication boundary, or common
function has many directly affected consumers. The scope is determined by the
contract, not by an artificial test-count limit. Existing tests are reused;
new tests cover missing behavior and demonstrated regressions.

Playwright, load, and extra integration checks are selected when the changed
scenario or task requirements need them. The two fixed final gates do not imply
that every optional suite runs for every change.

Do not run all individual components of `quality` immediately before `quality`.
A command may be repeated only after one of these events:

1. files that are inputs to that command changed;
2. a concrete failure from that command was fixed;
3. an environment failure was confirmed and the environment recovered.

Stage movement, `STATUS.json` or handoff-only edits, review assignment, and
writing the final response are not reasons to repeat an unchanged command.

## 3. PLAN gate shape

V2 replaces the V1 gate shape with this closed object:

```json
{
  "id": "GATE-QUALITY",
  "stage": "005",
  "command": "make quality"
}
```

`evidenceId` is forbidden in a V2 `PLAN.gates` item. The required ordered gates
remain:

```json
[
  {"id": "GATE-PRECOMMIT", "stage": "005", "command": "make pre-commit"},
  {"id": "GATE-QUALITY", "stage": "005", "command": "make quality"}
]
```

The ordering is the machine projection order. The execution order at Stage 005
is `make quality` followed by `make pre-commit`.

## 4. Evidence attempts and freshness

V2 replaces the V1 evidence object with the same closed object plus two required
nullable fields:

```json
{
  "id": "EVID-GATE-QUALITY-ATTEMPT-002",
  "stage": "005",
  "taskId": null,
  "checkId": null,
  "kind": "gate",
  "path": "plan/example-fast-tdd/evidence/EVID-GATE-QUALITY-ATTEMPT-002.md",
  "sha256": "<sha256 of evidence markdown>",
  "command": "make quality",
  "exitCode": 0,
  "observedAt": "2026-09-07T12:00:00Z",
  "gateId": "GATE-QUALITY",
  "verifiedManifestSha256": "<sha256 of evidence/final.sha256>"
}
```

Every V2 evidence record contains `gateId` and `verifiedManifestSha256`.
For an attempt of a declared gate both are non-null. For every other evidence
record both are `null`. A bound gate attempt has `kind: "gate"` and exactly the
stage and command of the referenced gate.

Every attempt receives a new unique evidence ID and a new immutable Markdown
file. `STATUS.evidence` is stored in append order. Earlier attempts are never
edited, removed, sorted, or reused. The evidence Markdown includes, and its hash
therefore protects, all standard evidence fields plus these exact lines:

```text
Gate: GATE-QUALITY
Verified manifest SHA-256: <sha256 of evidence/final.sha256>
```

For non-gate evidence the lines use `none`.

For each gate, `STATUS.gateResults` references the last appended attempt whose
`gateId` matches and whose `verifiedManifestSha256` equals the hash of the
current `evidence/final.sha256`:

- exit code `0` projects to `passed`;
- non-zero projects to `failed`;
- no attempt for the current manifest projects to `pending` with a null
  `evidenceId`.

A later failure cannot be hidden by an earlier success. A success against an old
manifest cannot validate changed implementation. Attempts against old manifests
remain immutable history.

## 5. Final manifest and gate recovery

`evidence/final.sha256` is prepared after all implementation, documentation, and
required generated files are complete and before the first full Stage 005 gate.
At Stage 005 and later, a current non-historical plan must match the file hashes
listed in that manifest. A gate attempt binds to the SHA-256 of the manifest
file, not to a mutable field in `STATUS.json`.

When implementation inputs change, prepare a new final manifest. Existing gate
attempts stay in `STATUS.evidence`; both gate results return to `pending` until
new attempts exist for the current manifest. Stage 005 closes only when both
current attempts pass.

When a full gate fails:

1. classify it as a current implementation failure, environment failure, or a
   pre-existing failure demonstrated on the original state;
2. reduce it to the focused failing test or scenario;
3. fix the current implementation within the agreed diff and preserve existing
   contracts unless the contract change was explicitly approved;
4. run the focused check after related fixes are complete;
5. repeat the affected final gate when its inputs changed. If only the
   environment recovered, repeat only the failed command; the current success of
   the other command remains valid.

Do not remove existing tests, weaken assertions, disable access checks, or hide
errors to obtain a pass. A confirmed unrelated failure remains a reported failed
gate and is handled separately; it does not expand the current plan into an
unbounded repair task.

## 6. Snapshot, reviews, and remediation

V2 keeps the three fixed independent review axes and all V1 independence,
skills, evidence, finding severity, and GO rules. All reviewers inspect the same
frozen manifest and evidence bundle. They use the recorded focused and final
gate evidence and do not execute their own full test suites.

Collect the first verdict from all three axes before remediation. Apply related
findings as one coordinated set. If product code, tested documentation, generated
files, or configuration changes, set the old snapshot to `invalidated`, retain
its old manifest and bundle hashes, create a current final manifest, obtain
current gate attempts, freeze a new snapshot round, and run all three axes on
that new snapshot.

An invalidated snapshot is historical. Its stored hashes must remain valid
SHA-256 values, but they are not compared with files changed by remediation.
Completion is forbidden until a new snapshot is frozen and all required current
reviews are GO with no blocking findings.

A change only to the plan report, `STATUS.json`, or handoff narrative does not
change product gate inputs and does not require another product test run.
Validated MDX, generated files, and configuration are gate inputs and do require
fresh verification when changed.

## 7. Validation interface

The single-plan command infers its protocol from `PLAN.json`:

```bash
make dev-plan-check plan=plan/<feature-slug>-fast-tdd phase=current
```

The current-plan aggregate is:

```bash
php artisan plan:validate --all --protocol=FAST_STRICT/2 --phase=current --root=/workspace
```

`--protocol` is valid only with `--all`. `make dev-plan-check-all` and the
isolated pre-commit use the V2 filter. An explicit `--all` call without a filter
continues to validate both protocol versions for historical audits.

Filtering reads each `PLAN.json` only far enough to determine the version.
V1 `STATUS.json`, V1 evidence, and old architecture hashes are not read by a V2
filtered run. Malformed `PLAN.json` or an unknown protocol version is an error
and is never silently excluded. Exit codes remain:

- `0`: selected plans are valid;
- `1`: plan or repository integrity failed;
- `2`: CLI usage is invalid.

`FPV-CLI-007` identifies an unsupported `--protocol` value or use without
`--all`. All other V1 diagnostic codes keep their established meanings.

## 8. Completion

A V2 plan is complete only when all V1 structural, ownership, skill, TDD,
traceability, evidence, security, and handoff invariants hold, and additionally:

- Stage 004 contains focused QA for the completed behavior and affected
  consumers;
- the current final manifest matches declared files before gate acceptance;
- the latest attempt for each current gate and manifest is successful;
- one frozen current snapshot has three independent GO reviews with no B0/B1;
- no blocking finding, active writer, or blocker remains;
- Stage 007 records handoff evidence without rerunning unchanged product gates.
