<?php

declare(strict_types=1);

namespace App\Support\Planning;

use DateTimeImmutable;
use DateTimeZone;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * @phpstan-type Skill array{id: string, applyAt: list<string>, purpose: string, expectedOutcome: string}
 * @phpstan-type Check array{id: string, kind: string, command: string, expected: string}
 * @phpstan-type Acceptance array{id: string, statement: string}
 * @phpstan-type Requirement array{id: string, type: string, statement: string, acceptance: list<Acceptance>}
 * @phpstan-type Task array{id: string, stage: string, title: string, objective: string, agentRole: string, executionMode: string, dependsOn: list<string>, ownedPaths: list<string>, createPaths: list<string>, deletePaths: list<string>, verificationMode: string, checks: list<Check>, skills: list<Skill>, requirementIds: list<string>, acceptanceIds: list<string>}
 * @phpstan-type Gate array{id: string, stage: string, command: string, evidenceId?: string}
 * @phpstan-type ReviewAxis array{id: string, title: string, skills: list<Skill>}
 * @phpstan-type Plan array{protocolVersion: string, planId: string, slug: string, revision: int, supersedes: string|null, title: string, goal: string, createdAt: string, architecture: array{path: string, sha256: string, ruleIds: list<string>}, protocol: array{path: string, sha256: string}, scope: array{in: list<string>, out: list<string>}, requirements: list<Requirement>, tasks: list<Task>, gates: list<Gate>, reviewAxes: list<ReviewAxis>}
 * @phpstan-type Reason array{id: string, reason: string}
 * @phpstan-type StatusStage array{id: string, state: string}
 * @phpstan-type StatusTask array{id: string, state: string, executorId: string|null, usedSkills: list<string>, unavailableSkills: list<Reason>, skippedSkills: list<Reason>, evidenceIds: list<string>, handoffEvidenceId: string|null}
 * @phpstan-type ActiveWriter array{writerId: string, taskIds: list<string>}
 * @phpstan-type Evidence array{id: string, stage: string, taskId: string|null, checkId: string|null, kind: string, path: string, sha256: string, command: string, exitCode: int, observedAt: string, gateId?: string|null, verifiedManifestSha256?: string|null}
 * @phpstan-type GateResult array{id: string, state: string, evidenceId: string|null}
 * @phpstan-type Snapshot array{state: string, round: int, manifestPath: string|null, manifestSha256: string|null, bundleSha256: string|null}
 * @phpstan-type Review array{axisId: string, reviewerId: string|null, verdict: string, round: int, snapshotSha256: string|null, evidencePath: string|null, evidenceSha256: string|null, usedSkills: list<string>, unavailableSkills: list<Reason>, skippedSkills: list<Reason>, b0: int, b1: int, b2: int}
 * @phpstan-type Blocker array{id: string, taskId: string|null, reason: string, openedAt: string}
 * @phpstan-type Status array{protocolVersion: string, planId: string, planRevision: int, planSha256: string, orchestratorId: string, overallState: string, currentStage: string, supersededBy: string|null, updatedAt: string, stages: list<StatusStage>, tasks: list<StatusTask>, activeWriters: list<ActiveWriter>, evidence: list<Evidence>, gateResults: list<GateResult>, snapshot: Snapshot, reviews: list<Review>, blockers: list<Blocker>}
 */
final class FastPlanValidator
{
    private const string PROTOCOL_V1 = 'FAST_STRICT/1';

    private const string PROTOCOL_V2 = 'FAST_STRICT/2';

    /** @var list<string> */
    private const array PROTOCOLS = [self::PROTOCOL_V1, self::PROTOCOL_V2];

    private const int MAX_JSON_BYTES = 1_048_576;

    /** @var list<string> */
    private const array STAGES = ['000', '001', '002', '003', '004', '005', '006', '007'];

    /** @var list<string> */
    private const array REVIEW_AXES = [
        'REV-SECURITY-PRIVACY-DATA',
        'REV-ARCHITECTURE-API-PONYTAIL',
        'REV-SPEC-TDD-UX-A11Y',
    ];

    /** @var array<string, string> */
    private const array REVIEW_TITLES = [
        'REV-SECURITY-PRIVACY-DATA' => 'Security / Privacy / Data / PostgreSQL',
        'REV-ARCHITECTURE-API-PONYTAIL' => 'Architecture / API / Ponytail',
        'REV-SPEC-TDD-UX-A11Y' => 'Spec / TDD / UX / a11y',
    ];

    /** @var array<string, string> */
    private const array REVIEW_SUFFIXES = [
        'REV-SECURITY-PRIVACY-DATA' => 'security-privacy-data',
        'REV-ARCHITECTURE-API-PONYTAIL' => 'architecture-api-ponytail',
        'REV-SPEC-TDD-UX-A11Y' => 'spec-tdd-ux-a11y',
    ];

    /** @var list<array{code: string, path: string, pointer: string, message: string, planId: string}> */
    private array $errors = [];

    private string $root = '';

    private string $planId = '';

    private int $checks = 0;

    /**
     * @return array{
     *     plans: int,
     *     checks: int,
     *     errors: list<array{code: string, path: string, pointer: string, message: string}>
     * }
     */
    public function validate(string $root, ?string $target, bool $all, string $phase, ?string $protocol = null): array
    {
        $this->errors = [];
        $this->checks = 0;
        $this->planId = '';

        if (! str_starts_with($root, '/') || ! is_dir($root) || ! is_readable($root)) {
            $this->error('FPV-CLI-001', '-', '', 'Repository root must be an existing absolute directory.');

            return $this->result(0);
        }

        $resolvedRoot = realpath($root);
        if ($resolvedRoot === false) {
            $this->error('FPV-CLI-001', '-', '', 'Repository root cannot be resolved.');

            return $this->result(0);
        }
        $this->root = rtrim($resolvedRoot, '/');
        if (! is_file($this->root.'/backend/artisan') || is_link($this->root.'/backend/artisan')
            || ! is_readable($this->root.'/backend/artisan')) {
            $this->error('FPV-CLI-001', 'backend/artisan', '', 'Repository root must contain its regular readable backend/artisan entry.');

            return $this->result(0);
        }

        $hasTarget = is_string($target) && $target !== '';
        if ($hasTarget === $all) {
            $this->error('FPV-CLI-002', '-', '', 'Exactly one selector, plan or --all, is required.');

            return $this->result(0);
        }

        if ($protocol !== null && ($protocol === '' || ! $all || ! in_array($protocol, self::PROTOCOLS, true))) {
            $this->error('FPV-CLI-007', '-', '', '--protocol accepts a supported version only together with --all.');

            return $this->result(0);
        }

        if (! in_array($phase, array_merge(['current'], self::STAGES), true)) {
            $this->error('FPV-CLI-003', '-', '', 'Phase must be current or stage 000 through 007.');

            return $this->result(0);
        }

        if ($all && $phase !== 'current') {
            $this->error('FPV-CLI-006', '-', '', '--all accepts only phase=current.');

            return $this->result(0);
        }

        if ($hasTarget && preg_match('#^plan/[a-z0-9]+(?:-[a-z0-9]+)*-fast-tdd$#D', $target) !== 1) {
            $this->error('FPV-CLI-004', '-', '', 'Target must be one canonical plan/<slug>-fast-tdd path.');

            return $this->result(0);
        }

        if ($hasTarget && ! is_dir($this->root.'/'.$target)) {
            $this->error('FPV-CLI-005', $target, '', 'Selected plan directory does not exist.');

            return $this->result(0);
        }

        if (! $this->validateSystemEntries()) {
            return $this->result(0);
        }

        $targets = $all ? $this->discoverTargets($protocol) : [$target];
        if ($targets === null) {
            return $this->result(0);
        }

        /** @var list<string> $targets */
        foreach ($targets as $planPath) {
            $this->planId = basename($planPath);
            $this->checks++;
            $this->validatePlanDirectory($planPath, $phase);
        }

        return $this->result(count($targets));
    }

    private function validateSystemEntries(): bool
    {
        $valid = true;
        foreach (['ARCHITECTURE.md', 'plan', 'plan/README.md', 'plan/FAST_STRICT_V1.md', 'plan/FAST_STRICT_V2.md'] as $path) {
            $absolute = $this->root.'/'.$path;
            $expectedDirectory = $path === 'plan';
            if (is_link($absolute)
                || ($expectedDirectory && ! is_dir($absolute))
                || (! $expectedDirectory && ! is_file($absolute))) {
                $this->error('FPV-SYS-001', $path, '', 'Required repository system entry is missing.');
                $valid = false;

                continue;
            }
            if (! $expectedDirectory) {
                $contents = is_readable($absolute) ? file_get_contents($absolute) : false;
                if (! is_string($contents) || trim($contents) === '' || ! mb_check_encoding($contents, 'UTF-8')) {
                    $this->error('FPV-FILE-002', $path, '', 'System Markdown must be readable, non-empty UTF-8.');
                    $valid = false;
                }
            }
        }

        $entries = is_dir($this->root.'/plan') ? scandir($this->root.'/plan') : false;
        if (is_array($entries)) {
            foreach ($entries as $entry) {
                if (in_array($entry, ['.', '..', 'README.md', 'FAST_STRICT_V1.md', 'FAST_STRICT_V2.md'], true)) {
                    continue;
                }
                if (is_dir($this->root.'/plan/'.$entry)
                    && preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*-fast-tdd$/D', $entry) === 1) {
                    continue;
                }
                $this->planId = $entry;
                $this->error('FPV-SYS-003', 'plan/'.$entry, '', 'Planning root contains an undeclared file or non-canonical directory.');
                $valid = false;
            }
        }

        return $valid;
    }

    /** @return list<string>|null */
    private function discoverTargets(?string $protocol): ?array
    {
        $entries = scandir($this->root.'/plan');
        if ($entries === false) {
            $this->error('FPV-SYS-001', 'plan', '', 'Planning root cannot be read.');

            return null;
        }

        $targets = [];
        $valid = true;
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $this->root.'/plan/'.$entry;
            if (! is_dir($path)) {
                continue;
            }
            if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*-fast-tdd$/D', $entry) !== 1) {
                $this->planId = $entry;
                $this->error('FPV-SYS-003', 'plan/'.$entry, '', 'Plan root contains a non-canonical directory.');
                $valid = false;

                continue;
            }
            $planPath = 'plan/'.$entry;
            if ($protocol !== null) {
                $this->planId = $entry;
                $plan = $this->readJson($planPath.'/PLAN.json');
                $version = $plan['protocolVersion'] ?? null;
                if (! is_string($version) || ! in_array($version, self::PROTOCOLS, true)) {
                    $this->error('FPV-SYS-002', $planPath.'/PLAN.json', '/protocolVersion', 'Unsupported planning protocol.');
                    $valid = false;

                    continue;
                }
                if ($version !== $protocol) {
                    continue;
                }
            }
            $targets[] = $planPath;
        }

        sort($targets, SORT_STRING);

        return $valid ? $targets : null;
    }

    private function validatePlanDirectory(string $planPath, string $phase): void
    {
        $directory = $this->root.'/'.$planPath;
        if (is_link($directory)) {
            $this->error('FPV-PATH-003', $planPath, '', 'Canonical plan directory cannot be a symlink.');

            return;
        }
        $planPathCode = $this->pathViolation($planPath);
        if ($planPathCode !== null) {
            $this->error($planPathCode, $planPath, '', 'Canonical plan directory is unsafe.');

            return;
        }
        $this->validateFilesystemTypes($planPath, $directory);

        foreach (['README.md', 'PLAN.json', 'STATUS.json', 'TASKS.md', 'ARCHITECTURE_CONFORMANCE.md', 'DECISIONS.md', 'TRACEABILITY.md'] as $file) {
            if (! is_file($directory.'/'.$file)) {
                $this->error('FPV-FILE-001', $planPath.'/'.$file, '', 'Required plan file is missing.');
            }
        }

        $plan = $this->readJson($planPath.'/PLAN.json');
        $status = $this->readJson($planPath.'/STATUS.json');
        if ($plan === null || $status === null) {
            $this->validateClosedFilesystem($planPath, null, null);

            return;
        }

        $protocolVersion = $plan['protocolVersion'] ?? null;
        if (! is_string($protocolVersion) || ! in_array($protocolVersion, self::PROTOCOLS, true)) {
            $this->error('FPV-SYS-002', $planPath.'/PLAN.json', '/protocolVersion', 'Unsupported planning protocol.');
            $this->validateClosedFilesystem($planPath, $plan, $status);

            return;
        }

        if (! $this->validatePlanShape($plan, $planPath.'/PLAN.json', $protocolVersion)
            || ! $this->validateStatusShape($status, $planPath.'/STATUS.json', $protocolVersion)) {
            $this->validateClosedFilesystem($planPath, $plan, $status);

            return;
        }

        /** @var Plan $plan */
        /** @var Status $status */
        $planErrorCount = count($this->errors);
        $this->validatePlanSemantics($planPath, $plan, $status);
        if (count($this->errors) !== $planErrorCount) {
            return;
        }
        $statusErrorCount = count($this->errors);
        $this->validateStatusSemantics($planPath, $plan, $status, $phase);
        if (count($this->errors) !== $statusErrorCount) {
            return;
        }
        $this->validateClosedFilesystem($planPath, $plan, $status);
        $this->validateHumanFiles($planPath, $plan);
    }

    /** @return array<mixed, mixed>|null */
    private function readJson(string $path): ?array
    {
        $absolute = $this->root.'/'.$path;
        if (! is_file($absolute)) {
            return null;
        }

        $size = filesize($absolute);
        if ($size === false || $size > self::MAX_JSON_BYTES) {
            $this->error('FPV-JSON-001', $path, '', 'JSON input exceeds the one MiB limit.');

            return null;
        }

        $contents = file_get_contents($absolute);
        if ($contents === false || str_starts_with($contents, "\xEF\xBB\xBF") || ! mb_check_encoding($contents, 'UTF-8')) {
            $this->error('FPV-JSON-002', $path, '', 'JSON must be UTF-8 without BOM.');

            return null;
        }

        try {
            $decoded = json_decode($contents, true, 128, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->error('FPV-JSON-002', $path, '', 'JSON cannot be parsed.');

            return null;
        }

        if (! is_array($decoded) || array_is_list($decoded)) {
            $this->error('FPV-JSON-002', $path, '', 'JSON top level must be one object.');

            return null;
        }

        return $decoded;
    }

    /**
     * @param  array<mixed, mixed>  $value
     * @param  list<string>  $keys
     */
    private function closed(array $value, array $keys, string $path, string $pointer): bool
    {
        if (array_is_list($value)) {
            $this->error('FPV-JSON-004', $path, $pointer, 'Expected a JSON object.');

            return false;
        }

        $actual = array_keys($value);
        sort($actual, SORT_STRING);
        $expected = $keys;
        sort($expected, SORT_STRING);
        if ($actual !== $expected) {
            $this->error('FPV-JSON-003', $path, $pointer, 'Closed object has an unknown or missing key.');

            return false;
        }

        return true;
    }

    /**
     * @return list<mixed>|null
     */
    private function listValue(mixed $value, string $path, string $pointer, bool $nonEmpty = false): ?array
    {
        if (! is_array($value) || ! array_is_list($value) || ($nonEmpty && $value === [])) {
            $this->error('FPV-JSON-004', $path, $pointer, 'Expected a JSON list with the required cardinality.');

            return null;
        }

        return $value;
    }

    private function nonEmptyString(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    /** @param list<mixed> $values */
    private function stringList(array $values, bool $nonEmpty = false): bool
    {
        if ($nonEmpty && $values === []) {
            return false;
        }

        return array_all($values, fn ($value) => $this->nonEmptyString($value));
    }

    /**
     * @param  array<mixed, mixed>  $plan
     *
     * @phpstan-assert-if-true Plan $plan
     */
    private function validatePlanShape(array $plan, string $path, string $protocolVersion): bool
    {
        $valid = $this->closed($plan, [
            'protocolVersion', 'planId', 'slug', 'revision', 'supersedes', 'title', 'goal', 'createdAt',
            'architecture', 'protocol', 'scope', 'requirements', 'tasks', 'gates', 'reviewAxes',
        ], $path, '');
        if (! $valid) {
            return false;
        }

        if (! $this->nonEmptyString($plan['protocolVersion'])
            || ! $this->nonEmptyString($plan['planId'])
            || ! $this->nonEmptyString($plan['slug'])
            || ! is_int($plan['revision'])
            || $plan['revision'] < 1
            || (! is_null($plan['supersedes']) && ! $this->nonEmptyString($plan['supersedes']))
            || ! $this->nonEmptyString($plan['title'])
            || ! $this->nonEmptyString($plan['goal'])
            || ! $this->nonEmptyString($plan['createdAt'])) {
            $this->error('FPV-JSON-004', $path, '', 'PLAN scalar has the wrong JSON type or format.');
            $valid = false;
        }

        foreach (['architecture', 'protocol', 'scope'] as $object) {
            if (! is_array($plan[$object]) || array_is_list($plan[$object])) {
                $this->error('FPV-JSON-004', $path, '/'.$object, 'Expected a JSON object.');
                $valid = false;
            }
        }
        if (! $valid) {
            return false;
        }

        /** @var array<string, mixed> $architecture */
        $architecture = $plan['architecture'];
        $valid = $this->closed($architecture, ['path', 'sha256', 'ruleIds'], $path, '/architecture');
        $rules = $this->listValue($architecture['ruleIds'] ?? null, $path, '/architecture/ruleIds', true);
        if (! $this->nonEmptyString($architecture['path'] ?? null)
            || ! $this->nonEmptyString($architecture['sha256'] ?? null)
            || $rules === null
            || ! $this->stringList($rules, true)) {
            $this->error('FPV-JSON-004', $path, '/architecture', 'Architecture object has the wrong JSON type or format.');
            $valid = false;
        }

        /** @var array<string, mixed> $protocol */
        $protocol = $plan['protocol'];
        $valid = $this->closed($protocol, ['path', 'sha256'], $path, '/protocol') && $valid;
        if (! $this->nonEmptyString($protocol['path'] ?? null) || ! $this->nonEmptyString($protocol['sha256'] ?? null)) {
            $this->error('FPV-JSON-004', $path, '/protocol', 'Protocol object has the wrong JSON type or format.');
            $valid = false;
        }

        /** @var array<string, mixed> $scope */
        $scope = $plan['scope'];
        $valid = $this->closed($scope, ['in', 'out'], $path, '/scope') && $valid;
        foreach (['in', 'out'] as $key) {
            $values = $this->listValue($scope[$key] ?? null, $path, '/scope/'.$key, true);
            if ($values === null || ! $this->stringList($values, true)) {
                $this->error('FPV-JSON-004', $path, '/scope/'.$key, 'Scope list must contain non-empty strings.');
                $valid = false;
            }
        }

        $requirements = $this->listValue($plan['requirements'], $path, '/requirements', true);
        $tasks = $this->listValue($plan['tasks'], $path, '/tasks', true);
        $gates = $this->listValue($plan['gates'], $path, '/gates', true);
        $axes = $this->listValue($plan['reviewAxes'], $path, '/reviewAxes', true);
        if ($requirements === null || $tasks === null || $gates === null || $axes === null) {
            return false;
        }

        foreach ($requirements as $index => $requirement) {
            $pointer = '/requirements/'.$index;
            if (! is_array($requirement) || array_is_list($requirement)
                || ! $this->closed($requirement, ['id', 'type', 'statement', 'acceptance'], $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($requirement['id'])
                || ! in_array($requirement['type'], ['functional', 'non_functional', 'constraint'], true)
                || ! $this->nonEmptyString($requirement['statement'])) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Requirement has the wrong JSON type or enum.');
                $valid = false;
            }
            $acceptance = $this->listValue($requirement['acceptance'], $path, $pointer.'/acceptance', true);
            if ($acceptance === null) {
                $valid = false;

                continue;
            }
            foreach ($acceptance as $acceptanceIndex => $criterion) {
                $criterionPointer = $pointer.'/acceptance/'.$acceptanceIndex;
                if (! is_array($criterion) || array_is_list($criterion)
                    || ! $this->closed($criterion, ['id', 'statement'], $path, $criterionPointer)) {
                    $valid = false;

                    continue;
                }
                if (! $this->nonEmptyString($criterion['id']) || ! $this->nonEmptyString($criterion['statement'])) {
                    $this->error('FPV-JSON-004', $path, $criterionPointer, 'Acceptance criterion has the wrong JSON type.');
                    $valid = false;
                }
            }
        }

        foreach ($tasks as $index => $task) {
            $pointer = '/tasks/'.$index;
            if (! is_array($task) || array_is_list($task)
                || ! $this->closed($task, [
                    'id', 'stage', 'title', 'objective', 'agentRole', 'executionMode', 'dependsOn',
                    'ownedPaths', 'createPaths', 'deletePaths', 'verificationMode', 'checks', 'skills',
                    'requirementIds', 'acceptanceIds',
                ], $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($task['id'])
                || ! in_array($task['stage'], self::STAGES, true)
                || ! $this->nonEmptyString($task['title'])
                || ! $this->nonEmptyString($task['objective'])
                || ! $this->nonEmptyString($task['agentRole'])
                || ! in_array($task['executionMode'], ['orchestrator', 'read_only_subagent', 'writer', 'reviewer'], true)
                || ! in_array($task['verificationMode'], ['tdd', 'contract', 'inspection'], true)) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Task has the wrong JSON type or enum.');
                $valid = false;
            }
            foreach (['dependsOn', 'ownedPaths', 'createPaths', 'deletePaths', 'requirementIds', 'acceptanceIds'] as $key) {
                $values = $this->listValue($task[$key], $path, $pointer.'/'.$key);
                if ($values === null || ! $this->stringList($values)) {
                    $this->error('FPV-JSON-004', $path, $pointer.'/'.$key, 'Task reference/path list is invalid.');
                    $valid = false;
                }
            }
            $checks = $this->listValue($task['checks'], $path, $pointer.'/checks', true);
            if ($checks === null) {
                $valid = false;
            } else {
                foreach ($checks as $checkIndex => $check) {
                    $checkPointer = $pointer.'/checks/'.$checkIndex;
                    if (! is_array($check) || array_is_list($check)
                        || ! $this->closed($check, ['id', 'kind', 'command', 'expected'], $path, $checkPointer)) {
                        $valid = false;

                        continue;
                    }
                    if (! $this->nonEmptyString($check['id'])
                        || ! in_array($check['kind'], ['tdd', 'contract', 'inspection'], true)
                        || ! $this->nonEmptyString($check['command'])
                        || ! $this->nonEmptyString($check['expected'])) {
                        $this->error('FPV-JSON-004', $path, $checkPointer, 'Check has the wrong JSON type or enum.');
                        $valid = false;
                    }
                }
            }
            $skills = $this->listValue($task['skills'], $path, $pointer.'/skills', true);
            if ($skills === null || ! $this->validateSkillShapes($skills, $path, $pointer.'/skills')) {
                $valid = false;
            }
        }

        foreach ($gates as $index => $gate) {
            $pointer = '/gates/'.$index;
            $gateKeys = $protocolVersion === self::PROTOCOL_V2
                ? ['id', 'stage', 'command']
                : ['id', 'stage', 'command', 'evidenceId'];
            if (! is_array($gate) || array_is_list($gate)
                || ! $this->closed($gate, $gateKeys, $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($gate['id'])
                || ! in_array($gate['stage'], self::STAGES, true)
                || ! $this->nonEmptyString($gate['command'])
                || ($protocolVersion === self::PROTOCOL_V1 && ! $this->nonEmptyString($gate['evidenceId'] ?? null))) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Gate has the wrong JSON type or enum.');
                $valid = false;
            }
        }

        foreach ($axes as $index => $axis) {
            $pointer = '/reviewAxes/'.$index;
            if (! is_array($axis) || array_is_list($axis)
                || ! $this->closed($axis, ['id', 'title', 'skills'], $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($axis['id']) || ! $this->nonEmptyString($axis['title'])) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Review axis has the wrong JSON type.');
                $valid = false;
            }
            $skills = $this->listValue($axis['skills'], $path, $pointer.'/skills', true);
            if ($skills === null || ! $this->validateSkillShapes($skills, $path, $pointer.'/skills')) {
                $valid = false;
            }
        }

        return $valid;
    }

    /** @param list<mixed> $skills */
    private function validateSkillShapes(array $skills, string $path, string $pointer): bool
    {
        $valid = true;
        foreach ($skills as $index => $skill) {
            $skillPointer = $pointer.'/'.$index;
            if (! is_array($skill) || array_is_list($skill)
                || ! $this->closed($skill, ['id', 'applyAt', 'purpose', 'expectedOutcome'], $path, $skillPointer)) {
                $valid = false;

                continue;
            }
            $applyAt = $this->listValue($skill['applyAt'], $path, $skillPointer.'/applyAt', true);
            if (! $this->nonEmptyString($skill['id'])
                || ! $this->nonEmptyString($skill['purpose'])
                || ! $this->nonEmptyString($skill['expectedOutcome'])
                || $applyAt === null
                || ! $this->stringList($applyAt, true)) {
                $this->error('FPV-JSON-004', $path, $skillPointer, 'Skill object has the wrong JSON type.');
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * @param  array<mixed, mixed>  $status
     *
     * @phpstan-assert-if-true Status $status
     */
    private function validateStatusShape(array $status, string $path, string $protocolVersion): bool
    {
        $valid = $this->closed($status, [
            'protocolVersion', 'planId', 'planRevision', 'planSha256', 'orchestratorId', 'overallState',
            'currentStage', 'supersededBy', 'updatedAt', 'stages', 'tasks', 'activeWriters', 'evidence',
            'gateResults', 'snapshot', 'reviews', 'blockers',
        ], $path, '');
        if (! $valid) {
            return false;
        }

        if (! $this->nonEmptyString($status['protocolVersion'])
            || ! $this->nonEmptyString($status['planId'])
            || ! is_int($status['planRevision'])
            || $status['planRevision'] < 1
            || ! $this->nonEmptyString($status['planSha256'])
            || ! $this->nonEmptyString($status['orchestratorId'])
            || ! in_array($status['overallState'], ['draft', 'active', 'blocked', 'complete', 'superseded'], true)
            || ! in_array($status['currentStage'], self::STAGES, true)
            || (! is_null($status['supersededBy']) && ! $this->nonEmptyString($status['supersededBy']))
            || ! $this->nonEmptyString($status['updatedAt'])) {
            $this->error('FPV-JSON-004', $path, '', 'STATUS scalar has the wrong JSON type or enum.');
            $valid = false;
        }

        $stages = $this->listValue($status['stages'], $path, '/stages', true);
        $tasks = $this->listValue($status['tasks'], $path, '/tasks');
        $writers = $this->listValue($status['activeWriters'], $path, '/activeWriters');
        $evidence = $this->listValue($status['evidence'], $path, '/evidence');
        $gates = $this->listValue($status['gateResults'], $path, '/gateResults');
        $reviews = $this->listValue($status['reviews'], $path, '/reviews');
        $blockers = $this->listValue($status['blockers'], $path, '/blockers');
        if ($stages === null || $tasks === null || $writers === null || $evidence === null
            || $gates === null || $reviews === null || $blockers === null) {
            return false;
        }

        foreach ($stages as $index => $stage) {
            $pointer = '/stages/'.$index;
            if (! is_array($stage) || array_is_list($stage)
                || ! $this->closed($stage, ['id', 'state'], $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! in_array($stage['id'], self::STAGES, true)
                || ! in_array($stage['state'], ['pending', 'in_progress', 'blocked', 'complete'], true)) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Stage status has the wrong enum.');
                $valid = false;
            }
        }

        foreach ($tasks as $index => $task) {
            $pointer = '/tasks/'.$index;
            if (! is_array($task) || array_is_list($task)
                || ! $this->closed($task, [
                    'id', 'state', 'executorId', 'usedSkills', 'unavailableSkills', 'skippedSkills',
                    'evidenceIds', 'handoffEvidenceId',
                ], $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($task['id'])
                || ! in_array($task['state'], ['pending', 'in_progress', 'blocked', 'complete'], true)
                || (! is_null($task['executorId']) && ! $this->nonEmptyString($task['executorId']))
                || (! is_null($task['handoffEvidenceId']) && ! $this->nonEmptyString($task['handoffEvidenceId']))) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Task status has the wrong JSON type or enum.');
                $valid = false;
            }
            foreach (['usedSkills', 'evidenceIds'] as $key) {
                $values = $this->listValue($task[$key], $path, $pointer.'/'.$key);
                if ($values === null || ! $this->stringList($values)) {
                    $this->error('FPV-JSON-004', $path, $pointer.'/'.$key, 'Task status list is invalid.');
                    $valid = false;
                }
            }
            foreach (['unavailableSkills', 'skippedSkills'] as $key) {
                $values = $this->listValue($task[$key], $path, $pointer.'/'.$key);
                if ($values === null || ! $this->validateReasonObjects($values, $path, $pointer.'/'.$key)) {
                    $valid = false;
                }
            }
        }

        foreach ($writers as $index => $writer) {
            $pointer = '/activeWriters/'.$index;
            if (! is_array($writer) || array_is_list($writer)
                || ! $this->closed($writer, ['writerId', 'taskIds'], $path, $pointer)) {
                $valid = false;

                continue;
            }
            $taskIds = $this->listValue($writer['taskIds'], $path, $pointer.'/taskIds', true);
            if (! $this->nonEmptyString($writer['writerId']) || $taskIds === null || ! $this->stringList($taskIds, true)) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Active writer has the wrong JSON type.');
                $valid = false;
            }
        }

        foreach ($evidence as $index => $item) {
            $pointer = '/evidence/'.$index;
            $evidenceKeys = [
                'id', 'stage', 'taskId', 'checkId', 'kind', 'path', 'sha256', 'command', 'exitCode', 'observedAt',
            ];
            if ($protocolVersion === self::PROTOCOL_V2) {
                $evidenceKeys[] = 'gateId';
                $evidenceKeys[] = 'verifiedManifestSha256';
            }
            if (! is_array($item) || array_is_list($item)
                || ! $this->closed($item, $evidenceKeys, $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($item['id'])
                || ! in_array($item['stage'], self::STAGES, true)
                || (! is_null($item['taskId']) && ! $this->nonEmptyString($item['taskId']))
                || (! is_null($item['checkId']) && ! $this->nonEmptyString($item['checkId']))
                || ! in_array($item['kind'], ['analysis', 'baseline', 'red', 'green', 'qa', 'gate', 'snapshot', 'handoff'], true)
                || ! $this->nonEmptyString($item['path'])
                || ! $this->nonEmptyString($item['sha256'])
                || ! $this->nonEmptyString($item['command'])
                || ! is_int($item['exitCode'])
                || ! $this->nonEmptyString($item['observedAt'])
                || ($protocolVersion === self::PROTOCOL_V2
                    && ((! is_null($item['gateId']) && ! $this->nonEmptyString($item['gateId']))
                        || (! is_null($item['verifiedManifestSha256']) && ! $this->nonEmptyString($item['verifiedManifestSha256']))))) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Evidence has the wrong JSON type or enum.');
                $valid = false;
            }
        }

        foreach ($gates as $index => $gate) {
            $pointer = '/gateResults/'.$index;
            if (! is_array($gate) || array_is_list($gate)
                || ! $this->closed($gate, ['id', 'state', 'evidenceId'], $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($gate['id'])
                || ! in_array($gate['state'], ['pending', 'passed', 'failed'], true)
                || (! is_null($gate['evidenceId']) && ! $this->nonEmptyString($gate['evidenceId']))) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Gate result has the wrong JSON type or enum.');
                $valid = false;
            }
        }

        if (! is_array($status['snapshot']) || array_is_list($status['snapshot'])
            || ! $this->closed($status['snapshot'], ['state', 'round', 'manifestPath', 'manifestSha256', 'bundleSha256'], $path, '/snapshot')) {
            $valid = false;
        } else {
            /** @var array<string, mixed> $snapshot */
            $snapshot = $status['snapshot'];
            if (! in_array($snapshot['state'], ['pending', 'frozen', 'invalidated'], true)
                || ! is_int($snapshot['round'])
                || $snapshot['round'] < 0
                || (! is_null($snapshot['manifestPath']) && ! $this->nonEmptyString($snapshot['manifestPath']))
                || (! is_null($snapshot['manifestSha256']) && ! $this->nonEmptyString($snapshot['manifestSha256']))
                || (! is_null($snapshot['bundleSha256']) && ! $this->nonEmptyString($snapshot['bundleSha256']))) {
                $this->error('FPV-JSON-004', $path, '/snapshot', 'Snapshot has the wrong JSON type or enum.');
                $valid = false;
            }
        }

        foreach ($reviews as $index => $review) {
            $pointer = '/reviews/'.$index;
            if (! is_array($review) || array_is_list($review)
                || ! $this->closed($review, [
                    'axisId', 'reviewerId', 'verdict', 'round', 'snapshotSha256', 'evidencePath', 'evidenceSha256',
                    'usedSkills', 'unavailableSkills', 'skippedSkills', 'b0', 'b1', 'b2',
                ], $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($review['axisId'])
                || (! is_null($review['reviewerId']) && ! $this->nonEmptyString($review['reviewerId']))
                || ! in_array($review['verdict'], ['pending', 'go', 'fix_required'], true)
                || ! is_int($review['round'])
                || $review['round'] < 0
                || (! is_null($review['snapshotSha256']) && ! $this->nonEmptyString($review['snapshotSha256']))
                || (! is_null($review['evidencePath']) && ! $this->nonEmptyString($review['evidencePath']))
                || (! is_null($review['evidenceSha256']) && ! $this->nonEmptyString($review['evidenceSha256']))
                || ! is_int($review['b0']) || $review['b0'] < 0
                || ! is_int($review['b1']) || $review['b1'] < 0
                || ! is_int($review['b2']) || $review['b2'] < 0) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Review has the wrong JSON type or enum.');
                $valid = false;
            }
            $used = $this->listValue($review['usedSkills'], $path, $pointer.'/usedSkills');
            if ($used === null || ! $this->stringList($used)) {
                $this->error('FPV-JSON-004', $path, $pointer.'/usedSkills', 'Review usedSkills is invalid.');
                $valid = false;
            }
            foreach (['unavailableSkills', 'skippedSkills'] as $key) {
                $values = $this->listValue($review[$key], $path, $pointer.'/'.$key);
                if ($values === null || ! $this->validateReasonObjects($values, $path, $pointer.'/'.$key)) {
                    $valid = false;
                }
            }
        }

        foreach ($blockers as $index => $blocker) {
            $pointer = '/blockers/'.$index;
            if (! is_array($blocker) || array_is_list($blocker)
                || ! $this->closed($blocker, ['id', 'taskId', 'reason', 'openedAt'], $path, $pointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($blocker['id'])
                || (! is_null($blocker['taskId']) && ! $this->nonEmptyString($blocker['taskId']))
                || ! $this->nonEmptyString($blocker['reason'])
                || ! $this->nonEmptyString($blocker['openedAt'])) {
                $this->error('FPV-JSON-004', $path, $pointer, 'Blocker has the wrong JSON type.');
                $valid = false;
            }
        }

        return $valid;
    }

    /** @param list<mixed> $values */
    private function validateReasonObjects(array $values, string $path, string $pointer): bool
    {
        $valid = true;
        foreach ($values as $index => $value) {
            $itemPointer = $pointer.'/'.$index;
            if (! is_array($value) || array_is_list($value)
                || ! $this->closed($value, ['id', 'reason'], $path, $itemPointer)) {
                $valid = false;

                continue;
            }
            if (! $this->nonEmptyString($value['id']) || ! $this->nonEmptyString($value['reason'])) {
                $this->error('FPV-JSON-004', $path, $itemPointer, 'Skill exception has the wrong JSON type.');
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * @param  Plan  $plan
     * @param  Status  $status
     */
    private function validatePlanSemantics(string $planPath, array $plan, array $status): void
    {
        $path = $planPath.'/PLAN.json';
        if (! in_array($plan['protocolVersion'], self::PROTOCOLS, true)) {
            $this->error('FPV-SYS-002', $path, '/protocolVersion', 'Unsupported planning protocol.');
        }

        $slug = $plan['slug'];
        $expectedId = $slug.'-fast-tdd';
        $supersedesValid = $plan['revision'] === 1
            ? $plan['supersedes'] === null
            : $this->validSupersedes($slug, $plan['revision'], $plan['supersedes']);
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) !== 1
            || $plan['planId'] !== $expectedId
            || basename($planPath) !== $expectedId
            || ! $supersedesValid
            || ! $this->isTimestamp($plan['createdAt'])) {
            $this->error('FPV-PLAN-001', $path, '', 'Plan identity, revision or supersession is invalid.');
        }

        $architecture = $plan['architecture'];
        $protocol = $plan['protocol'];
        $architectureFile = $this->root.'/ARCHITECTURE.md';
        $protocolPath = $plan['protocolVersion'] === self::PROTOCOL_V2
            ? 'plan/FAST_STRICT_V2.md'
            : 'plan/FAST_STRICT_V1.md';
        $protocolFile = $this->root.'/'.$protocolPath;
        $hashPattern = '/^[a-f0-9]{64}$/D';
        $terminal = in_array($status['overallState'], ['complete', 'superseded'], true);
        if ($architecture['path'] !== 'ARCHITECTURE.md'
            || preg_match($hashPattern, $architecture['sha256']) !== 1
            || $protocol['path'] !== $protocolPath
            || preg_match($hashPattern, $protocol['sha256']) !== 1
            || ! is_file($protocolFile)
            || (! $terminal && hash_file('sha256', $architectureFile) !== $architecture['sha256'])
            || hash_file('sha256', $protocolFile) !== $protocol['sha256']) {
            $this->error('FPV-PLAN-002', $path, '/architecture', 'Protocol or architecture reference/hash is invalid.');
        }

        $ruleIds = $architecture['ruleIds'];
        $architectureText = (string) file_get_contents($architectureFile);
        if (! $this->isSortedUnique($ruleIds) || $ruleIds === []) {
            $this->error('FPV-JSON-005', $path, '/architecture/ruleIds', 'Architecture rule IDs must be sorted and unique.');
        } else {
            foreach ($ruleIds as $index => $ruleId) {
                if (preg_match('/^ARCH-[A-Z0-9]+(?:-[A-Z0-9]+)*-[0-9]{3}$/D', $ruleId) !== 1
                    || (! $terminal && ! str_contains($architectureText, $ruleId))) {
                    $this->error('FPV-PLAN-002', $path, '/architecture/ruleIds/'.$index, 'Referenced architecture rule does not exist.');
                }
            }
        }

        $scope = $plan['scope'];
        foreach (['in', 'out'] as $key) {
            $values = $scope[$key];
            if (! $this->isUnique($values)) {
                $this->error('FPV-JSON-005', $path, '/scope/'.$key, 'Scope values must be unique.');
            }
        }

        $requirements = $plan['requirements'];
        $tasks = $plan['tasks'];
        $gates = $plan['gates'];
        $axes = $plan['reviewAxes'];

        $requirementIds = [];
        $acceptanceIds = [];
        foreach ($requirements as $index => $requirement) {
            if (preg_match('/^REQ-[0-9]{3}$/D', $requirement['id']) !== 1) {
                $this->error('FPV-JSON-004', $path, '/requirements/'.$index.'/id', 'Requirement ID format is invalid.');
            }
            $requirementIds[] = $requirement['id'];
            foreach ($requirement['acceptance'] as $acceptanceIndex => $acceptance) {
                if (preg_match('/^AC-[0-9]{3}$/D', $acceptance['id']) !== 1) {
                    $this->error('FPV-JSON-004', $path, '/requirements/'.$index.'/acceptance/'.$acceptanceIndex.'/id', 'Acceptance ID format is invalid.');
                }
                $acceptanceIds[] = $acceptance['id'];
            }
        }
        if (! $this->isUnique($requirementIds) || ! $this->isUnique($acceptanceIds)) {
            $this->error('FPV-JSON-005', $path, '/requirements', 'Requirement and acceptance IDs must be unique.');
        }

        $taskIds = array_column($tasks, 'id');
        $checkIds = [];
        if (! $this->isSortedUnique($taskIds)) {
            $this->error('FPV-JSON-005', $path, '/tasks', 'Task IDs must be sorted and unique.');
        }

        $referencedRequirements = [];
        $referencedAcceptance = [];
        $taskById = [];
        $stageZeroRoles = [];
        $stageOneTasks = [];
        foreach ($tasks as $index => $task) {
            $pointer = '/tasks/'.$index;
            $taskById[$task['id']] = $task;
            if (preg_match('/^TASK-[0-9]{3}$/D', $task['id']) !== 1) {
                $this->error('FPV-JSON-004', $path, $pointer.'/id', 'Task ID format is invalid.');
            }
            foreach ($task['checks'] as $checkIndex => $check) {
                $checkIds[] = $check['id'];
                if (preg_match('/^CHECK-[0-9]{3}$/D', $check['id']) !== 1) {
                    $this->error('FPV-JSON-004', $path, $pointer.'/checks/'.$checkIndex.'/id', 'Check ID format is invalid.');
                }
                if ($check['kind'] !== $task['verificationMode']) {
                    $this->error('FPV-PLAN-004', $path, $pointer.'/checks/'.$checkIndex.'/kind', 'Check kind must equal task verificationMode.');
                }
            }
            foreach ($task['requirementIds'] as $id) {
                $referencedRequirements[$id] = true;
                if (! in_array($id, $requirementIds, true)) {
                    $this->error('FPV-PLAN-003', $path, $pointer.'/requirementIds', 'Task references an unknown requirement.');
                }
            }
            foreach ($task['acceptanceIds'] as $id) {
                $referencedAcceptance[$id] = true;
                if (! in_array($id, $acceptanceIds, true)) {
                    $this->error('FPV-PLAN-003', $path, $pointer.'/acceptanceIds', 'Task references an unknown acceptance criterion.');
                }
            }
            foreach (['dependsOn', 'ownedPaths', 'createPaths', 'deletePaths', 'requirementIds', 'acceptanceIds'] as $listKey) {
                if (! $this->isSortedUnique($task[$listKey])) {
                    $this->error('FPV-JSON-005', $path, $pointer.'/'.$listKey, 'Set-like task list must be sorted and unique.');
                }
            }
            if (in_array($task['verificationMode'], ['tdd', 'contract'], true) && $task['stage'] !== '002') {
                $this->error('FPV-PLAN-004', $path, $pointer.'/stage', 'TDD and contract tasks start at Stage 002.');
            }
            if ($task['stage'] === '000') {
                $stageZeroRoles[] = $task['agentRole'];
                if ($task['executionMode'] !== 'read_only_subagent'
                    || $task['ownedPaths'] !== [] || $task['createPaths'] !== [] || $task['deletePaths'] !== []) {
                    $this->error('FPV-PLAN-004', $path, $pointer, 'Stage 000 tasks must be read-only subagents without write paths.');
                }
            }
            if ($task['stage'] === '001') {
                $stageOneTasks[] = $task;
                if ($task['executionMode'] !== 'orchestrator'
                    || $task['verificationMode'] !== 'inspection'
                    || count($task['checks']) !== 1
                    || $task['ownedPaths'] !== [] || $task['createPaths'] !== [] || $task['deletePaths'] !== []) {
                    $this->error('FPV-PLAN-004', $path, $pointer, 'Stage 001 permits one path-free orchestrator inspection task.');
                }
            }
            $this->validateTaskSkills($task, $path, $pointer, false);
            $this->validateTaskPaths($task, $path, $pointer);
        }

        if (! $this->isUnique($checkIds)) {
            $this->error('FPV-JSON-005', $path, '/tasks', 'Check IDs must be globally unique.');
        }
        foreach ($requirementIds as $id) {
            if (! isset($referencedRequirements[$id])) {
                $this->error('FPV-PLAN-003', $path, '/requirements', 'Every requirement must be referenced by a task.');
            }
        }
        foreach ($acceptanceIds as $id) {
            if (! isset($referencedAcceptance[$id])) {
                $this->error('FPV-PLAN-003', $path, '/requirements', 'Every acceptance criterion must be referenced by a task.');
            }
        }
        foreach (['business_system', 'architecture_codebase', 'qa_risk'] as $role) {
            if (! in_array($role, $stageZeroRoles, true)) {
                $this->error('FPV-PLAN-004', $path, '/tasks', 'Stage 000 is missing one fixed independent role.');
            }
        }
        if (count($stageOneTasks) > 1) {
            $this->error('FPV-PLAN-004', $path, '/tasks', 'Stage 001 permits at most one preflight/baseline task.');
        }

        $this->validateTaskGraph($tasks, $taskById, $path);
        $this->validateLeaseOverlap($tasks, $taskById, $path);
        $this->validateGates($gates, $path, $plan['protocolVersion']);
        $this->validateReviewAxes($axes, $path);
    }

    /** @param Task|ReviewAxis $task */
    private function validateTaskSkills(array $task, string $path, string $pointer, bool $review): void
    {
        $ids = [];
        $moments = ['analysis', 'design', 'red', 'green', 'refactor', 'verification', 'review', 'handoff'];
        foreach ($task['skills'] as $index => $skill) {
            $ids[] = $skill['id'];
            if (! $this->isUnique($skill['applyAt'])
                || array_diff($skill['applyAt'], $moments) !== []
                || ($review && $skill['applyAt'] !== ['review'])) {
                $this->error($review ? 'FPV-PLAN-007' : 'FPV-PLAN-006', $path, $pointer.'/skills/'.$index, 'Skill application moments are invalid.');
            }
        }
        $required = $review
            ? ['ponytail:ponytail', 'ponytail:ponytail-review']
            : ['ponytail:ponytail'];
        if (! $this->isUnique($ids) || array_diff($required, $ids) !== []) {
            $this->error($review ? 'FPV-PLAN-007' : 'FPV-PLAN-006', $path, $pointer.'/skills', 'Exact skill IDs are duplicate or miss a required Ponytail skill.');
        }
    }

    /** @param Task $task */
    private function validateTaskPaths(array $task, string $path, string $pointer): void
    {
        $owned = $task['ownedPaths'];
        if (array_diff($task['createPaths'], $owned) !== []
            || array_diff($task['deletePaths'], $owned) !== []
            || array_intersect($task['createPaths'], $task['deletePaths']) !== []) {
            $this->error('FPV-PLAN-005', $path, $pointer, 'Create/delete paths must be disjoint subsets of ownedPaths.');
        }
        foreach ($owned as $index => $ownedPath) {
            $code = $this->pathViolation($ownedPath);
            if ($code !== null) {
                $this->error($code, $path, $pointer.'/ownedPaths/'.$index, 'Owned path is unsafe or escapes the repository.');
            }
            if (str_starts_with($ownedPath, 'plan/')) {
                $this->error('FPV-PLAN-005', $path, $pointer.'/ownedPaths/'.$index, 'Plan control paths cannot be leased.');
            }
        }
    }

    /**
     * @param  list<Task>  $tasks
     * @param  array<string, Task>  $taskById
     */
    private function validateTaskGraph(array $tasks, array $taskById, string $path): void
    {
        $hasGraphError = false;
        foreach ($tasks as $index => $task) {
            foreach ($task['dependsOn'] as $dependency) {
                if (! isset($taskById[$dependency])) {
                    $hasGraphError = true;

                    continue;
                }
                $dependencyTask = $taskById[$dependency];
                if ($dependencyTask['stage'] > $task['stage']
                    || ($dependencyTask['stage'] === $task['stage'] && $dependency >= $task['id'])) {
                    $hasGraphError = true;
                }
            }
            if (in_array($task['id'], $task['dependsOn'], true)) {
                $hasGraphError = true;
            }
        }

        $visiting = [];
        $visited = [];
        $visit = function (string $id) use (&$visit, &$visiting, &$visited, $taskById, &$hasGraphError): void {
            if (isset($visiting[$id])) {
                $hasGraphError = true;

                return;
            }
            if (isset($visited[$id]) || ! isset($taskById[$id])) {
                return;
            }
            $visiting[$id] = true;
            foreach ($taskById[$id]['dependsOn'] as $dependency) {
                $visit($dependency);
            }
            unset($visiting[$id]);
            $visited[$id] = true;
        };
        foreach (array_keys($taskById) as $id) {
            $visit($id);
        }
        if ($hasGraphError) {
            $this->error('FPV-PLAN-004', $path, '/tasks', 'Task dependency graph or stage order is invalid.');
        }
    }

    /**
     * @param  list<Task>  $tasks
     * @param  array<string, Task>  $taskById
     */
    private function validateLeaseOverlap(array $tasks, array $taskById, string $path): void
    {
        $owner = [];
        foreach ($tasks as $task) {
            foreach ($task['ownedPaths'] as $ownedPath) {
                if (! isset($owner[$ownedPath])) {
                    $owner[$ownedPath] = $task['id'];

                    continue;
                }
                $previous = $taskById[$owner[$ownedPath]];
                $serialized = in_array($previous['id'], $task['dependsOn'], true)
                    || in_array($task['id'], $previous['dependsOn'], true);
                if (! $serialized || $task['executionMode'] !== 'orchestrator' || $previous['executionMode'] !== 'orchestrator') {
                    $this->error('FPV-PLAN-005', $path, '/tasks', 'Write leases overlap without an allowed orchestrator serialization.');
                }
            }
        }
    }

    /** @param list<Gate> $gates */
    private function validateGates(array $gates, string $path, string $protocolVersion): void
    {
        $ids = array_column($gates, 'id');
        $evidenceIds = $protocolVersion === self::PROTOCOL_V1 ? array_column($gates, 'evidenceId') : [];
        if (! $this->isSortedUnique($ids) || ($protocolVersion === self::PROTOCOL_V1 && ! $this->isUnique($evidenceIds))) {
            $this->error('FPV-JSON-005', $path, '/gates', 'Gate and gate evidence IDs must be unique and ordered.');
        }
        $expected = [
            'GATE-PRECOMMIT' => ['005', 'make pre-commit', 'EVID-GATE-PRECOMMIT'],
            'GATE-QUALITY' => ['005', 'make quality', 'EVID-GATE-QUALITY'],
        ];
        foreach ($gates as $index => $gate) {
            if (preg_match('/^GATE-[A-Z0-9]+(?:-[A-Z0-9]+)*$/D', $gate['id']) !== 1
                || ($protocolVersion === self::PROTOCOL_V1
                    && preg_match('/^EVID-[A-Z0-9]+(?:-[A-Z0-9]+)*$/D', (string) ($gate['evidenceId'] ?? '')) !== 1)
                || ! str_starts_with($gate['command'], 'make ')) {
                $this->error('FPV-PLAN-008', $path, '/gates/'.$index, 'Gate command or evidence mapping is invalid.');
            }
        }
        foreach ($expected as $id => [$stage, $command, $evidenceId]) {
            $match = array_find($gates, fn ($gate) => $gate['id'] === $id);
            if ($match === null || $match['stage'] !== $stage || $match['command'] !== $command
                || ($protocolVersion === self::PROTOCOL_V1 && ($match['evidenceId'] ?? null) !== $evidenceId)) {
                $this->error('FPV-PLAN-008', $path, '/gates', 'Required Jobgram quality gate is missing or changed.');
            }
        }
    }

    /** @param list<ReviewAxis> $axes */
    private function validateReviewAxes(array $axes, string $path): void
    {
        if (array_column($axes, 'id') !== self::REVIEW_AXES) {
            $this->error('FPV-PLAN-007', $path, '/reviewAxes', 'Review axes must use the three fixed IDs in canonical order.');
        }
        foreach ($axes as $index => $axis) {
            $id = $axis['id'];
            if (! isset(self::REVIEW_TITLES[$id]) || $axis['title'] !== self::REVIEW_TITLES[$id]) {
                $this->error('FPV-PLAN-007', $path, '/reviewAxes/'.$index.'/title', 'Review axis title is not canonical.');
            }
            $this->validateTaskSkills($axis, $path, '/reviewAxes/'.$index, true);
        }
    }

    private function pathViolation(string $path): ?string
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '\\')
            || str_contains($path, '//') || preg_match('/[^\x20-\x7E]|[*?]/', $path) === 1) {
            return 'FPV-PATH-001';
        }
        $segments = explode('/', $path);
        if (in_array('.', $segments, true) || in_array('..', $segments, true)) {
            return 'FPV-PATH-001';
        }
        foreach ($segments as $segment) {
            if ((str_contains($segment, '[') || str_contains($segment, ']'))
                && (! str_starts_with($path, 'frontend/src/app/')
                    || preg_match('/^\[[A-Za-z][A-Za-z0-9_]*\]$/D', $segment) !== 1)) {
                return 'FPV-PATH-001';
            }
        }

        $probe = $this->root;
        foreach ($segments as $segment) {
            $probe .= '/'.$segment;
            if (is_link($probe)) {
                $resolved = realpath($probe);
                if ($resolved !== false && $resolved !== $this->root && ! str_starts_with($resolved, $this->root.'/')) {
                    return 'FPV-PATH-002';
                }

                return 'FPV-PATH-003';
            }
            if (! file_exists($probe)) {
                break;
            }
            $resolved = realpath($probe);
            if ($resolved !== false && $resolved !== $this->root && ! str_starts_with($resolved, $this->root.'/')) {
                return 'FPV-PATH-002';
            }
        }

        return null;
    }

    /** @param list<string> $values */
    private function isUnique(array $values): bool
    {
        return count($values) === count(array_unique($values, SORT_REGULAR));
    }

    /** @param list<string> $values */
    private function isSortedUnique(array $values): bool
    {
        if (! $this->isUnique($values)) {
            return false;
        }
        $sorted = $values;
        sort($sorted, SORT_STRING);

        return $values === $sorted;
    }

    private function isTimestamp(string $value): bool
    {
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/D', $value) !== 1) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d\\TH:i:s\\Z', $value, new DateTimeZone('UTC'));
        $errors = DateTimeImmutable::getLastErrors();

        return $date !== false
            && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
            && $date->format('Y-m-d\\TH:i:s\\Z') === $value;
    }

    /**
     * @param  Plan  $plan
     * @param  Status  $status
     */
    private function validateStatusSemantics(string $planPath, array $plan, array $status, string $phase): void
    {
        $statusPath = $planPath.'/STATUS.json';
        $planFile = $this->root.'/'.$planPath.'/PLAN.json';
        $fatal = false;
        if ($status['protocolVersion'] !== $plan['protocolVersion']
            || $status['planId'] !== $plan['planId']
            || $status['planRevision'] !== $plan['revision']
            || preg_match('/^[a-f0-9]{64}$/D', $status['planSha256']) !== 1
            || hash_file('sha256', $planFile) !== $status['planSha256']) {
            $this->error('FPV-STATUS-001', $statusPath, '', 'STATUS identity, revision or frozen PLAN hash does not match.');
            $fatal = true;
        }
        if (! $this->isTimestamp($status['updatedAt'])) {
            $this->error('FPV-JSON-004', $statusPath, '/updatedAt', 'updatedAt must be an RFC 3339 UTC timestamp.');
        }
        if ($status['overallState'] === 'superseded'
            && ! $this->hasValidSuccessor($plan, $status['supersededBy'])) {
            $this->error('FPV-STATUS-002', $statusPath, '/supersededBy', 'Supersession must link to the existing next canonical revision in both directions.');
            $fatal = true;
        }

        if (! $this->validateStageState($status, $statusPath)) {
            $fatal = true;
        }

        $tasks = $plan['tasks'];
        $taskStatuses = $status['tasks'];
        $evidence = $status['evidence'];

        $taskById = [];
        foreach ($tasks as $task) {
            $taskById[$task['id']] = $task;
        }
        $statusByTask = [];
        foreach ($taskStatuses as $taskStatus) {
            $statusByTask[$taskStatus['id']] = $taskStatus;
        }
        if (array_column($taskStatuses, 'id') !== array_column($tasks, 'id')) {
            $this->error('FPV-STATUS-003', $statusPath, '/tasks', 'STATUS tasks must be an exact ordered projection of PLAN tasks.');
            $fatal = true;
        }
        if ($fatal) {
            return;
        }

        $evidenceById = [];
        foreach ($evidence as $item) {
            $evidenceById[$item['id']] = $item;
        }
        $evidenceIds = array_column($evidence, 'id');
        $validEvidenceIds = $plan['protocolVersion'] === self::PROTOCOL_V2
            ? $this->isUnique($evidenceIds)
            : $this->isSortedUnique($evidenceIds);
        if (! $validEvidenceIds) {
            $this->error('FPV-JSON-005', $statusPath, '/evidence', 'Evidence IDs must be unique and use the protocol order.');
        }

        foreach ($taskStatuses as $index => $taskStatus) {
            $pointer = '/tasks/'.$index;
            if (! isset($taskById[$taskStatus['id']])) {
                continue;
            }
            $task = $taskById[$taskStatus['id']];
            $plannedSkills = array_column($task['skills'], 'id');
            $orderedUsedSkills = array_values(array_filter(
                $plannedSkills,
                static fn (string $skillId): bool => in_array($skillId, $taskStatus['usedSkills'], true),
            ));
            if (! $this->isUnique($taskStatus['usedSkills'])
                || array_diff($taskStatus['usedSkills'], $plannedSkills) !== []
                || $taskStatus['usedSkills'] !== $orderedUsedSkills) {
                $this->error('FPV-STATUS-004', $statusPath, $pointer.'/usedSkills', 'Used skills must be exact PLAN skill IDs.');
            }
            $unavailableIds = array_column($taskStatus['unavailableSkills'], 'id');
            $skippedIds = array_column($taskStatus['skippedSkills'], 'id');
            if (! $this->isUnique($unavailableIds) || ! $this->isUnique($skippedIds)
                || array_diff($unavailableIds, $plannedSkills) !== []
                || array_diff($skippedIds, $plannedSkills) !== []
                || array_intersect($taskStatus['usedSkills'], $unavailableIds) !== []
                || array_intersect($taskStatus['usedSkills'], $skippedIds) !== []
                || array_intersect($unavailableIds, $skippedIds) !== []) {
                $this->error('FPV-STATUS-004', $statusPath, $pointer, 'Skill ledgers must be disjoint unique PLAN skill IDs.');
            }
            if ($taskStatus['unavailableSkills'] !== [] || $taskStatus['skippedSkills'] !== []) {
                if ($taskStatus['state'] !== 'blocked' || $status['overallState'] !== 'blocked') {
                    $this->error('FPV-STATUS-004', $statusPath, $pointer, 'Unavailable or skipped required skill must block execution.');
                }
            }
            if (! $this->isSortedUnique($taskStatus['evidenceIds'])) {
                $this->error('FPV-JSON-005', $statusPath, $pointer.'/evidenceIds', 'Task evidence IDs must be sorted and unique.');
            }
            foreach ($taskStatus['evidenceIds'] as $evidenceId) {
                if (! isset($evidenceById[$evidenceId])) {
                    $this->error('FPV-STATUS-003', $statusPath, $pointer.'/evidenceIds', 'Task references missing evidence.');
                } elseif ($evidenceById[$evidenceId]['taskId'] !== $task['id']) {
                    $this->error('FPV-STATUS-003', $statusPath, $pointer.'/evidenceIds', 'Task evidence must belong to that task.');
                }
            }
            if ($taskStatus['state'] === 'pending'
                && ($taskStatus['executorId'] !== null || $taskStatus['usedSkills'] !== []
                    || $taskStatus['evidenceIds'] !== [] || $taskStatus['handoffEvidenceId'] !== null)) {
                $this->error('FPV-STATUS-003', $statusPath, $pointer, 'Pending task cannot prefill execution evidence.');
            }
            if ($taskStatus['state'] !== 'pending' && $taskStatus['executorId'] === null) {
                $this->error('FPV-STATUS-003', $statusPath, $pointer.'/executorId', 'Started task requires an executor.');
            }
            if ($taskStatus['state'] !== 'pending') {
                foreach ($task['dependsOn'] as $dependency) {
                    if (! isset($statusByTask[$dependency]) || $statusByTask[$dependency]['state'] !== 'complete') {
                        $this->error('FPV-STATUS-003', $statusPath, $pointer, 'Started task requires all dependencies to be complete.');
                        break;
                    }
                }
            }
            if ($taskStatus['state'] === 'complete') {
                if ($taskStatus['executorId'] === null
                    || $taskStatus['usedSkills'] !== $plannedSkills
                    || $taskStatus['unavailableSkills'] !== []
                    || $taskStatus['skippedSkills'] !== []) {
                    $this->error('FPV-STATUS-003', $statusPath, $pointer, 'Complete task lacks executor or exact skill evidence.');
                }
                foreach ($task['checks'] as $check) {
                    $hasCheckEvidence = false;
                    foreach ($taskStatus['evidenceIds'] as $evidenceId) {
                        if (isset($evidenceById[$evidenceId]) && $evidenceById[$evidenceId]['checkId'] === $check['id']) {
                            $hasCheckEvidence = true;
                            break;
                        }
                    }
                    if (! $hasCheckEvidence) {
                        $this->error('FPV-STATUS-003', $statusPath, $pointer.'/evidenceIds', 'Complete task lacks evidence for a required check.');
                    }
                }
                if ($status['overallState'] === 'complete'
                    && ($taskStatus['handoffEvidenceId'] === null
                        || ! isset($evidenceById[$taskStatus['handoffEvidenceId']])
                        || ! in_array($taskStatus['handoffEvidenceId'], $taskStatus['evidenceIds'], true)
                        || $evidenceById[$taskStatus['handoffEvidenceId']]['kind'] !== 'handoff'
                        || $evidenceById[$taskStatus['handoffEvidenceId']]['stage'] !== '007'
                        || $evidenceById[$taskStatus['handoffEvidenceId']]['taskId'] !== $task['id'])) {
                    $this->error('FPV-COMPLETE-001', $statusPath, $pointer, 'Stage 007 requires handoff evidence for every task.');
                }
            }
        }

        foreach ($evidence as $index => $item) {
            if ($item['taskId'] !== null && isset($statusByTask[$item['taskId']])
                && ! in_array($item['id'], $statusByTask[$item['taskId']]['evidenceIds'], true)) {
                $this->error('FPV-STATUS-003', $statusPath, '/evidence/'.$index, 'Task evidence must be listed by its STATUS task.');
            }
        }

        if (! in_array($status['overallState'], ['complete', 'superseded'], true)) {
            foreach ($taskStatuses as $index => $taskStatus) {
                $task = $taskById[$taskStatus['id']] ?? null;
                if ($task !== null && $task['stage'] > $status['currentStage'] && $taskStatus['state'] !== 'pending') {
                    $this->error('FPV-STATUS-003', $statusPath, '/tasks/'.$index, 'Future-stage task cannot prefill execution state.');
                }
            }
            foreach ($evidence as $index => $item) {
                if ($item['stage'] > $status['currentStage']) {
                    $this->error('FPV-STATUS-003', $statusPath, '/evidence/'.$index, 'Future-stage evidence cannot be prefilled.');
                }
            }
        }

        $this->validateActiveWriters($planPath, $taskById, $statusByTask, $status, $statusPath);
        $this->validateBlockers($status, $taskById, $statusPath);
        $readinessIndex = $this->readinessIndex($status, $phase);
        $this->validateEvidence($planPath, $plan, $status, $taskById, $evidenceById, $statusPath, $readinessIndex);
        $this->validateGateResults($plan, $status, $evidenceById, $statusPath);
        $this->validateManifests($planPath, $plan, $status, $evidence, $phase);
        $reviewErrorCount = count($this->errors);
        $this->validateReviews($planPath, $plan, $status, $taskStatuses, $statusPath);
        $reviewsValid = count($this->errors) === $reviewErrorCount;
        $this->validatePhaseReadiness($plan, $status, $taskById, $statusByTask, $evidenceById, $statusPath, $readinessIndex, $phase, $reviewsValid);
        if ($reviewsValid) {
            $this->validateCompletion($status, $statusPath);
        }
    }

    /** @param Status $status */
    private function readinessIndex(array $status, string $phase): int
    {
        if ($phase !== 'current') {
            $index = array_search($phase, self::STAGES, true);

            return is_int($index) ? $index : -1;
        }
        if ($status['overallState'] === 'complete') {
            return 7;
        }
        $current = array_search($status['currentStage'], self::STAGES, true);

        return is_int($current) ? $current - 1 : -1;
    }

    /**
     * @param  Plan  $plan
     * @param  Status  $status
     * @param  array<string, Task>  $taskById
     * @param  array<string, StatusTask>  $statusByTask
     * @param  array<string, Evidence>  $evidenceById
     */
    private function validatePhaseReadiness(
        array $plan,
        array $status,
        array $taskById,
        array $statusByTask,
        array $evidenceById,
        string $statusPath,
        int $readinessIndex,
        string $phase,
        bool $reviewsValid,
    ): void {
        if ($readinessIndex < 0) {
            return;
        }

        $stageZeroExecutors = [];
        $stageZeroValid = true;
        foreach ($plan['tasks'] as $task) {
            if ($task['stage'] !== '000') {
                continue;
            }
            $taskStatus = $statusByTask[$task['id']] ?? null;
            if ($taskStatus === null || $taskStatus['state'] !== 'complete' || $taskStatus['executorId'] === null) {
                $stageZeroValid = false;

                continue;
            }
            $stageZeroExecutors[] = $taskStatus['executorId'];
            $hasAnalysis = false;
            foreach ($taskStatus['evidenceIds'] as $evidenceId) {
                $item = $evidenceById[$evidenceId] ?? null;
                if ($item !== null && $item['kind'] === 'analysis' && $item['stage'] === '000'
                    && $item['taskId'] === $task['id'] && $item['exitCode'] === 0) {
                    $hasAnalysis = true;
                    break;
                }
            }
            if (! $hasAnalysis) {
                $stageZeroValid = false;
            }
        }
        if (! $stageZeroValid || ! $this->isUnique($stageZeroExecutors)) {
            $this->error('FPV-STATUS-003', $statusPath, '/tasks', 'Stage 000 closure requires distinct executors and analysis evidence.');
        }

        foreach ($taskById as $taskId => $task) {
            $taskStage = array_search($task['stage'], self::STAGES, true);
            if (! is_int($taskStage) || $taskStage > $readinessIndex) {
                continue;
            }
            $state = $statusByTask[$taskId]['state'] ?? null;
            if (in_array($task['verificationMode'], ['tdd', 'contract'], true)) {
                $expected = $readinessIndex >= 4 ? 'complete' : 'in_progress';
            } else {
                $expected = 'complete';
            }
            if ($state !== $expected) {
                $this->error('FPV-STATUS-003', $statusPath, '/tasks', 'Closed phase has an invalid task lifecycle state.');
            }
        }

        if ($phase === '001' && $status['activeWriters'] !== []) {
            $this->error('FPV-STATUS-005', $statusPath, '/activeWriters', 'Stage 001 closure requires no active writers.');
        }

        if ($readinessIndex >= 5) {
            if (count($status['gateResults']) !== count($plan['gates'])
                || in_array(false, array_map(
                    static fn (array $result): bool => $result['state'] === 'passed',
                    $status['gateResults'],
                ), true)) {
                $this->error('FPV-EVID-005', $statusPath, '/gateResults', 'Stage 005 closure requires every fixed gate to pass.');
            }
        }

        if ($readinessIndex >= 6 && $reviewsValid) {
            $snapshot = $status['snapshot'];
            $reviews = $status['reviews'];
            $reviewsReady = $snapshot['state'] === 'frozen'
                && is_string($snapshot['bundleSha256'])
                && count($reviews) === 3
                && array_column($reviews, 'axisId') === self::REVIEW_AXES;
            foreach ($reviews as $review) {
                $reviewsReady = $reviewsReady
                    && $review['verdict'] === 'go'
                    && $review['round'] === $snapshot['round']
                    && $review['snapshotSha256'] === $snapshot['bundleSha256'];
            }
            if (! $reviewsReady) {
                $this->error('FPV-REVIEW-003', $statusPath, '/reviews', 'Stage 006 closure requires three GO reviews of one frozen snapshot.');
            }
        }

        if ($readinessIndex >= 7 && $status['overallState'] !== 'complete') {
            $this->error('FPV-COMPLETE-001', $statusPath, '', 'Stage 007 closure requires complete state and valid task handoffs.');
        }
    }

    /** @param Status $status */
    private function validateStageState(array $status, string $path): bool
    {
        $stages = $status['stages'];
        if (count($stages) !== 8 || array_column($stages, 'id') !== self::STAGES) {
            $this->error('FPV-STATUS-002', $path, '/stages', 'STATUS must contain stages 000 through 007 in order.');

            return false;
        }

        $state = $status['overallState'];
        $current = $status['currentStage'];
        $currentIndex = array_search($current, self::STAGES, true);
        $valid = is_int($currentIndex);
        if ($valid && in_array($state, ['draft', 'active'], true)) {
            foreach ($stages as $index => $stage) {
                $expected = $index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'in_progress' : 'pending');
                if ($stage['state'] !== $expected) {
                    $valid = false;
                }
            }
            if ($state === 'draft' && $current !== '000') {
                $valid = false;
            }
        } elseif ($state === 'complete') {
            $valid = $current === '007';
            foreach ($stages as $stage) {
                $valid = $valid && $stage['state'] === 'complete';
            }
        } elseif ($state === 'superseded') {
            $valid = $status['supersededBy'] !== null;
        } elseif ($state === 'blocked') {
            foreach ($stages as $index => $stage) {
                $expected = $index < $currentIndex ? 'complete' : ($index === $currentIndex ? 'blocked' : 'pending');
                if ($stage['state'] !== $expected) {
                    $valid = false;
                }
            }
        }

        if ($state !== 'superseded' && $status['supersededBy'] !== null) {
            $valid = false;
        }
        if (! $valid) {
            $this->error('FPV-STATUS-002', $path, '/stages', 'Stage progression, currentStage or overallState is inconsistent.');
        }

        return $valid;
    }

    /**
     * @param  array<string, Task>  $taskById
     * @param  array<string, StatusTask>  $statusByTask
     * @param  Status  $status
     */
    private function validateActiveWriters(
        string $planPath,
        array $taskById,
        array $statusByTask,
        array $status,
        string $statusPath,
    ): void {
        $writers = $status['activeWriters'];
        $invalid = count($writers) > 3 || ! $this->isUnique(array_column($writers, 'writerId'));
        $leasedPaths = [];
        foreach ($writers as $writer) {
            foreach ($writer['taskIds'] as $taskId) {
                if (! isset($taskById[$taskId], $statusByTask[$taskId])) {
                    $invalid = true;

                    continue;
                }
                $task = $taskById[$taskId];
                $taskStatus = $statusByTask[$taskId];
                if ($task['executionMode'] !== 'writer'
                    || $taskStatus['state'] !== 'in_progress'
                    || $taskStatus['executorId'] !== $writer['writerId']) {
                    $invalid = true;
                }
                foreach ($task['dependsOn'] as $dependency) {
                    if (! isset($statusByTask[$dependency]) || $statusByTask[$dependency]['state'] !== 'complete') {
                        $invalid = true;
                    }
                }
                foreach ($task['ownedPaths'] as $ownedPath) {
                    if (isset($leasedPaths[$ownedPath]) && $leasedPaths[$ownedPath] !== $writer['writerId']) {
                        $invalid = true;
                    }
                    $leasedPaths[$ownedPath] = $writer['writerId'];
                }
            }
        }
        if ($invalid) {
            $this->error('FPV-STATUS-005', $statusPath, '/activeWriters', 'Active writer count, executor, dependency or lease is invalid.');
        }
        if (count($writers) >= 2 && ! is_file($this->root.'/'.$planPath.'/OWNERSHIP.md')) {
            $this->error('FPV-FILE-001', $planPath.'/OWNERSHIP.md', '', 'OWNERSHIP.md is required for two or more active writers.');
        }
    }

    /**
     * @param  Status  $status
     * @param  array<string, Task>  $taskById
     */
    private function validateBlockers(array $status, array $taskById, string $path): void
    {
        $blocked = $status['overallState'] === 'blocked';
        $hasBlockedItem = in_array('blocked', array_column($status['stages'], 'state'), true)
            || in_array('blocked', array_column($status['tasks'], 'state'), true);
        if ($blocked !== ($status['blockers'] !== [] && $hasBlockedItem)) {
            $this->error('FPV-STATUS-006', $path, '/blockers', 'Blocked state must have an actionable blocker and blocked task or stage.');
        }
        $ids = array_column($status['blockers'], 'id');
        if (! $this->isUnique($ids)) {
            $this->error('FPV-JSON-005', $path, '/blockers', 'Blocker IDs must be unique.');
        }
        foreach ($status['blockers'] as $index => $blocker) {
            if (preg_match('/^BLOCK-[0-9]{3}$/D', $blocker['id']) !== 1
                || ! $this->isTimestamp($blocker['openedAt'])
                || ($blocker['taskId'] !== null && ! isset($taskById[$blocker['taskId']]))) {
                $this->error('FPV-STATUS-006', $path, '/blockers/'.$index, 'Blocker ID, timestamp or task reference is invalid.');
            }
        }
    }

    /**
     * @param  Plan  $plan
     * @param  Status  $status
     * @param  array<string, Task>  $taskById
     * @param  array<string, Evidence>  $evidenceById
     */
    private function validateEvidence(
        string $planPath,
        array $plan,
        array $status,
        array $taskById,
        array $evidenceById,
        string $statusPath,
        int $readinessIndex,
    ): void {
        $v2 = $plan['protocolVersion'] === self::PROTOCOL_V2;
        $gatesById = [];
        foreach ($plan['gates'] as $gate) {
            $gatesById[$gate['id']] = $gate;
        }
        foreach ($status['evidence'] as $index => $item) {
            $pointer = '/evidence/'.$index;
            if (preg_match('/^EVID-[A-Z0-9]+(?:-[A-Z0-9]+)*$/D', $item['id']) !== 1
                || ! $this->isTimestamp($item['observedAt'])) {
                $this->error('FPV-EVID-001', $statusPath, $pointer, 'Evidence ID or observation timestamp is invalid.');
            }

            $pathCode = $this->pathViolation($item['path']);
            $allowedPath = str_starts_with($item['path'], $planPath.'/evidence/')
                && (preg_match('#^'.preg_quote($planPath, '#').'/evidence/EVID-[A-Z0-9]+(?:-[A-Z0-9]+)*\.md$#D', $item['path']) === 1
                    || $item['path'] === $planPath.'/evidence/baseline.sha256');
            $absolute = $this->root.'/'.$item['path'];
            $contents = $pathCode === null && $allowedPath && is_file($absolute)
                ? file_get_contents($absolute)
                : false;
            $invalidMarkdown = str_ends_with($item['path'], '.md')
                && (! is_string($contents)
                    || trim($contents) === ''
                    || ! mb_check_encoding($contents, 'UTF-8')
                    || ! $this->evidenceMarkdownComplete($contents, $item));
            if ($pathCode !== null || ! $allowedPath || ! is_file($absolute)
                || preg_match('/^[a-f0-9]{64}$/D', $item['sha256']) !== 1
                || hash_file('sha256', $absolute) !== $item['sha256']
                || $invalidMarkdown) {
                $this->error('FPV-EVID-002', $statusPath, $pointer.'/path', 'Evidence path, file or hash is invalid.');
            }

            $task = null;
            $check = null;
            if ($item['taskId'] !== null) {
                if (! isset($taskById[$item['taskId']])) {
                    $this->error('FPV-EVID-001', $statusPath, $pointer.'/taskId', 'Evidence references an unknown task.');
                } else {
                    $task = $taskById[$item['taskId']];
                    foreach ($task['checks'] as $candidate) {
                        if ($candidate['id'] === $item['checkId']) {
                            $check = $candidate;
                            break;
                        }
                    }
                    if ($check === null) {
                        $this->error('FPV-EVID-001', $statusPath, $pointer.'/checkId', 'Evidence references an unknown task check.');
                    }
                }
            } elseif ($item['checkId'] !== null) {
                $this->error('FPV-EVID-001', $statusPath, $pointer.'/checkId', 'Plan-wide evidence cannot reference a task check.');
            }

            if ($item['kind'] === 'analysis') {
                if ($task === null || $check === null || $item['stage'] !== '000'
                    || $item['command'] !== $check['command'] || $item['exitCode'] !== 0) {
                    $this->error('FPV-EVID-001', $statusPath, $pointer, 'Analysis evidence must be a successful Stage 000 task check.');
                }
            } elseif ($item['kind'] === 'baseline') {
                $stageOneTasks = array_values(array_filter(
                    $plan['tasks'],
                    static fn (array $candidate): bool => $candidate['stage'] === '001',
                ));
                $expectedTask = $stageOneTasks[0] ?? null;
                $bindingValid = $expectedTask === null
                    ? $item['taskId'] === null && $item['checkId'] === null
                    : $item['taskId'] === $expectedTask['id']
                        && $item['checkId'] === $expectedTask['checks'][0]['id']
                        && $item['command'] === $expectedTask['checks'][0]['command'];
                if ($item['stage'] !== '001' || ! $bindingValid
                    || $item['path'] !== $planPath.'/evidence/baseline.sha256' || $item['exitCode'] !== 0) {
                    $this->error('FPV-EVID-001', $statusPath, $pointer, 'Baseline evidence must bind the optional Stage 001 task and exact manifest.');
                }
            } elseif ($item['kind'] === 'red') {
                if ($task === null || $check === null || ! in_array($task['verificationMode'], ['tdd', 'contract'], true)
                    || $item['stage'] !== '002' || $item['command'] !== $check['command'] || $item['exitCode'] === 0) {
                    $this->error('FPV-EVID-003', $statusPath, $pointer, 'RED proof must be intended, Stage 002 and non-zero.');
                }
            } elseif ($item['kind'] === 'green') {
                if ($task === null || $check === null || ! in_array($task['verificationMode'], ['tdd', 'contract'], true)
                    || $item['stage'] !== '003' || $item['command'] !== $check['command'] || $item['exitCode'] !== 0) {
                    $this->error('FPV-EVID-004', $statusPath, $pointer, 'GREEN proof must use the same Stage 002 check command and exit zero.');
                }
            } elseif ($item['kind'] === 'qa') {
                if ($task === null || $check === null || $item['stage'] !== '004'
                    || $item['command'] !== $check['command'] || $item['exitCode'] !== 0) {
                    $this->error('FPV-EVID-005', $statusPath, $pointer, 'QA evidence command, stage or exit is invalid.');
                }
            } elseif ($item['kind'] === 'gate') {
                if (($check !== null && $item['command'] !== $check['command'])) {
                    $this->error('FPV-EVID-005', $statusPath, $pointer, 'QA or gate evidence command/exit is invalid.');
                }
            } elseif ($item['kind'] === 'snapshot') {
                if ($item['stage'] !== '006' || $item['taskId'] !== null || $item['checkId'] !== null || $item['exitCode'] !== 0) {
                    $this->error('FPV-EVID-001', $statusPath, $pointer, 'Snapshot evidence must be successful and plan-wide at Stage 006.');
                }
            } elseif ($item['kind'] === 'handoff') {
                if ($task === null || $check === null || $item['stage'] !== '007'
                    || $item['command'] !== $check['command'] || $item['exitCode'] !== 0) {
                    $this->error('FPV-EVID-001', $statusPath, $pointer, 'Handoff evidence must be a successful Stage 007 task check.');
                }
            } elseif ($check !== null && $item['command'] !== $check['command']) {
                $this->error('FPV-EVID-001', $statusPath, $pointer.'/command', 'Evidence command differs from its PLAN check.');
            }

            if ($v2) {
                $gateId = $item['gateId'] ?? null;
                $manifestSha256 = $item['verifiedManifestSha256'] ?? null;
                if ($gateId === null) {
                    if ($manifestSha256 !== null) {
                        $this->error('FPV-EVID-005', $statusPath, $pointer, 'Unbound evidence cannot claim a verified manifest.');
                    }
                } else {
                    $gate = $gatesById[$gateId] ?? null;
                    if ($item['kind'] !== 'gate' || $gate === null
                        || $item['stage'] !== $gate['stage'] || $item['command'] !== $gate['command']
                        || preg_match('/^[a-f0-9]{64}$/D', (string) $manifestSha256) !== 1) {
                        $this->error('FPV-EVID-005', $statusPath, $pointer, 'Bound gate evidence must identify its gate and verified final manifest.');
                    }
                }
                if ($item['kind'] !== 'gate' && ($gateId !== null || $manifestSha256 !== null)) {
                    $this->error('FPV-EVID-005', $statusPath, $pointer, 'Only gate evidence can bind a gate or final manifest.');
                }
            }
        }

        if ($readinessIndex >= 1
            && count(array_filter(
                $status['evidence'],
                static fn (array $item): bool => $item['kind'] === 'baseline',
            )) !== 1) {
            $this->error('FPV-EVID-001', $statusPath, '/evidence', 'Closed Stage 001 requires exactly one baseline evidence record.');
        }

        if ($readinessIndex < 2) {
            return;
        }
        foreach ($plan['tasks'] as $task) {
            if (! in_array($task['verificationMode'], ['tdd', 'contract'], true)) {
                continue;
            }
            foreach ($task['checks'] as $check) {
                $red = null;
                $green = null;
                $qa = null;
                foreach ($evidenceById as $item) {
                    if ($item['taskId'] !== $task['id'] || $item['checkId'] !== $check['id']) {
                        continue;
                    }
                    if ($item['kind'] === 'red') {
                        $red = $item;
                    } elseif ($item['kind'] === 'green') {
                        $green = $item;
                    } elseif ($item['kind'] === 'qa') {
                        $qa = $item;
                    }
                }
                if ($red === null || $red['stage'] !== '002' || $red['command'] !== $check['command'] || $red['exitCode'] === 0) {
                    $this->error('FPV-EVID-003', $statusPath, '/evidence', 'Required RED proof is absent or invalid.');
                }
                if ($readinessIndex >= 3
                    && ($green === null || $green['stage'] !== '003' || $green['command'] !== $check['command'] || $green['exitCode'] !== 0)) {
                    $this->error('FPV-EVID-004', $statusPath, '/evidence', 'Required same-command GREEN proof is absent or invalid.');
                }
                if ($readinessIndex >= 4
                    && ($qa === null || $qa['stage'] !== '004' || $qa['command'] !== $check['command'] || $qa['exitCode'] !== 0)) {
                    $this->error('FPV-EVID-005', $statusPath, '/evidence', 'Required focused QA proof is absent or invalid.');
                }
            }
        }
    }

    /**
     * @param  Plan  $plan
     * @param  Status  $status
     * @param  array<string, Evidence>  $evidenceById
     */
    private function validateGateResults(array $plan, array $status, array $evidenceById, string $statusPath): void
    {
        $results = $status['gateResults'];
        if (array_column($results, 'id') !== array_column($plan['gates'], 'id')) {
            $this->error('FPV-EVID-005', $statusPath, '/gateResults', 'Gate results must exactly project PLAN gates.');

            return;
        }
        if ($plan['protocolVersion'] === self::PROTOCOL_V2) {
            $finalPath = $this->root.'/plan/'.$plan['planId'].'/evidence/final.sha256';
            $manifestSha256 = is_file($finalPath) ? hash_file('sha256', $finalPath) : null;
            if ($status['snapshot']['state'] === 'invalidated'
                && $manifestSha256 === $status['snapshot']['manifestSha256']) {
                $manifestSha256 = null;
            }
            foreach ($results as $index => $result) {
                $gate = $plan['gates'][$index];
                $latest = null;
                foreach ($status['evidence'] as $item) {
                    if (($item['gateId'] ?? null) === $gate['id']
                        && ($item['verifiedManifestSha256'] ?? null) === $manifestSha256) {
                        $latest = $item;
                    }
                }
                if ($latest === null) {
                    if ($result['state'] !== 'pending' || $result['evidenceId'] !== null) {
                        $this->error('FPV-EVID-005', $statusPath, '/gateResults/'.$index, 'Gate without an attempt for the current manifest must stay pending.');
                    }

                    continue;
                }
                $expectedState = $latest['exitCode'] === 0 ? 'passed' : 'failed';
                if ($result['state'] !== $expectedState || $result['evidenceId'] !== $latest['id']) {
                    $this->error('FPV-EVID-005', $statusPath, '/gateResults/'.$index, 'Gate result must reference the latest attempt for the current manifest.');
                }
            }

            return;
        }
        foreach ($results as $index => $result) {
            $gate = $plan['gates'][$index];
            if ($result['state'] === 'pending') {
                if ($result['evidenceId'] !== null) {
                    $this->error('FPV-EVID-005', $statusPath, '/gateResults/'.$index, 'Pending gate cannot reference evidence.');
                }

                continue;
            }
            $evidenceId = $result['evidenceId'];
            if ($evidenceId === null
                || $evidenceId !== ($gate['evidenceId'] ?? null)
                || ! isset($evidenceById[$evidenceId])) {
                $this->error('FPV-EVID-005', $statusPath, '/gateResults/'.$index, 'Gate result references wrong evidence.');

                continue;
            }
            $item = $evidenceById[$evidenceId];
            $valid = $item['kind'] === 'gate'
                && $item['stage'] === $gate['stage']
                && $item['command'] === $gate['command']
                && ($result['state'] === 'passed' ? $item['exitCode'] === 0 : $item['exitCode'] !== 0);
            if (! $valid) {
                $this->error('FPV-EVID-005', $statusPath, '/gateResults/'.$index, 'Gate state does not match command evidence.');
            }
        }
    }

    /**
     * @param  Plan  $plan
     * @param  Status  $status
     * @param  list<Evidence>  $evidence
     */
    private function validateManifests(string $planPath, array $plan, array $status, array $evidence, string $phase): void
    {
        $readinessIndex = $this->readinessIndex($status, $phase);
        $complete = $status['overallState'] === 'complete';
        $historical = in_array($status['overallState'], ['complete', 'superseded'], true);
        $v2 = $plan['protocolVersion'] === self::PROTOCOL_V2;
        $baselineRequired = $complete || $readinessIndex >= 1;
        $finalRequired = $complete || $readinessIndex >= ($v2 ? 5 : 6) || $status['snapshot']['state'] !== 'pending';
        $baselinePath = $planPath.'/evidence/baseline.sha256';
        $finalPath = $planPath.'/evidence/final.sha256';
        if ($baselineRequired && ! is_file($this->root.'/'.$baselinePath)) {
            $this->error('FPV-MANIFEST-001', $baselinePath, '', 'Phase requires baseline manifest.');
        }
        if ($finalRequired && ! is_file($this->root.'/'.$finalPath)) {
            $this->error('FPV-MANIFEST-001', $finalPath, '', 'Phase requires final manifest.');
        }

        $ownedPaths = [];
        $createPaths = [];
        $deletePaths = [];
        foreach ($plan['tasks'] as $task) {
            foreach ($task['ownedPaths'] as $path) {
                $ownedPaths[$path] = true;
            }
            foreach ($task['createPaths'] as $path) {
                $createPaths[$path] = true;
            }
            foreach ($task['deletePaths'] as $path) {
                $deletePaths[$path] = true;
            }
        }
        $expectedPaths = array_keys($ownedPaths);
        sort($expectedPaths, SORT_STRING);

        $baseline = is_file($this->root.'/'.$baselinePath) ? $this->parseManifest($baselinePath) : null;
        $final = is_file($this->root.'/'.$finalPath) ? $this->parseManifest($finalPath) : null;
        foreach (['baseline' => $baseline, 'final' => $final] as $kind => $manifest) {
            if ($manifest === null) {
                continue;
            }
            if (array_keys($manifest) !== $expectedPaths) {
                $this->error('FPV-MANIFEST-003', $planPath.'/evidence/'.$kind.'.sha256', '', 'Manifest path set differs from PLAN ownedPaths.');
            }
            foreach ($manifest as $filePath => $storedHash) {
                if ($kind === 'baseline'
                    && (($storedHash === 'MISSING') !== isset($createPaths[$filePath]))) {
                    $this->error('FPV-MANIFEST-004', $baselinePath, '', 'Baseline MISSING entries must exactly match createPaths.');
                }
                if ($kind === 'final'
                    && (($storedHash === 'MISSING') !== isset($deletePaths[$filePath]))) {
                    $this->error('FPV-MANIFEST-004', $finalPath, '', 'Final MISSING entries must exactly match deletePaths.');
                }
            }
        }

        if ($baseline !== null && $phase === '001') {
            $this->compareManifestToCurrent($baseline, $baselinePath);
        }
        $snapshot = $status['snapshot'];
        $usingInvalidatedManifest = $v2
            && $snapshot['state'] === 'invalidated'
            && is_file($this->root.'/'.$finalPath)
            && hash_file('sha256', $this->root.'/'.$finalPath) === $snapshot['manifestSha256'];
        $compareFinal = in_array($phase, $v2 ? ['005', '006', '007'] : ['006', '007'], true)
            || ($phase === 'current' && in_array($status['currentStage'], $v2 ? ['006', '007'] : ['007'], true));
        if ($final !== null && ! $historical && ! $usingInvalidatedManifest && $compareFinal) {
            $this->compareManifestToCurrent($final, $finalPath);
        }

        if ($snapshot['state'] === 'pending') {
            if ($snapshot['round'] !== 0 || $snapshot['manifestPath'] !== null
                || $snapshot['manifestSha256'] !== null || $snapshot['bundleSha256'] !== null) {
                $this->error('FPV-MANIFEST-004', $planPath.'/STATUS.json', '/snapshot', 'Pending snapshot cannot contain hashes.');
            }

            return;
        }
        if (in_array($snapshot['state'], ['frozen', 'invalidated'], true)) {
            if ($snapshot['round'] < 1 || $snapshot['manifestPath'] !== $finalPath || $final === null) {
                $this->error('FPV-MANIFEST-004', $planPath.'/STATUS.json', '/snapshot', 'Stored snapshot manifest reference is invalid.');

                return;
            }
            if ($v2 && $snapshot['state'] === 'invalidated') {
                if (preg_match('/^[a-f0-9]{64}$/D', (string) $snapshot['manifestSha256']) !== 1
                    || preg_match('/^[a-f0-9]{64}$/D', (string) $snapshot['bundleSha256']) !== 1) {
                    $this->error('FPV-MANIFEST-004', $planPath.'/STATUS.json', '/snapshot', 'Invalidated snapshot must retain its prior hashes.');
                }

                return;
            }
            $manifestHash = hash_file('sha256', $this->root.'/'.$finalPath);
            $bundleHash = $this->bundleSha256($planPath, $evidence);
            if ($snapshot['manifestSha256'] !== $manifestHash || $snapshot['bundleSha256'] !== $bundleHash) {
                $this->error('FPV-MANIFEST-004', $planPath.'/STATUS.json', '/snapshot', 'Stored snapshot manifest or canonical bundle hash is invalid.');
            }
        }
    }

    /** @return array<string, string>|null */
    private function parseManifest(string $path): ?array
    {
        $contents = file_get_contents($this->root.'/'.$path);
        if ($contents === false || str_contains($contents, "\r") || ! str_ends_with($contents, "\n")) {
            $this->error('FPV-MANIFEST-002', $path, '', 'Manifest encoding or line endings are invalid.');

            return null;
        }
        $lines = explode("\n", substr($contents, 0, -1));
        if (in_array('', $lines, true)) {
            $this->error('FPV-MANIFEST-002', $path, '', 'Manifest cannot contain blank lines.');

            return null;
        }
        $manifest = [];
        $paths = [];
        foreach ($lines as $line) {
            if (preg_match('/^(MISSING|[a-f0-9]{64})  ([^\s].*)$/D', $line, $matches) !== 1
                || $this->pathViolation($matches[2]) !== null) {
                $this->error('FPV-MANIFEST-002', $path, '', 'Manifest line format or path is invalid.');

                return null;
            }
            $paths[] = $matches[2];
            $manifest[$matches[2]] = $matches[1];
        }
        $sorted = $paths;
        sort($sorted, SORT_STRING);
        if ($paths !== $sorted || ! $this->isUnique($paths)) {
            $this->error('FPV-MANIFEST-002', $path, '', 'Manifest paths must be sorted and unique.');

            return null;
        }

        return $manifest;
    }

    /** @param array<string, string> $manifest */
    private function compareManifestToCurrent(array $manifest, string $path): void
    {
        foreach ($manifest as $filePath => $storedHash) {
            $absolute = $this->root.'/'.$filePath;
            $actual = is_file($absolute) ? hash_file('sha256', $absolute) : 'MISSING';
            if ($actual !== $storedHash) {
                $this->error('FPV-MANIFEST-004', $path, '', 'Manifest does not match current declared files for this phase.');
            }
        }
    }

    /** @param list<Evidence> $evidence */
    private function bundleSha256(string $planPath, array $evidence): string
    {
        $directory = $this->root.'/'.$planPath;
        $lines = ['PLAN '.hash_file('sha256', $directory.'/PLAN.json')];
        $documents = ['ARCHITECTURE_CONFORMANCE.md', 'DECISIONS.md', 'README.md', 'TASKS.md', 'TRACEABILITY.md'];
        if (is_file($directory.'/OWNERSHIP.md')) {
            $documents[] = 'OWNERSHIP.md';
            sort($documents, SORT_STRING);
        }
        foreach ($documents as $document) {
            $lines[] = 'DOC '.$document.' '.hash_file('sha256', $directory.'/'.$document);
        }
        $lines[] = 'MANIFEST '.hash_file('sha256', $directory.'/evidence/final.sha256');
        $included = array_values(array_filter(
            $evidence,
            static fn (array $item): bool => in_array($item['kind'], ['analysis', 'baseline', 'red', 'green', 'qa', 'gate'], true),
        ));
        usort($included, static fn (array $left, array $right): int => $left['id'] <=> $right['id']);
        foreach ($included as $item) {
            $lines[] = 'EVIDENCE '.$item['id'].' '.$item['sha256'];
        }

        return hash('sha256', implode("\n", $lines)."\n");
    }

    /**
     * @param  Plan  $plan
     * @param  Status  $status
     * @param  list<StatusTask>  $taskStatuses
     */
    private function validateReviews(
        string $planPath,
        array $plan,
        array $status,
        array $taskStatuses,
        string $statusPath,
    ): void {
        $reviews = $status['reviews'];
        if (array_column($reviews, 'axisId') !== self::REVIEW_AXES) {
            $this->error('FPV-REVIEW-001', $statusPath, '/reviews', 'STATUS must contain the three fixed review axes in order.');
        }

        $axisById = [];
        foreach ($plan['reviewAxes'] as $axis) {
            $axisById[$axis['id']] = $axis;
        }
        $taskExecutors = array_values(array_filter(array_column($taskStatuses, 'executorId'), is_string(...)));
        $reviewers = [];
        foreach ($reviews as $index => $review) {
            $pointer = '/reviews/'.$index;
            $axisId = $review['axisId'];
            if (! isset($axisById[$axisId])) {
                $this->error('FPV-REVIEW-001', $statusPath, $pointer.'/axisId', 'Review references an unknown fixed axis.');

                continue;
            }
            $axisSkills = array_column($axisById[$axisId]['skills'], 'id');
            if ($review['verdict'] === 'pending') {
                if ($review['reviewerId'] !== null || $review['round'] !== 0 || $review['snapshotSha256'] !== null
                    || $review['evidencePath'] !== null || $review['evidenceSha256'] !== null
                    || $review['usedSkills'] !== [] || $review['unavailableSkills'] !== [] || $review['skippedSkills'] !== []
                    || $review['b0'] !== 0 || $review['b1'] !== 0 || $review['b2'] !== 0) {
                    $this->error('FPV-REVIEW-001', $statusPath, $pointer, 'Pending review cannot prefill reviewer evidence.');
                }

                continue;
            }

            if ($review['usedSkills'] !== $axisSkills
                || $review['unavailableSkills'] !== [] || $review['skippedSkills'] !== []) {
                $this->error('FPV-REVIEW-001', $statusPath, $pointer, 'Review skill ledger must exactly match PLAN and contain no exception.');
            }
            if ($review['reviewerId'] === null
                || $review['reviewerId'] === $status['orchestratorId']
                || in_array($review['reviewerId'], $taskExecutors, true)
                || in_array($review['reviewerId'], $reviewers, true)) {
                $this->error('FPV-REVIEW-002', $statusPath, $pointer.'/reviewerId', 'Reviewer must be independent from orchestrator, writers and other axes.');
            } else {
                $reviewers[] = $review['reviewerId'];
            }
            $reviewableSnapshot = $status['snapshot']['state'] === 'frozen'
                || ($plan['protocolVersion'] === self::PROTOCOL_V2 && $status['snapshot']['state'] === 'invalidated');
            if (! $reviewableSnapshot
                || $review['round'] !== $status['snapshot']['round']
                || $review['snapshotSha256'] !== $status['snapshot']['bundleSha256']) {
                $this->error('FPV-REVIEW-003', $statusPath, $pointer, 'Review does not inspect the current frozen snapshot round/hash.');
            }

            $suffix = self::REVIEW_SUFFIXES[$axisId] ?? '';
            $expectedPath = $planPath.'/reviews/round-'.$review['round'].'-'.$suffix.'.md';
            $absolute = $this->root.'/'.$expectedPath;
            $pathCode = $this->pathViolation($expectedPath);
            $contents = $pathCode === null && is_file($absolute) ? file_get_contents($absolute) : false;
            if ($pathCode !== null || $review['evidencePath'] !== $expectedPath || ! is_file($absolute)
                || preg_match('/^[a-f0-9]{64}$/D', (string) $review['evidenceSha256']) !== 1
                || hash_file('sha256', $absolute) !== $review['evidenceSha256']
                || ! is_string($contents) || trim($contents) === '' || ! mb_check_encoding($contents, 'UTF-8')) {
                $this->error('FPV-REVIEW-001', $statusPath, $pointer.'/evidencePath', 'Review evidence path or hash is invalid.');
            }
            if (($review['verdict'] === 'go' && ($review['b0'] !== 0 || $review['b1'] !== 0))
                || ($review['verdict'] === 'fix_required' && $review['b0'] === 0 && $review['b1'] === 0 && $review['b2'] === 0)) {
                $this->error('FPV-REVIEW-004', $statusPath, $pointer, 'Review verdict contradicts open finding counts.');
            }
            if ($review['b2'] > 0 && is_file($absolute)) {
                $reviewText = (string) file_get_contents($absolute);
                if (! str_contains($reviewText, 'B2 rationale:') || ! str_contains($reviewText, 'B2 owner:')) {
                    $this->error('FPV-REVIEW-004', $statusPath, $pointer.'/b2', 'Open B2 requires rationale and owner in hashed review evidence.');
                }
            }
        }
    }

    /** @param Evidence $item */
    private function evidenceMarkdownComplete(string $contents, array $item): bool
    {
        $task = $item['taskId'] ?? 'none';
        $check = $item['checkId'] ?? 'none';

        $v2FieldsPresent = ! array_key_exists('gateId', $item)
            || (str_contains($contents, 'Gate: '.($item['gateId'] ?? 'none'))
                && str_contains($contents, 'Verified manifest SHA-256: '.($item['verifiedManifestSha256'] ?? 'none')));

        return $v2FieldsPresent
            && str_contains($contents, $item['id'])
            && str_contains($contents, 'Stage: '.$item['stage'])
            && str_contains($contents, 'Task: '.$task)
            && str_contains($contents, 'Check: '.$check)
            && str_contains($contents, 'Command: '.$item['command'])
            && str_contains($contents, 'Exit code: '.(string) $item['exitCode'])
            && stripos($contents, 'expected') !== false
            && stripos($contents, 'observed') !== false
            && str_contains($contents, 'Redacted:')
            && str_contains($contents, 'Limitations:');
    }

    /** @param Status $status */
    private function validateCompletion(array $status, string $statusPath): void
    {
        if ($status['overallState'] !== 'complete') {
            return;
        }
        $valid = $status['currentStage'] === '007'
            && count($status['stages']) === 8
            && ! in_array(false, array_map(static fn (array $stage): bool => $stage['state'] === 'complete', $status['stages']), true)
            && ! in_array(false, array_map(static fn (array $task): bool => $task['state'] === 'complete', $status['tasks']), true)
            && ! in_array(false, array_map(static fn (array $gate): bool => $gate['state'] === 'passed', $status['gateResults']), true)
            && $status['activeWriters'] === []
            && $status['blockers'] === []
            && $status['snapshot']['state'] === 'frozen'
            && count($status['reviews']) === 3
            && ! in_array(false, array_map(
                static fn (array $review): bool => $review['verdict'] === 'go' && $review['b0'] === 0 && $review['b1'] === 0,
                $status['reviews'],
            ), true);
        if (! $valid) {
            $this->error('FPV-COMPLETE-001', $statusPath, '', 'Stage 007 engineering Definition of Done is incomplete.');
        }
    }

    private function validateFilesystemTypes(string $planPath, string $directory): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );
        /** @var array<string, string> $caseNames */
        $caseNames = [];
        foreach ($iterator as $item) {
            /** @var SplFileInfo $item */
            $relative = $planPath.'/'.substr($item->getPathname(), strlen($directory) + 1);
            if ($item->isLink()) {
                $this->error('FPV-PATH-003', $relative, '', 'Symlinks and forbidden filesystem types are not allowed.');

                continue;
            }
            $stat = lstat($item->getPathname());
            if (! $item->isFile() && ! $item->isDir()) {
                $this->error('FPV-PATH-003', $relative, '', 'Only regular files and directories are allowed.');
            } elseif ($item->isFile() && is_array($stat) && $stat['nlink'] > 1) {
                $this->error('FPV-PATH-003', $relative, '', 'Hard-link aliases are not allowed.');
            }
            $key = strtolower($relative);
            if (isset($caseNames[$key]) && $caseNames[$key] !== $relative) {
                $this->error('FPV-PATH-004', $relative, '', 'Case-colliding path is not canonical.');
            }
            $caseNames[$key] = $relative;
        }
    }

    /**
     * @param  array<mixed, mixed>|null  $plan
     * @param  array<mixed, mixed>|null  $status
     */
    private function validateClosedFilesystem(string $planPath, ?array $plan, ?array $status): void
    {
        $directory = $this->root.'/'.$planPath;
        $entries = scandir($directory);
        if ($entries === false) {
            $this->error('FPV-FILE-001', $planPath, '', 'Plan directory cannot be read.');

            return;
        }
        $canonical = [
            'README.md', 'PLAN.json', 'STATUS.json', 'TASKS.md', 'ARCHITECTURE_CONFORMANCE.md',
            'DECISIONS.md', 'TRACEABILITY.md', 'OWNERSHIP.md', 'evidence', 'reviews',
        ];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (! in_array($entry, $canonical, true)) {
                $wrongCase = array_any($canonical, fn ($name) => strtolower($entry) === strtolower((string) $name));
                $this->error($wrongCase ? 'FPV-PATH-004' : 'FPV-FILE-003', $planPath.'/'.$entry, '', 'Plan root contains an undeclared or wrong-case entry.');
            }
        }

        $activeWriters = $status['activeWriters'] ?? [];
        $writerCount = is_array($activeWriters) ? count($activeWriters) : 0;
        if ($writerCount >= 2 && ! is_file($directory.'/OWNERSHIP.md')) {
            $this->error('FPV-FILE-001', $planPath.'/OWNERSHIP.md', '', 'OWNERSHIP.md is required for active parallel writers.');
        }

        $allowedEvidence = ['command-log.md'];
        $allowedReviews = [];
        $historicalReviewHashes = [];
        if ($status !== null) {
            $evidence = $status['evidence'] ?? [];
            if (is_array($evidence)) {
                foreach ($evidence as $item) {
                    $prefix = $planPath.'/evidence/';
                    if (is_array($item) && is_string($item['path'] ?? null) && str_starts_with($item['path'], $prefix)) {
                        $allowedEvidence[] = substr($item['path'], strlen($prefix));
                    }
                    if (! is_array($item) || ! is_string($item['id'] ?? null)
                        || ! str_starts_with($item['id'], 'EVID-GATE-REVIEW-LEDGER-')
                        || ! is_string($item['path'] ?? null) || ! is_string($item['sha256'] ?? null)) {
                        continue;
                    }
                    $ledger = $this->root.'/'.$item['path'];
                    if (! is_file($ledger) || hash_file('sha256', $ledger) !== $item['sha256']) {
                        continue;
                    }
                    $contents = file_get_contents($ledger);
                    if (! is_string($contents)) {
                        continue;
                    }
                    preg_match_all('/^([a-f0-9]{64})  (round-[1-9][0-9]*-([a-z0-9-]+)\.md)$/m', $contents, $matches, PREG_SET_ORDER);
                    foreach ($matches as $match) {
                        if (in_array($match[3], array_values(self::REVIEW_SUFFIXES), true)) {
                            $historicalReviewHashes[$match[2]] = $match[1];
                        }
                    }
                }
            }
            $reviews = $status['reviews'] ?? [];
            if (is_array($reviews)) {
                foreach ($reviews as $review) {
                    $prefix = $planPath.'/reviews/';
                    if (is_array($review) && is_string($review['evidencePath'] ?? null)
                        && str_starts_with($review['evidencePath'], $prefix)) {
                        $allowedReviews[] = substr($review['evidencePath'], strlen($prefix));
                    }
                }
            }
            $snapshot = $status['snapshot'] ?? null;
            if (is_array($snapshot) && is_int($snapshot['round'] ?? null) && is_dir($directory.'/reviews')) {
                $exclusiveRound = ($snapshot['state'] ?? null) === 'invalidated'
                    ? $snapshot['round'] + 1
                    : $snapshot['round'];
                $reviewEntries = scandir($directory.'/reviews');
                if ($reviewEntries !== false) {
                    foreach ($reviewEntries as $entry) {
                        if (in_array($entry, $allowedReviews, true)) {
                            continue;
                        }
                        if (preg_match('/^round-([1-9][0-9]*)-([a-z0-9-]+)\.md$/D', $entry, $matches) === 1
                            && (int) $matches[1] < $exclusiveRound
                            && in_array($matches[2], array_values(self::REVIEW_SUFFIXES), true)
                            && isset($historicalReviewHashes[$entry])
                            && hash_file('sha256', $directory.'/reviews/'.$entry) === $historicalReviewHashes[$entry]) {
                            $allowedReviews[] = $entry;
                        } elseif (str_starts_with($entry, 'round-') && $entry !== '.' && $entry !== '..') {
                            $this->error('FPV-REVIEW-003', $planPath.'/reviews/'.$entry, '', 'Historical review must match a hashed review ledger.');
                        }
                    }
                }
            }
        }
        foreach (['baseline.sha256', 'final.sha256'] as $manifest) {
            if (is_file($directory.'/evidence/'.$manifest)) {
                $allowedEvidence[] = $manifest;
            }
        }
        $this->validateChildDirectory($planPath.'/evidence', $allowedEvidence, true);
        if (is_dir($directory.'/reviews')) {
            if ($allowedReviews === []) {
                $this->error('FPV-FILE-003', $planPath.'/reviews', '', 'Review directory cannot exist before review evidence.');
            } else {
                $this->validateChildDirectory($planPath.'/reviews', $allowedReviews, false);
            }
        } elseif ($allowedReviews !== []) {
            $this->error('FPV-FILE-001', $planPath.'/reviews', '', 'Review evidence directory is missing.');
        }
    }

    /** @param list<string> $allowed */
    private function validateChildDirectory(string $path, array $allowed, bool $required): void
    {
        $absolute = $this->root.'/'.$path;
        if (! is_dir($absolute)) {
            if ($required) {
                $this->error('FPV-FILE-001', $path, '', 'Required plan directory is missing.');
            }

            return;
        }
        $entries = scandir($absolute);
        if ($entries === false) {
            $this->error('FPV-FILE-001', $path, '', 'Plan evidence directory cannot be read.');

            return;
        }
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            if (! in_array($entry, $allowed, true)) {
                $this->error('FPV-FILE-003', $path.'/'.$entry, '', 'Undeclared evidence or review artifact exists.');
            }
        }
    }

    /** @param Plan $plan */
    private function validateHumanFiles(string $planPath, array $plan): void
    {
        $checks = [
            'README.md' => ['# Goal', '# Scope', '# Non-goals', '# Operator Notes'],
            'TASKS.md' => ['# Tasks'],
            'ARCHITECTURE_CONFORMANCE.md' => ['Deviations: none'],
            'DECISIONS.md' => ['# Accepted Decisions', '# Rejected Alternatives'],
            'TRACEABILITY.md' => ['# Traceability'],
            'evidence/command-log.md' => ['# Command Log'],
        ];
        foreach ($checks as $file => $needles) {
            $absolute = $this->root.'/'.$planPath.'/'.$file;
            if (! is_file($absolute)) {
                $this->error('FPV-FILE-001', $planPath.'/'.$file, '', 'Required Markdown projection is missing.');

                continue;
            }
            $contents = (string) file_get_contents($absolute);
            if (trim($contents) === '' || ! mb_check_encoding($contents, 'UTF-8')) {
                $this->error('FPV-FILE-002', $planPath.'/'.$file, '', 'Markdown projection must be non-empty UTF-8.');

                continue;
            }
            foreach ($needles as $needle) {
                if (! str_contains($contents, $needle)) {
                    $this->error('FPV-FILE-002', $planPath.'/'.$file, '', 'Markdown projection misses a required durable heading or marker.');
                    break;
                }
            }
            if ($file === 'TASKS.md') {
                $tokens = [];
                foreach ($plan['tasks'] as $task) {
                    foreach (['id', 'title', 'objective', 'agentRole', 'executionMode', 'verificationMode'] as $key) {
                        $tokens[] = $task[$key];
                    }
                    foreach (['dependsOn', 'ownedPaths', 'createPaths', 'deletePaths', 'requirementIds', 'acceptanceIds'] as $key) {
                        array_push($tokens, ...$task[$key]);
                    }
                    foreach ($task['checks'] as $check) {
                        array_push($tokens, $check['id'], $check['kind'], $check['command'], $check['expected']);
                    }
                    foreach ($task['skills'] as $skill) {
                        array_push($tokens, $skill['id'], $skill['purpose'], $skill['expectedOutcome'], ...$skill['applyAt']);
                    }
                }
                if ($this->firstMissingToken($contents, $tokens) !== null) {
                    $this->error('FPV-FILE-002', $planPath.'/'.$file, '', 'TASKS.md omits a frozen task path, check or full skill projection.');
                }
            }
            if ($file === 'TRACEABILITY.md') {
                $tokens = [];
                foreach ($plan['requirements'] as $requirement) {
                    $tokens[] = $requirement['id'];
                    foreach ($requirement['acceptance'] as $acceptance) {
                        $tokens[] = $acceptance['id'];
                    }
                }
                foreach ($plan['tasks'] as $task) {
                    $tokens[] = $task['id'];
                    foreach ($task['checks'] as $check) {
                        $tokens[] = $check['id'];
                    }
                }
                foreach ($plan['gates'] as $gate) {
                    $tokens[] = $gate['id'];
                    if (isset($gate['evidenceId'])) {
                        $tokens[] = $gate['evidenceId'];
                    }
                }
                if ($this->firstMissingToken($contents, $tokens) !== null) {
                    $this->error('FPV-FILE-002', $planPath.'/'.$file, '', 'TRACEABILITY.md omits a REQ, AC, TASK, CHECK, GATE or EVID link.');
                }
            }
            if ($file === 'ARCHITECTURE_CONFORMANCE.md') {
                foreach ($plan['architecture']['ruleIds'] as $ruleId) {
                    if (! str_contains($contents, $ruleId)) {
                        $this->error('FPV-FILE-002', $planPath.'/'.$file, '', 'Architecture conformance omits a frozen PLAN rule ID.');
                        break;
                    }
                }
            }
        }
    }

    /** @param list<string> $tokens */
    private function firstMissingToken(string $contents, array $tokens): ?string
    {
        foreach ($tokens as $token) {
            if ($token !== '' && ! str_contains($contents, $token)) {
                return $token;
            }
        }

        return null;
    }

    private function validSupersedes(string $slug, int $revision, mixed $supersedes): bool
    {
        if (! is_string($supersedes) || $revision < 2) {
            return false;
        }
        $base = preg_replace('/-v[0-9]+$/D', '', $slug);
        if (! is_string($base) || $base === $slug) {
            return false;
        }
        $expectedSlug = $revision === 2 ? $base : $base.'-v'.($revision - 1);

        return $slug === $base.'-v'.$revision && $supersedes === $expectedSlug.'-fast-tdd';
    }

    /** @param Plan $plan */
    private function hasValidSuccessor(array $plan, mixed $supersededBy): bool
    {
        $base = $plan['revision'] === 1
            ? $plan['slug']
            : preg_replace('/-v'.preg_quote((string) $plan['revision'], '/').'$/D', '', $plan['slug']);
        if (! is_string($base) || $base === '') {
            return false;
        }
        $nextRevision = $plan['revision'] + 1;
        $nextSlug = $base.'-v'.$nextRevision;
        $nextId = $nextSlug.'-fast-tdd';
        $directory = $this->root.'/plan/'.$nextId;
        $planFile = $directory.'/PLAN.json';
        if ($supersededBy !== $nextId || is_link($directory) || ! is_dir($directory)
            || ! is_file($planFile) || ! is_file($directory.'/STATUS.json')) {
            return false;
        }
        $contents = file_get_contents($planFile);
        if (! is_string($contents) || strlen($contents) > self::MAX_JSON_BYTES) {
            return false;
        }
        try {
            $successor = json_decode($contents, true, 128, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return false;
        }

        return is_array($successor)
            && ! array_is_list($successor)
            && ($successor['protocolVersion'] ?? null) === $plan['protocolVersion']
            && ($successor['planId'] ?? null) === $nextId
            && ($successor['slug'] ?? null) === $nextSlug
            && ($successor['revision'] ?? null) === $nextRevision
            && ($successor['supersedes'] ?? null) === $plan['planId'];
    }

    /** @return array{plans: int, checks: int, errors: list<array{code: string, path: string, pointer: string, message: string}>} */
    private function result(int $plans): array
    {
        usort($this->errors, static fn (array $left, array $right): int => [
            $left['planId'],
            $left['code'],
            $left['path'],
            $left['pointer'],
            $left['message'],
        ] <=> [
            $right['planId'],
            $right['code'],
            $right['path'],
            $right['pointer'],
            $right['message'],
        ]);

        $errors = array_map(
            static fn (array $error): array => [
                'code' => $error['code'],
                'path' => $error['path'],
                'pointer' => $error['pointer'],
                'message' => $error['message'],
            ],
            $this->errors,
        );

        return ['plans' => $plans, 'checks' => $this->checks, 'errors' => $errors];
    }

    private function error(string $code, string $path, string $pointer, string $message): void
    {
        $candidate = [
            'code' => $code,
            'path' => $path,
            'pointer' => $pointer,
            'message' => $message,
            'planId' => $this->planId,
        ];
        foreach ($this->errors as $error) {
            if ($error === $candidate) {
                return;
            }
        }
        $this->errors[] = $candidate;
    }
}
