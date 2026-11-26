<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

afterEach(function (): void {
    foreach (fastStrictFixtureRoots() as $root) {
        fastStrictDeleteTree($root);
    }

    fastStrictFixtureRoots(reset: true);
});

test('валидатор принимает точный draft FAST_STRICT/1 план детерминированно и только для чтения', function (): void {
    $root = fastStrictCreateFixture();
    $before = fastStrictTreeDigest($root);

    $firstExitCode = fastStrictRun($root);
    $firstOutput = Artisan::output();

    expect($firstExitCode)->toBe(0)
        ->and($firstOutput)->toBe("SUMMARY plans=1 errors=0\n")
        ->and(fastStrictTreeDigest($root))->toBe($before);

    $secondExitCode = fastStrictRun($root);

    expect($secondExitCode)->toBe(0)
        ->and(Artisan::output())->toBe($firstOutput)
        ->and(fastStrictTreeDigest($root))->toBe($before);
});

test('валидатор принимает точный путь динамического маршрута Next.js', function (string $protocolVersion): void {
    $root = fastStrictCreateFixture(protocolVersion: $protocolVersion);
    $dynamicPath = 'frontend/src/app/(catalog)/vacancies/[slug]/page.tsx';
    fastStrictWrite($root.'/'.$dynamicPath, "export default function Page() { return null; }\n");
    fastStrictMutatePlan($root, static function (array &$plan) use ($dynamicPath): void {
        $plan['tasks'][4]['ownedPaths'][1] = $dynamicPath;
    });

    $tasksPath = $root.'/plan/example-fast-tdd/TASKS.md';
    fastStrictWrite($tasksPath, str_replace('src/Feature.php', $dynamicPath, (string) file_get_contents($tasksPath)));

    $exitCode = fastStrictRun($root);

    expect(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n")
        ->and($exitCode)->toBe(0);
})->with(['FAST_STRICT/1', 'FAST_STRICT/2']);

test('валидатор отклоняет квадратные скобки вне точного сегмента маршрута', function (string $ownedPath): void {
    $root = fastStrictCreateFixture(protocolVersion: 'FAST_STRICT/2');
    fastStrictMutatePlan($root, static function (array &$plan) use ($ownedPath): void {
        $plan['tasks'][4]['ownedPaths'][1] = $ownedPath;
        sort($plan['tasks'][4]['ownedPaths'], SORT_STRING);
    });
    $tasksPath = $root.'/plan/example-fast-tdd/TASKS.md';
    fastStrictWrite($tasksPath, str_replace('src/Feature.php', $ownedPath, (string) file_get_contents($tasksPath)));
    $exitCode = fastStrictRun($root);

    expect(Artisan::output())->toContain('FPV-PATH-001')
        ->and($exitCode)->toBe(1);
})->with([
    'частичный сегмент' => 'frontend/src/app/(catalog)/vacancies/[slug]extra/page.tsx',
    'вне App Router' => 'backend/app/Services/[slug]/Page.php',
    'glob' => 'frontend/src/app/(catalog)/vacancies/*/page.tsx',
]);

test('валидатор принимает завершенный план с тремя независимыми review одного snapshot', function (): void {
    $root = fastStrictCreateFixture(complete: true);
    $before = fastStrictTreeDigest($root);

    expect(fastStrictRun($root, '007'))->toBe(0)
        ->and(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n")
        ->and(fastStrictTreeDigest($root))->toBe($before);
});

test('валидатор принимает draft и завершенный FAST_STRICT/2 план', function (bool $complete): void {
    $root = fastStrictCreateFixture(complete: $complete, protocolVersion: 'FAST_STRICT/2');

    $exitCode = fastStrictRun($root, $complete ? '007' : 'current');

    expect(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n")
        ->and($exitCode)->toBe(0);
})->with([false, true]);

test('FAST_STRICT/2 сохраняет неудачную попытку gate перед успешной для того же manifest', function (): void {
    $root = fastStrictCreateFixture(complete: true, protocolVersion: 'FAST_STRICT/2');
    fastStrictAddV2GateAttempt($root, 'EVID-GATE-QUALITY-ATTEMPT-001', 'GATE-QUALITY', 1, beforeEvidenceId: 'EVID-GATE-QUALITY');
    fastStrictRefreshV2Snapshot($root);

    $exitCode = fastStrictRun($root, '007');

    expect(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n")
        ->and($exitCode)->toBe(0);
});

test('FAST_STRICT/2 не позволяет скрыть позднее падение ранним успешным gate', function (): void {
    $root = fastStrictCreateFixture(complete: true, protocolVersion: 'FAST_STRICT/2');
    fastStrictAddV2GateAttempt($root, 'EVID-GATE-QUALITY-ATTEMPT-002', 'GATE-QUALITY', 1);

    expect(fastStrictRun($root, '007'))->toBe(1)
        ->and(Artisan::output())->toContain('FPV-EVID-005');
});

test('FAST_STRICT/2 не принимает gate от предыдущего manifest', function (): void {
    $root = fastStrictCreateFixture(complete: true, protocolVersion: 'FAST_STRICT/2');
    fastStrictWrite($root.'/src/Feature.php', "<?php\n\ndeclare(strict_types=1);\n\nfinal class ChangedFeature {}\n");
    /** @var FastStrictFixturePlan $plan */
    $plan = json_decode((string) file_get_contents($root.'/plan/example-fast-tdd/PLAN.json'), true, 512, JSON_THROW_ON_ERROR);
    fastStrictWrite(
        $root.'/plan/example-fast-tdd/evidence/final.sha256',
        fastStrictManifest($root, fastStrictOwnedPaths($plan)),
    );

    expect(fastStrictRun($root, '007'))->toBe(1)
        ->and(Artisan::output())->toContain('FPV-EVID-005');
});

test('FAST_STRICT/2 не требует нового gate после изменения только STATUS', function (): void {
    $root = fastStrictCreateFixture(complete: true, protocolVersion: 'FAST_STRICT/2');
    fastStrictMutateStatus($root, static function (array &$status): void {
        $status['updatedAt'] = '2026-08-20T01:00:00Z';
    });

    expect(fastStrictRun($root, '007'))->toBe(0)
        ->and(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n");
});

test('FAST_STRICT/2 допускает исправления с invalidated snapshot, но запрещает закрыть review stage', function (): void {
    $root = fastStrictCreateFixture(complete: true, protocolVersion: 'FAST_STRICT/2');
    fastStrictWrite($root.'/src/Feature.php', "<?php\n\ndeclare(strict_types=1);\n\nfinal class ReviewFix {}\n");
    $statusPath = $root.'/plan/example-fast-tdd/STATUS.json';
    /** @var FastStrictFixtureStatus $status */
    $status = json_decode((string) file_get_contents($statusPath), true, 512, JSON_THROW_ON_ERROR);
    foreach ($status['evidence'] as $item) {
        if ($item['kind'] === 'handoff') {
            unlink($root.'/'.$item['path']);
        }
    }
    fastStrictMutateStatus($root, static function (array &$status): void {
        $status['overallState'] = 'active';
        $status['currentStage'] = '005';
        foreach ($status['stages'] as $index => &$stage) {
            $stage['state'] = $index < 5 ? 'complete' : ($index === 5 ? 'in_progress' : 'pending');
        }
        unset($stage);
        $status['snapshot']['state'] = 'invalidated';
        foreach ($status['gateResults'] as &$gateResult) {
            $gateResult['state'] = 'pending';
            $gateResult['evidenceId'] = null;
        }
        unset($gateResult);
        $status['evidence'] = array_values(array_filter(
            $status['evidence'],
            static fn (array $item): bool => $item['kind'] !== 'handoff',
        ));
        foreach ($status['tasks'] as $index => &$task) {
            $task['evidenceIds'] = array_values(array_filter(
                $task['evidenceIds'],
                static fn (string $id): bool => ! str_starts_with($id, 'EVID-HANDOFF-'),
            ));
            $task['handoffEvidenceId'] = null;
            if ($index === 5) {
                $task['state'] = 'in_progress';
            } elseif ($index === 6) {
                $task['state'] = 'pending';
                $task['executorId'] = null;
                $task['usedSkills'] = [];
                $task['evidenceIds'] = [];
            }
        }
        unset($task);
    });

    expect(fastStrictRun($root))->toBe(0)
        ->and(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n");

    expect(fastStrictRun($root, '006'))->toBe(1)
        ->and(Artisan::output())->toContain('FPV-EVID-005');
});

test('protocol filter проверяет V2 и не читает STATUS исторического V1', function (): void {
    $root = fastStrictCreateFixture(protocolVersion: 'FAST_STRICT/2');
    $legacyRoot = fastStrictCreateFixture();
    fastStrictCopyTree($legacyRoot.'/plan/example-fast-tdd', $root.'/plan/legacy-fast-tdd');
    fastStrictMutateJson($root.'/plan/legacy-fast-tdd/PLAN.json', static function (array &$plan): void {
        $plan['planId'] = 'legacy-fast-tdd';
        $plan['slug'] = 'legacy';
    });
    fastStrictWrite($root.'/plan/legacy-fast-tdd/STATUS.json', "not-json\n");

    expect(Artisan::call('plan:validate', [
        '--all' => true,
        '--protocol' => 'FAST_STRICT/2',
        '--root' => $root,
        '--no-interaction' => true,
    ]))->toBe(0)
        ->and(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n");
});

test('protocol filter отклоняет неизвестную версию и одиночный selector', function (array $arguments): void {
    $root = fastStrictCreateFixture(protocolVersion: 'FAST_STRICT/2');

    expect(Artisan::call('plan:validate', $arguments + [
        '--root' => $root,
        '--no-interaction' => true,
    ]))->toBe(2)
        ->and(Artisan::output())->toContain('FPV-CLI-007');
})->with([
    'неизвестная версия' => [[
        '--all' => true,
        '--protocol' => 'FAST_STRICT/999',
    ]],
    'filter с одиночным plan' => [[
        'plan' => 'plan/example-fast-tdd',
        '--protocol' => 'FAST_STRICT/2',
    ]],
]);

test('protocol filter сообщает о поврежденном или неизвестном PLAN', function (Closure $breakPlan): void {
    $root = fastStrictCreateFixture(protocolVersion: 'FAST_STRICT/2');
    $legacyRoot = fastStrictCreateFixture();
    fastStrictCopyTree($legacyRoot.'/plan/example-fast-tdd', $root.'/plan/broken-fast-tdd');
    $breakPlan($root.'/plan/broken-fast-tdd/PLAN.json');

    expect(Artisan::call('plan:validate', [
        '--all' => true,
        '--protocol' => 'FAST_STRICT/2',
        '--root' => $root,
        '--no-interaction' => true,
    ]))->toBe(1)
        ->and(Artisan::output())->toContain('FPV-SYS-002');
})->with([
    'неизвестная версия' => [fn (string $path) => fastStrictMutateJson($path, static function (array &$plan): void {
        $plan['protocolVersion'] = 'FAST_STRICT/999';
    })],
    'поврежденный JSON' => [fn (string $path) => fastStrictWrite($path, "not-json\n")],
]);

test('режим all принимает пустой plan root и проверяет планы в стабильном порядке', function (): void {
    $emptyRoot = fastStrictCreateEmptyRepository();

    expect(Artisan::call('plan:validate', [
        '--all' => true,
        '--root' => $emptyRoot,
        '--no-interaction' => true,
    ]))->toBe(0)
        ->and(Artisan::output())->toBe("SUMMARY plans=0 errors=0\n");

    $root = fastStrictCreateFixture();
    fastStrictClonePlan($root, 'alpha');
    fastStrictMutateJson($root.'/plan/example-fast-tdd/PLAN.json', static function (array &$plan): void {
        $plan['protocolVersion'] = 'BROKEN';
    });
    fastStrictMutateJson($root.'/plan/alpha-fast-tdd/PLAN.json', static function (array &$plan): void {
        $plan['protocolVersion'] = 'BROKEN';
    });

    expect(Artisan::call('plan:validate', [
        '--all' => true,
        '--root' => $root,
        '--no-interaction' => true,
    ]))->toBe(1);

    $output = Artisan::output();
    $alphaPosition = strpos($output, 'plan/alpha-fast-tdd/PLAN.json');
    $examplePosition = strpos($output, 'plan/example-fast-tdd/PLAN.json');
    assert($alphaPosition !== false && $examplePosition !== false);

    expect($alphaPosition)->toBeLessThan($examplePosition);
});

test('валидатор возвращает exit code 2 только для ошибки CLI или target', function (array $arguments, string $code): void {
    $root = fastStrictCreateFixture();
    $arguments['--root'] ??= $root;
    $arguments['--no-interaction'] = true;

    $exitCode = Artisan::call('plan:validate', $arguments);
    $output = Artisan::output();

    expect($exitCode)->toBe(2)
        ->and($output)->toContain($code)
        ->and($output)->toContain('SUMMARY');
})->with([
    'нет selector' => [[], 'FPV-CLI-002'],
    'selector задвоен' => [[
        'plan' => 'plan/example-fast-tdd',
        '--all' => true,
    ], 'FPV-CLI-002'],
    'phase неизвестен' => [[
        'plan' => 'plan/example-fast-tdd',
        '--phase' => '999',
    ], 'FPV-CLI-003'],
    'target не каноничен' => [[
        'plan' => './plan/example-fast-tdd',
    ], 'FPV-CLI-004'],
    'target отсутствует' => [[
        'plan' => 'plan/missing-fast-tdd',
    ], 'FPV-CLI-005'],
    'all не принимает явный stage' => [[
        '--all' => true,
        '--phase' => '006',
    ], 'FPV-CLI-006'],
    'root отсутствует' => [[
        'plan' => 'plan/example-fast-tdd',
        '--root' => '/definitely/missing/fast-strict-root',
    ], 'FPV-CLI-001'],
]);

test('валидатор проверяет обязательные системные файлы protocol и canonical plan root', function (Closure $mutate, string $code, bool $all): void {
    $root = fastStrictCreateFixture();
    $mutate($root);

    $arguments = $all
        ? ['--all' => true, '--root' => $root, '--no-interaction' => true]
        : ['plan' => 'plan/example-fast-tdd', '--root' => $root, '--no-interaction' => true];

    expect(Artisan::call('plan:validate', $arguments))->toBe(1)
        ->and(Artisan::output())->toContain($code);
})->with([
    'обязательный system file отсутствует' => [
        fn (string $root) => unlink($root.'/plan/README.md'),
        'FPV-SYS-001',
        true,
    ],
    'protocol не поддерживается' => [
        fn (string $root) => fastStrictMutateJson(
            $root.'/plan/example-fast-tdd/PLAN.json',
            static function (array &$plan): void {
                $plan['protocolVersion'] = 'FAST_STRICT/999';
            },
        ),
        'FPV-SYS-002',
        false,
    ],
    'plan root содержит неканонический каталог' => [
        fn (string $root) => mkdir($root.'/plan/Not-Canonical-fast-tdd'),
        'FPV-SYS-003',
        true,
    ],
    'system Markdown является symlink' => [
        function (string $root): void {
            unlink($root.'/plan/README.md');
            symlink($root.'/ARCHITECTURE.md', $root.'/plan/README.md');
        },
        'FPV-SYS-001',
        true,
    ],
    'system Markdown пустой' => [
        fn (string $root) => file_put_contents($root.'/ARCHITECTURE.md', ''),
        'FPV-FILE-002',
        true,
    ],
    'system Markdown имеет неверный UTF-8' => [
        fn (string $root) => file_put_contents($root.'/plan/README.md', "\xFF"),
        'FPV-FILE-002',
        true,
    ],
]);

test('валидатор отклоняет опасные или некорректные JSON-файлы', function (Closure $mutate, string $code): void {
    $root = fastStrictCreateFixture();
    $mutate($root);

    expect(fastStrictRun($root))->toBe(1)
        ->and(Artisan::output())->toContain($code);
})->with([
    'JSON больше одного MiB' => [
        fn (string $root) => file_put_contents(
            $root.'/plan/example-fast-tdd/STATUS.json',
            '{"padding":"'.str_repeat('x', 1_048_576).'"}',
        ),
        'FPV-JSON-001',
    ],
    'JSON синтаксически сломан' => [
        fn (string $root) => file_put_contents($root.'/plan/example-fast-tdd/STATUS.json', '{'),
        'FPV-JSON-002',
    ],
    'неизвестный вложенный ключ' => [
        fn (string $root) => fastStrictMutateJson(
            $root.'/plan/example-fast-tdd/PLAN.json',
            static function (array &$plan): void {
                /** @var array{tasks: list<array{skills: list<array<string, mixed>>}>} $plan */
                $plan['tasks'][0]['skills'][0]['unexpected'] = true;
            },
        ),
        'FPV-JSON-003',
    ],
    'обязательный ключ отсутствует' => [
        fn (string $root) => fastStrictMutateJson(
            $root.'/plan/example-fast-tdd/STATUS.json',
            static function (array &$status): void {
                /** @var array{tasks: list<array{handoffEvidenceId?: mixed}>} $status */
                unset($status['tasks'][0]['handoffEvidenceId']);
            },
        ),
        'FPV-JSON-003',
    ],
    'тип revision неверен' => [
        fn (string $root) => fastStrictMutateJson(
            $root.'/plan/example-fast-tdd/PLAN.json',
            static function (array &$plan): void {
                /** @var array{revision: mixed} $plan */
                $plan['revision'] = '1';
            },
        ),
        'FPV-JSON-004',
    ],
    'повтор requirement id' => [
        fn (string $root) => fastStrictMutateJson(
            $root.'/plan/example-fast-tdd/PLAN.json',
            static function (array &$plan): void {
                /** @var array{requirements: list<mixed>} $plan */
                $plan['requirements'][] = $plan['requirements'][0];
            },
        ),
        'FPV-JSON-005',
    ],
]);

test('валидатор проверяет frozen PLAN identity hashes traceability DAG stages skills и TDD-пару', function (Closure $mutate, string $code): void {
    $root = fastStrictCreateFixture();
    $mutate($root);

    expect(fastStrictRun($root))->toBe(1)
        ->and(Artisan::output())->toContain($code);
})->with([
    'slug не совпадает с каталогом' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['slug'] = 'other';
        }),
        'FPV-PLAN-001',
    ],
    'architecture path не корневой' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['architecture']['path'] = 'docs/'.'ARCHITECTURE.md';
        }),
        'FPV-PLAN-002',
    ],
    'architecture hash не совпадает' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['architecture']['sha256'] = str_repeat('0', 64);
        }),
        'FPV-PLAN-002',
    ],
    'acceptance не покрыт задачей' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['requirements'][0]['acceptance'][] = [
                'id' => 'AC-999',
                'statement' => 'Новый критерий должен быть покрыт задачей.',
            ];
        }),
        'FPV-PLAN-003',
    ],
    'dependency образует цикл' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][0]['dependsOn'] = ['TASK-007'];
            $plan['tasks'][6]['dependsOn'] = ['TASK-001'];
        }),
        'FPV-PLAN-004',
    ],
    'RED находится не в stage 002' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][4]['stage'] = '001';
        }),
        'FPV-PLAN-004',
    ],
    'check kind расходится с verificationMode' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][6]['checks'][0]['kind'] = 'contract';
        }),
        'FPV-PLAN-004',
    ],
    'Ponytail отсутствует у task' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            array_pop($plan['tasks'][0]['skills']);
        }),
        'FPV-PLAN-006',
    ],
    'Stage 000 имеет неверную роль' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][0]['agentRole'] = 'business_analyst';
        }),
        'FPV-PLAN-004',
    ],
    'Stage 000 не read only subagent' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][1]['executionMode'] = 'orchestrator';
        }),
        'FPV-PLAN-004',
    ],
    'review axis id не фиксирован' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['reviewAxes'][0]['id'] = 'REV-OTHER';
        }),
        'FPV-PLAN-007',
    ],
    'review axis не имеет Ponytail review' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            array_pop($plan['reviewAxes'][0]['skills']);
        }),
        'FPV-PLAN-007',
    ],
    'небезопасный owned path' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][4]['ownedPaths'] = ['../outside.php'];
        }),
        'FPV-PATH-001',
    ],
    'owned path не ASCII' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][4]['ownedPaths'] = ['src/Файл.php'];
        }),
        'FPV-PATH-001',
    ],
    'owned path выходит из repository' => [
        function (string $root): void {
            symlink(sys_get_temp_dir(), $root.'/outside-link');
            fastStrictMutatePlan($root, static function (array &$plan): void {
                $plan['tasks'][4]['ownedPaths'] = ['outside-link/not-created.php'];
            });
        },
        'FPV-PATH-002',
    ],
    'owned path проходит через внутренний symlink' => [
        function (string $root): void {
            mkdir($root.'/inside-target');
            symlink($root.'/inside-target', $root.'/inside-link');
            fastStrictMutatePlan($root, static function (array &$plan): void {
                $plan['tasks'][4]['ownedPaths'] = ['inside-link/not-created.php'];
            });
        },
        'FPV-PATH-003',
    ],
    'пересекающиеся leases writers' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][5]['ownedPaths'] = $plan['tasks'][4]['ownedPaths'];
        }),
        'FPV-PLAN-005',
    ],
    'gate запускает host PHP' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['gates'][0]['command'] = 'php artisan test';
        }),
        'FPV-PLAN-008',
    ],
]);

test('одна primary PLAN ошибка не каскадирует в STATUS readiness', function (): void {
    $root = fastStrictCreateFixture();
    fastStrictMutatePlan($root, static function (array &$plan): void {
        array_pop($plan['tasks'][0]['skills']);
    });

    $exitCode = fastStrictRun($root);
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('FPV-PLAN-006')
        ->and($output)->toContain("SUMMARY plans=1 errors=1\n");
});

test('валидатор проверяет mutable STATUS writers blockers и skill audit', function (Closure $mutate, string $code): void {
    $root = fastStrictCreateFixture();
    $mutate($root);

    expect(fastStrictRun($root))->toBe(1)
        ->and(Artisan::output())->toContain($code);
})->with([
    'plan hash не совпадает' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['planSha256'] = str_repeat('0', 64);
        }),
        'FPV-STATUS-001',
    ],
    'currentStage расходится со stage states' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['stages'][0]['state'] = 'complete';
        }),
        'FPV-STATUS-002',
    ],
    'task отсутствует' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            array_pop($status['tasks']);
        }),
        'FPV-STATUS-003',
    ],
    'неизвестный skill отмечен used' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][0]['usedSkills'] = ['not-in-plan'];
        }),
        'FPV-STATUS-004',
    ],
    'четыре active writers' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['activeWriters'] = [
                ['writerId' => 'writer-1', 'taskIds' => ['TASK-004']],
                ['writerId' => 'writer-2', 'taskIds' => ['TASK-005']],
                ['writerId' => 'writer-3', 'taskIds' => ['TASK-006']],
                ['writerId' => 'writer-4', 'taskIds' => ['TASK-007']],
            ];
        }),
        'FPV-STATUS-005',
    ],
    'writer получает пересекающиеся paths' => [
        function (string $root): void {
            fastStrictMutatePlan($root, static function (array &$plan): void {
                $plan['tasks'][5]['ownedPaths'] = $plan['tasks'][4]['ownedPaths'];
            });
            fastStrictMutateStatus($root, static function (array &$status): void {
                $status['currentStage'] = '005';
                foreach (range(0, 4) as $stage) {
                    $status['stages'][$stage]['state'] = 'complete';
                }
                $status['stages'][5]['state'] = 'in_progress';
                $status['tasks'][4]['state'] = 'in_progress';
                $status['tasks'][4]['executorId'] = 'writer-1';
                $status['tasks'][5]['state'] = 'in_progress';
                $status['tasks'][5]['executorId'] = 'writer-2';
                $status['activeWriters'] = [
                    ['writerId' => 'writer-1', 'taskIds' => ['TASK-005']],
                    ['writerId' => 'writer-2', 'taskIds' => ['TASK-006']],
                ];
            });
        },
        'FPV-PLAN-005',
    ],
    'blocked без blocker' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['overallState'] = 'blocked';
            $status['stages'][0]['state'] = 'blocked';
            $status['tasks'][0]['state'] = 'blocked';
            $status['tasks'][0]['executorId'] = 'blocked-agent';
        }),
        'FPV-STATUS-006',
    ],
    'blocked сохраняет неверную stage progression' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['overallState'] = 'blocked';
            $status['tasks'][0]['state'] = 'blocked';
            $status['tasks'][0]['executorId'] = 'blocked-agent';
            $status['blockers'] = [[
                'id' => 'BLOCK-001',
                'taskId' => 'TASK-001',
                'reason' => 'Waiting for an approved decision.',
                'openedAt' => '2026-08-20T00:00:00Z',
            ]];
        }),
        'FPV-STATUS-002',
    ],
]);

test('STATUS строго проверяет executor skill ledgers и blockers', function (Closure $mutate, string $code): void {
    $root = fastStrictCreateFixture(complete: true);
    $mutate($root);

    expect(fastStrictRun($root, '007'))->toBe(1)
        ->and(Artisan::output())->toContain($code);
})->with([
    'started task не имеет executor' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][0]['executorId'] = null;
        }),
        'FPV-STATUS-003',
    ],
    'usedSkills меняет порядок PLAN' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][0]['usedSkills'] = array_reverse($status['tasks'][0]['usedSkills']);
        }),
        'FPV-STATUS-004',
    ],
    'skill одновременно used и unavailable' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][0]['unavailableSkills'] = [[
                'id' => $status['tasks'][0]['usedSkills'][0],
                'reason' => 'Unavailable after execution.',
            ]];
        }),
        'FPV-STATUS-004',
    ],
    'blocker имеет неизвестный taskId' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['overallState'] = 'blocked';
            $status['currentStage'] = '007';
            $status['stages'][7]['state'] = 'blocked';
            $status['tasks'][6]['state'] = 'blocked';
            $status['blockers'] = [[
                'id' => 'BLOCK-001',
                'taskId' => 'TASK-999',
                'reason' => 'Unknown task.',
                'openedAt' => '2026-08-20T00:00:00Z',
            ]];
        }),
        'FPV-STATUS-006',
    ],
]);

test('валидатор проверяет evidence hashes RED GREEN manifests reviews и completion', function (Closure $mutate, string $code): void {
    $root = fastStrictCreateFixture(complete: true);
    $mutate($root);

    expect(fastStrictRun($root, '007'))->toBe(1)
        ->and(Artisan::output())->toContain($code);
})->with([
    'evidence file изменен' => [
        fn (string $root) => file_put_contents(
            $root.'/plan/example-fast-tdd/evidence/EVID-RED-005.md',
            "tampered\n",
        ),
        'FPV-EVID-002',
    ],
    'RED ошибочно имеет exit 0' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['evidence'][fastStrictEvidenceIndex($status, 'EVID-RED-005')]['exitCode'] = 0;
        }),
        'FPV-EVID-003',
    ],
    'GREEN наблюдает другую команду' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['evidence'][fastStrictEvidenceIndex($status, 'EVID-GREEN-005')]['command'] = 'make another-command';
        }),
        'FPV-EVID-004',
    ],
    'evidence ссылается на неизвестную задачу' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['evidence'][fastStrictEvidenceIndex($status, 'EVID-QA-005')]['taskId'] = 'TASK-999';
        }),
        'FPV-EVID-001',
    ],
    'gate evidence имеет non-zero exit' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['evidence'][fastStrictEvidenceIndex($status, 'EVID-GATE-QUALITY')]['exitCode'] = 1;
        }),
        'FPV-EVID-005',
    ],
    'обязательный final manifest отсутствует' => [
        fn (string $root) => unlink($root.'/plan/example-fast-tdd/evidence/final.sha256'),
        'FPV-MANIFEST-001',
    ],
    'final manifest содержит traversal' => [
        fn (string $root) => file_put_contents(
            $root.'/plan/example-fast-tdd/evidence/final.sha256',
            str_repeat('a', 64)."  ../outside.php\n",
        ),
        'FPV-MANIFEST-002',
    ],
    'final manifest не покрывает весь write surface' => [
        fn (string $root) => file_put_contents(
            $root.'/plan/example-fast-tdd/evidence/final.sha256',
            fastStrictSha256($root.'/docs/example.md')."  docs/example.md\n",
        ),
        'FPV-MANIFEST-003',
    ],
    'snapshot bundle hash не соответствует материалам' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['snapshot']['bundleSha256'] = str_repeat('0', 64);
        }),
        'FPV-MANIFEST-004',
    ],
    'reviewers повторяются' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['reviews'][1]['reviewerId'] = $status['reviews'][0]['reviewerId'];
        }),
        'FPV-REVIEW-002',
    ],
    'review смотрит другой snapshot' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['reviews'][0]['snapshotSha256'] = str_repeat('0', 64);
        }),
        'FPV-REVIEW-003',
    ],
    'review держит B1' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['reviews'][0]['b1'] = 1;
        }),
        'FPV-REVIEW-004',
    ],
    'review пропускает обязательный skill' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['reviews'][0]['skippedSkills'] = [[
                'id' => 'ponytail:ponytail',
                'reason' => 'Skipped.',
            ]];
        }),
        'FPV-REVIEW-001',
    ],
    'complete без трех reviews' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            array_pop($status['reviews']);
        }),
        'FPV-REVIEW-001',
    ],
    'complete имеет active writer' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['activeWriters'] = [[
                'writerId' => 'late-writer',
                'taskIds' => ['TASK-004'],
            ]];
        }),
        'FPV-COMPLETE-001',
    ],
]);

test('одно отсутствующее review не каскадирует в readiness и completion', function (): void {
    $root = fastStrictCreateFixture(complete: true);
    fastStrictMutateStatus($root, static function (array &$status): void {
        array_pop($status['reviews']);
    });

    $exitCode = fastStrictRun($root, '007');
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('FPV-REVIEW-001')
        ->and($output)->toContain("SUMMARY plans=1 errors=1\n");
});

test('числовая phase проверяет готовность закрыть этап', function (string $phase, Closure $mutate, string $code): void {
    $root = fastStrictCreateFixture(complete: true);
    $mutate($root);

    expect(fastStrictRun($root, $phase))->toBe(1)
        ->and(Artisan::output())->toContain($code);
})->with([
    '000 требует независимых executors' => [
        '000',
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][1]['executorId'] = $status['tasks'][0]['executorId'];
        }),
        'FPV-STATUS-003',
    ],
    '001 запрещает active writers' => [
        '001',
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['activeWriters'] = [[
                'writerId' => 'writer-1',
                'taskIds' => ['TASK-006'],
            ]];
        }),
        'FPV-STATUS-005',
    ],
    '002 требует RED для каждого check' => [
        '002',
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][4]['checks'][] = [
                'id' => 'CHECK-105',
                'kind' => 'tdd',
                'command' => 'make second-focused-check',
                'expected' => 'nonzero because behavior is absent',
            ];
        }),
        'FPV-EVID-003',
    ],
    '003 требует same-command GREEN' => [
        '003',
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $index = fastStrictEvidenceIndex($status, 'EVID-GREEN-005');
            array_splice($status['evidence'], $index, 1);
            $status['tasks'][4]['evidenceIds'] = array_values(array_filter(
                $status['tasks'][4]['evidenceIds'],
                static fn (string $id): bool => $id !== 'EVID-GREEN-005',
            ));
        }),
        'FPV-EVID-004',
    ],
    '004 требует завершить TDD task' => [
        '004',
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][4]['state'] = 'in_progress';
        }),
        'FPV-STATUS-003',
    ],
    '005 требует passed gates' => [
        '005',
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $index = fastStrictEvidenceIndex($status, 'EVID-GATE-QUALITY');
            $status['gateResults'][1]['state'] = 'failed';
            $status['evidence'][$index]['exitCode'] = 1;
        }),
        'FPV-EVID-005',
    ],
    '006 требует frozen snapshot и три GO' => [
        '006',
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['snapshot'] = [
                'state' => 'pending',
                'round' => 0,
                'manifestPath' => null,
                'manifestSha256' => null,
                'bundleSha256' => null,
            ];
        }),
        'FPV-REVIEW-003',
    ],
    '007 требует handoff правильного kind stage и task' => [
        '007',
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][0]['handoffEvidenceId'] = 'EVID-ANALYSIS-001';
        }),
        'FPV-COMPLETE-001',
    ],
]);

test('Stage 001 закрывается task-bound baseline, а Stage 002 допускает активного writer', function (): void {
    $root = fastStrictCreateFixture(complete: true);
    fastStrictRewindToStageTwo($root, false);

    expect(fastStrictRun($root, '001'))->toBe(0)
        ->and(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n");

    fastStrictRewindToStageTwo($root, true);

    expect(fastStrictRun($root))->toBe(0)
        ->and(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n");
});

test('task lifecycle связывает dependencies и evidence в обе стороны', function (bool $complete, Closure $mutate): void {
    $root = fastStrictCreateFixture(complete: $complete);
    $mutate($root);

    expect(fastStrictRun($root, $complete ? '007' : 'current'))->toBe(1)
        ->and(Artisan::output())->toContain('FPV-STATUS-003');
})->with([
    'started task имеет незавершенную dependency' => [
        true,
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][3]['state'] = 'pending';
        }),
    ],
    'task присваивает чужой evidence' => [
        true,
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][0]['evidenceIds'][] = 'EVID-ANALYSIS-002';
            sort($status['tasks'][0]['evidenceIds'], SORT_STRING);
        }),
    ],
    'evidence не указан в STATUS task' => [
        true,
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][4]['evidenceIds'] = array_values(array_filter(
                $status['tasks'][4]['evidenceIds'],
                static fn (string $id): bool => $id !== 'EVID-RED-005',
            ));
        }),
    ],
    'future task prefilled до своего stage' => [
        false,
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['tasks'][5]['state'] = 'in_progress';
            $status['tasks'][5]['executorId'] = 'writer-future';
            $status['tasks'][5]['usedSkills'] = ['laravel-development', 'ponytail:ponytail'];
        }),
    ],
]);

test('trust boundary отклоняет MISSING drift control chars invalid dates и пустые UTF-8 artifacts', function (Closure $mutate, string $code): void {
    $root = fastStrictCreateFixture(complete: true);
    $mutate($root);

    expect(fastStrictRun($root, '007'))->toBe(1)
        ->and(Artisan::output())->toContain($code);
})->with([
    'MISSING указан не для deletePath' => [
        fn (string $root) => file_put_contents(
            $root.'/plan/example-fast-tdd/evidence/final.sha256',
            "MISSING  docs/example.md\n".fastStrictSha256($root.'/src/Feature.php')."  src/Feature.php\n",
        ),
        'FPV-MANIFEST-004',
    ],
    'failed gate использует чужую команду' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $index = fastStrictEvidenceIndex($status, 'EVID-GATE-QUALITY');
            $status['gateResults'][1]['state'] = 'failed';
            $status['evidence'][$index]['command'] = 'make foreign-gate';
            $status['evidence'][$index]['exitCode'] = 1;
        }),
        'FPV-EVID-005',
    ],
    'declared path содержит ASCII control' => [
        fn (string $root) => fastStrictMutatePlan($root, static function (array &$plan): void {
            $plan['tasks'][4]['ownedPaths'] = ["src/\tFeature.php"];
        }),
        'FPV-PATH-001',
    ],
    'timestamp описывает невозможную дату' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['updatedAt'] = '2026-02-30T00:00:00Z';
        }),
        'FPV-JSON-004',
    ],
    'Markdown evidence пустой при верном hash' => [
        function (string $root): void {
            $path = $root.'/plan/example-fast-tdd/evidence/EVID-RED-005.md';
            file_put_contents($path, '');
            fastStrictMutateStatus($root, static function (array &$status) use ($path): void {
                $status['evidence'][fastStrictEvidenceIndex($status, 'EVID-RED-005')]['sha256'] = fastStrictSha256($path);
            });
        },
        'FPV-EVID-002',
    ],
    'Markdown evidence является неполным placeholder' => [
        function (string $root): void {
            $path = $root.'/plan/example-fast-tdd/evidence/EVID-RED-005.md';
            file_put_contents($path, "# EVID-RED-005\n");
            fastStrictMutateStatus($root, static function (array &$status) use ($path): void {
                $status['evidence'][fastStrictEvidenceIndex($status, 'EVID-RED-005')]['sha256'] = fastStrictSha256($path);
            });
        },
        'FPV-EVID-002',
    ],
    'review evidence пустой при верном hash' => [
        function (string $root): void {
            $path = $root.'/plan/example-fast-tdd/reviews/round-1-security-privacy-data.md';
            file_put_contents($path, '');
            fastStrictMutateStatus($root, static function (array &$status) use ($path): void {
                $status['reviews'][0]['evidenceSha256'] = fastStrictSha256($path);
            });
        },
        'FPV-REVIEW-001',
    ],
    'analysis evidence имеет неверный stage' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['evidence'][fastStrictEvidenceIndex($status, 'EVID-ANALYSIS-001')]['stage'] = '001';
        }),
        'FPV-EVID-001',
    ],
    'baseline evidence задвоен' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $copy = $status['evidence'][fastStrictEvidenceIndex($status, 'EVID-BASELINE')];
            $copy['id'] = 'EVID-BASELINE-SECOND';
            $status['evidence'][] = $copy;
            usort($status['evidence'], static fn (array $left, array $right): int => $left['id'] <=> $right['id']);
        }),
        'FPV-EVID-001',
    ],
    'final manifest ошибочно объявлен evidence path' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status) use ($root): void {
            $index = fastStrictEvidenceIndex($status, 'EVID-ANALYSIS-001');
            $status['evidence'][$index]['path'] = 'plan/example-fast-tdd/evidence/final.sha256';
            $status['evidence'][$index]['sha256'] = hash_file(
                'sha256',
                $root.'/plan/example-fast-tdd/evidence/final.sha256',
            );
        }),
        'FPV-EVID-002',
    ],
    'invalidated snapshot не сохраняет прошлые hashes' => [
        fn (string $root) => fastStrictMutateStatus($root, static function (array &$status): void {
            $status['snapshot']['state'] = 'invalidated';
            $status['snapshot']['manifestSha256'] = str_repeat('0', 64);
        }),
        'FPV-MANIFEST-004',
    ],
]);

test('invalidated snapshot сохраняет immutable review evidence прошлого раунда', function (): void {
    $root = fastStrictCreateFixture(complete: true);
    $statusPath = $root.'/plan/example-fast-tdd/STATUS.json';
    $decoded = json_decode((string) file_get_contents($statusPath), true, 512, JSON_THROW_ON_ERROR);
    assert(is_array($decoded));
    $reviews = $decoded['reviews'] ?? null;
    $evidence = $decoded['evidence'] ?? null;
    assert(is_array($reviews) && is_array($evidence));
    $reviewLines = [];
    foreach ($reviews as $review) {
        assert(is_array($review) && is_string($review['evidencePath'] ?? null));
        $reviewLines[] = hash_file('sha256', $root.'/'.$review['evidencePath']).'  '.basename($review['evidencePath']);
    }
    foreach ($evidence as $item) {
        if (is_array($item) && ($item['kind'] ?? null) === 'handoff' && is_string($item['path'] ?? null)) {
            unlink($root.'/'.$item['path']);
        }
    }
    $ledgerPath = 'plan/example-fast-tdd/evidence/EVID-GATE-REVIEW-LEDGER-R1.md';
    $ledger = "# EVID-GATE-REVIEW-LEDGER-R1\n\nStage: 005\nTask: none\nCheck: none\nCommand: Verify immutable historical review ledger.\nExit code: 0\nExpected: prior reviews remain immutable.\nObserved: review hashes recorded.\n\n".implode("\n", $reviewLines)."\n\nRedacted: raw output omitted.\nLimitations: fixture only.\n";
    fastStrictWrite($root.'/'.$ledgerPath, $ledger);

    fastStrictMutateStatus($root, static function (array &$status) use ($root, $ledgerPath, $ledger): void {
        $status['overallState'] = 'active';
        $status['currentStage'] = '006';
        $status['stages'][6]['state'] = 'in_progress';
        $status['stages'][7]['state'] = 'pending';
        $status['activeWriters'] = [];
        $status['snapshot']['state'] = 'invalidated';
        $status['evidence'] = array_values(array_filter(
            $status['evidence'],
            static fn (array $item): bool => $item['kind'] !== 'handoff',
        ));
        foreach ($status['tasks'] as &$task) {
            $task['evidenceIds'] = array_values(array_filter(
                $task['evidenceIds'],
                static fn (string $id): bool => ! str_starts_with($id, 'EVID-HANDOFF-'),
            ));
            $task['handoffEvidenceId'] = null;
        }
        unset($task);
        $status['tasks'][6]['state'] = 'pending';
        $status['tasks'][6]['executorId'] = null;
        $status['tasks'][6]['usedSkills'] = [];
        $status['tasks'][6]['evidenceIds'] = [];
        $status['tasks'][6]['handoffEvidenceId'] = null;
        $status['evidence'][] = [
            'id' => 'EVID-GATE-REVIEW-LEDGER-R1',
            'stage' => '005',
            'taskId' => null,
            'checkId' => null,
            'kind' => 'gate',
            'path' => $ledgerPath,
            'sha256' => hash('sha256', $ledger),
            'command' => 'Verify immutable historical review ledger.',
            'exitCode' => 0,
            'observedAt' => '2026-08-20T12:00:00Z',
        ];
        usort($status['evidence'], static fn (array $left, array $right): int => $left['id'] <=> $right['id']);
        $status['snapshot']['bundleSha256'] = fastStrictBundleSha256($root, $status['evidence']);
        foreach ($status['reviews'] as &$review) {
            $review['reviewerId'] = null;
            $review['verdict'] = 'pending';
            $review['round'] = 0;
            $review['snapshotSha256'] = null;
            $review['evidencePath'] = null;
            $review['evidenceSha256'] = null;
            $review['usedSkills'] = [];
            $review['unavailableSkills'] = [];
            $review['skippedSkills'] = [];
            $review['b0'] = 0;
            $review['b1'] = 0;
            $review['b2'] = 0;
        }
        unset($review);
    });

    fastStrictRun($root);
    expect(Artisan::output())->toContain('SUMMARY plans=1 errors=0');

    $reviewPath = $root.'/plan/example-fast-tdd/reviews/round-1-security-privacy-data.md';
    file_put_contents($reviewPath, "tampered\n", FILE_APPEND);
    expect(fastStrictRun($root))->toBe(1)
        ->and(Artisan::output())->toContain('FPV-REVIEW-003');
});

test('ранний superseded план остается исторически валиден без будущих manifests', function (): void {
    $root = fastStrictCreateFixture();
    fastStrictClonePlan($root, 'example-v2');
    fastStrictMutateJson($root.'/plan/example-v2-fast-tdd/PLAN.json', static function (array &$plan): void {
        $plan['revision'] = 2;
        $plan['supersedes'] = 'example-fast-tdd';
    });
    fastStrictMutateJson($root.'/plan/example-v2-fast-tdd/STATUS.json', static function (array &$status) use ($root): void {
        $status['planRevision'] = 2;
        $status['planSha256'] = fastStrictSha256($root.'/plan/example-v2-fast-tdd/PLAN.json');
    });
    fastStrictMutateStatus($root, static function (array &$status): void {
        $status['overallState'] = 'superseded';
        $status['supersededBy'] = 'example-v2-fast-tdd';
    });
    file_put_contents($root.'/ARCHITECTURE.md', "# Later architecture\n");

    expect(fastStrictRun($root))->toBe(0)
        ->and(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n");
});

test('superseded требует существующий canonical successor с обратной ссылкой', function (): void {
    $root = fastStrictCreateFixture();
    fastStrictMutateStatus($root, static function (array &$status): void {
        $status['overallState'] = 'superseded';
        $status['supersededBy'] = 'replacement-fast-tdd';
    });

    expect(fastStrictRun($root))->toBe(1)
        ->and(Artisan::output())->toContain('FPV-STATUS-002');
});

test('immutable V1 hash проверяется и у завершенного плана', function (): void {
    $root = fastStrictCreateFixture(complete: true);
    file_put_contents($root.'/plan/FAST_STRICT_V1.md', "# tampered protocol\n");

    expect(fastStrictRun($root, '007'))->toBe(1)
        ->and(Artisan::output())->toContain('FPV-PLAN-002');
});

test('architecture conformance хранит каждый frozen ruleId PLAN', function (): void {
    $root = fastStrictCreateFixture();
    file_put_contents(
        $root.'/plan/example-fast-tdd/ARCHITECTURE_CONFORMANCE.md',
        "# Architecture Conformance\n\nDeviations: none\n",
    );

    expect(fastStrictRun($root))->toBe(1)
        ->and(Artisan::output())->toContain('FPV-FILE-002');
});

test('завершенный план сохраняет историческую валидность и допускает B2 с rationale и owner', function (): void {
    $root = fastStrictCreateFixture(complete: true);
    file_put_contents($root.'/src/Feature.php', "<?php\n// later legitimate plan\n");

    $reviewPath = $root.'/plan/example-fast-tdd/reviews/round-1-security-privacy-data.md';
    file_put_contents($reviewPath, "\nB2 rationale: accepted minor documentation debt.\nB2 owner: docs-team.\n", FILE_APPEND);
    fastStrictMutateStatus($root, static function (array &$status) use ($reviewPath): void {
        $status['reviews'][0]['b2'] = 1;
        $status['reviews'][0]['evidenceSha256'] = fastStrictSha256($reviewPath);
    });

    expect(fastStrictRun($root, '007'))->toBe(0)
        ->and(Artisan::output())->toBe("SUMMARY plans=1 errors=0\n");
});

test('валидатор требует закрытый набор plan docs и OWNERSHIP при нескольких writers', function (Closure $mutate, string $code): void {
    $root = fastStrictCreateFixture();
    $mutate($root);

    expect(fastStrictRun($root))->toBe(1)
        ->and(Artisan::output())->toContain($code);
})->with([
    'обязательный doc отсутствует' => [
        fn (string $root) => unlink($root.'/plan/example-fast-tdd/TRACEABILITY.md'),
        'FPV-FILE-001',
    ],
    'Markdown projection не содержит обязательных headings' => [
        fn (string $root) => file_put_contents($root.'/plan/example-fast-tdd/README.md', "# Broken\n"),
        'FPV-FILE-002',
    ],
    'TASKS projection является placeholder' => [
        fn (string $root) => file_put_contents($root.'/plan/example-fast-tdd/TASKS.md', "# Tasks\n"),
        'FPV-FILE-002',
    ],
    'TRACEABILITY projection является placeholder' => [
        fn (string $root) => file_put_contents($root.'/plan/example-fast-tdd/TRACEABILITY.md', "# Traceability\n"),
        'FPV-FILE-002',
    ],
    'пустой reviews directory создан до review evidence' => [
        fn (string $root) => mkdir($root.'/plan/example-fast-tdd/reviews'),
        'FPV-FILE-003',
    ],
    'OWNERSHIP отсутствует для двух active writers' => [
        function (string $root): void {
            fastStrictWrite($root.'/plan/example-fast-tdd/OWNERSHIP.md', "# Ownership\n\nOrchestrator reservation.\n");
            fastStrictMutateStatus($root, static function (array &$status): void {
                $status['tasks'][4]['state'] = 'in_progress';
                $status['tasks'][4]['executorId'] = 'writer-1';
                $status['tasks'][5]['state'] = 'in_progress';
                $status['tasks'][5]['executorId'] = 'writer-2';
                $status['activeWriters'] = [
                    ['writerId' => 'writer-1', 'taskIds' => ['TASK-005']],
                    ['writerId' => 'writer-2', 'taskIds' => ['TASK-006']],
                ];
            });
            unlink($root.'/plan/example-fast-tdd/OWNERSHIP.md');
        },
        'FPV-FILE-001',
    ],
    'неизвестный top-level файл' => [
        fn (string $root) => file_put_contents($root.'/plan/example-fast-tdd/EXTRA.md', "extra\n"),
        'FPV-FILE-003',
    ],
    'symlink внутри plan' => [
        fn (string $root) => symlink($root.'/ARCHITECTURE.md', $root.'/plan/example-fast-tdd/LINK.md'),
        'FPV-PATH-003',
    ],
    'canonical plan directory является symlink' => [
        function (string $root): void {
            rename($root.'/plan/example-fast-tdd', $root.'/stored-plan');
            symlink($root.'/stored-plan', $root.'/plan/example-fast-tdd');
        },
        'FPV-PATH-003',
    ],
    'canonical file имеет неверный регистр' => [
        fn (string $root) => rename(
            $root.'/plan/example-fast-tdd/PLAN.json',
            $root.'/plan/example-fast-tdd/plan.json',
        ),
        'FPV-PATH-004',
    ],
]);

test('валидатор сортирует независимые ошибки по path pointer и code', function (): void {
    $root = fastStrictCreateFixture();
    fastStrictMutatePlan($root, static function (array &$plan): void {
        $plan['architecture']['sha256'] = str_repeat('f', 64);
        array_pop($plan['tasks'][0]['skills']);
    });

    expect(fastStrictRun($root))->toBe(1);
    $first = Artisan::output();
    expect(fastStrictRun($root))->toBe(1);
    $second = Artisan::output();

    $architecturePosition = strpos($first, 'FPV-PLAN-002');
    $skillPosition = strpos($first, 'FPV-PLAN-006');
    assert($architecturePosition !== false && $skillPosition !== false);

    expect($second)->toBe($first)
        ->and($architecturePosition)->toBeLessThan($skillPosition);
});

/** @return list<string> */
function fastStrictFixtureRoots(?string $add = null, bool $reset = false): array
{
    /** @var list<string> $roots */
    static $roots = [];

    if ($reset) {
        $roots = [];
    }

    if ($add !== null) {
        $roots[] = $add;
    }

    return $roots;
}

function fastStrictCreateEmptyRepository(string $protocolVersion = 'FAST_STRICT/1'): string
{
    $root = sys_get_temp_dir().'/jobgram-fast-strict-'.bin2hex(random_bytes(8));
    fastStrictFixtureRoots($root);
    fastStrictWrite($root.'/ARCHITECTURE.md', "# Architecture\n\n[ARCH-PLAN-001] Rule.\n");
    fastStrictWrite($root.'/backend/artisan', "#!/usr/bin/env php\n");
    fastStrictWrite($root.'/plan/FAST_STRICT_V1.md', "# FAST_STRICT/1\n");
    fastStrictWrite($root.'/plan/FAST_STRICT_V2.md', "# FAST_STRICT/2\n");
    fastStrictWrite($root.'/plan/README.md', "# Planning\n\n{$protocolVersion} operator guide.\n");

    return $root;
}

function fastStrictCreateFixture(bool $complete = false, string $protocolVersion = 'FAST_STRICT/1'): string
{
    $root = fastStrictCreateEmptyRepository($protocolVersion);
    $directory = $root.'/plan/example-fast-tdd';

    fastStrictWrite($root.'/src/Feature.php', "<?php\n\ndeclare(strict_types=1);\n");
    fastStrictWrite($root.'/backend/tests/Feature/ExampleTest.php', "<?php\n\ntest('пример', fn () => true);\n");
    fastStrictWrite($root.'/docs/example.md', "# Example\n");

    fastStrictWrite($directory.'/README.md', "# Goal\n\nExample.\n\n# Scope\n\nExample.\n\n# Non-goals\n\nNone.\n\n# Operator Notes\n\nRead only.\n");
    fastStrictWrite($directory.'/ARCHITECTURE_CONFORMANCE.md', "# Architecture Conformance\n\nARCH-PLAN-001\n\nDeviations: none\n");
    fastStrictWrite($directory.'/DECISIONS.md', "# Accepted Decisions\n\nUse {$protocolVersion}.\n\n# Rejected Alternatives\n\nNo sidecar schema.\n");
    fastStrictWrite($directory.'/evidence/command-log.md', "# Command Log\n\nRedacted evidence index.\n");

    $plan = fastStrictPlan($root, $protocolVersion);
    fastStrictWrite(
        $directory.'/TASKS.md',
        "# Tasks\n\n```json\n".json_encode($plan['tasks'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n```\n",
    );
    fastStrictWrite(
        $directory.'/TRACEABILITY.md',
        "# Traceability\n\n```json\n".json_encode([
            'requirements' => $plan['requirements'],
            'tasks' => $plan['tasks'],
            'gates' => $plan['gates'],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n```\n",
    );
    fastStrictWriteJson($directory.'/PLAN.json', $plan);

    if ($complete) {
        $manifest = fastStrictManifest($root, fastStrictOwnedPaths($plan));
        fastStrictWrite($directory.'/evidence/baseline.sha256', $manifest);
        fastStrictWrite($directory.'/evidence/final.sha256', $manifest);
    }

    $status = fastStrictStatus($root, $plan, $complete);
    fastStrictWriteJson($directory.'/STATUS.json', $status);

    return $root;
}

/** @return FastStrictFixturePlan */
function fastStrictPlan(string $root, string $protocolVersion = 'FAST_STRICT/1'): array
{
    $tasks = [
        fastStrictTask('TASK-001', '000', 'Business analysis', 'business_system', 'read_only_subagent', [], [], 'inspection', 'make dev-plan-check'),
        fastStrictTask('TASK-002', '000', 'Architecture analysis', 'architecture_codebase', 'read_only_subagent', [], [], 'inspection', 'make dev-plan-check'),
        fastStrictTask('TASK-003', '000', 'QA risk analysis', 'qa_risk', 'read_only_subagent', [], [], 'inspection', 'make dev-plan-check'),
        fastStrictTask('TASK-004', '001', 'Discovery and baseline', 'orchestrator', 'orchestrator', ['TASK-001', 'TASK-002', 'TASK-003'], [], 'inspection', 'make dev-plan-check'),
        fastStrictTask('TASK-005', '002', 'Focused RED and GREEN', 'backend_tdd', 'writer', ['TASK-004'], ['backend/tests/Feature/ExampleTest.php', 'src/Feature.php'], 'tdd', 'make dev-test-filter filter=ExampleTest'),
        fastStrictTask('TASK-006', '005', 'Integration docs and gates', 'integration', 'writer', ['TASK-005'], ['docs/example.md'], 'inspection', 'make quality'),
        fastStrictTask('TASK-007', '007', 'Final integrity and handoff', 'orchestrator', 'orchestrator', ['TASK-006'], [], 'inspection', 'make dev-plan-check'),
    ];

    return [
        'protocolVersion' => $protocolVersion,
        'planId' => 'example-fast-tdd',
        'slug' => 'example',
        'revision' => 1,
        'supersedes' => null,
        'title' => 'Example feature',
        'goal' => 'Deliver one observable example feature.',
        'createdAt' => '2026-08-20T00:00:00Z',
        'architecture' => [
            'path' => 'ARCHITECTURE.md',
            'sha256' => fastStrictSha256($root.'/ARCHITECTURE.md'),
            'ruleIds' => ['ARCH-PLAN-001'],
        ],
        'protocol' => [
            'path' => $protocolVersion === 'FAST_STRICT/2' ? 'plan/FAST_STRICT_V2.md' : 'plan/FAST_STRICT_V1.md',
            'sha256' => fastStrictSha256($root.'/plan/'.($protocolVersion === 'FAST_STRICT/2' ? 'FAST_STRICT_V2.md' : 'FAST_STRICT_V1.md')),
        ],
        'scope' => [
            'in' => ['Implement the example feature.'],
            'out' => ['Unrelated refactors.'],
        ],
        'requirements' => [[
            'id' => 'REQ-001',
            'type' => 'functional',
            'statement' => 'The public example behavior is available.',
            'acceptance' => [[
                'id' => 'AC-001',
                'statement' => 'The focused public test passes.',
            ]],
        ]],
        'tasks' => $tasks,
        'gates' => array_map(static function (array $gate) use ($protocolVersion): array {
            if ($protocolVersion === 'FAST_STRICT/2') {
                unset($gate['evidenceId']);
            }

            return $gate;
        }, [
            [
                'id' => 'GATE-PRECOMMIT',
                'stage' => '005',
                'command' => 'make pre-commit',
                'evidenceId' => 'EVID-GATE-PRECOMMIT',
            ],
            [
                'id' => 'GATE-QUALITY',
                'stage' => '005',
                'command' => 'make quality',
                'evidenceId' => 'EVID-GATE-QUALITY',
            ],
        ]),
        'reviewAxes' => [
            fastStrictReviewAxis('REV-SECURITY-PRIVACY-DATA', 'Security / Privacy / Data / PostgreSQL'),
            fastStrictReviewAxis('REV-ARCHITECTURE-API-PONYTAIL', 'Architecture / API / Ponytail'),
            fastStrictReviewAxis('REV-SPEC-TDD-UX-A11Y', 'Spec / TDD / UX / a11y'),
        ],
    ];
}

/**
 * @param  list<string>  $dependsOn
 * @param  list<string>  $ownedPaths
 * @return FastStrictFixtureTask
 */
function fastStrictTask(
    string $id,
    string $stage,
    string $title,
    string $agentRole,
    string $executionMode,
    array $dependsOn,
    array $ownedPaths,
    string $verificationMode,
    string $command,
): array {
    return [
        'id' => $id,
        'stage' => $stage,
        'title' => $title,
        'objective' => 'Produce a verifiable '.$title.' outcome.',
        'agentRole' => $agentRole,
        'executionMode' => $executionMode,
        'dependsOn' => $dependsOn,
        'ownedPaths' => $ownedPaths,
        'createPaths' => [],
        'deletePaths' => [],
        'verificationMode' => $verificationMode,
        'checks' => [[
            'id' => 'CHECK-'.substr($id, 5),
            'kind' => $verificationMode,
            'command' => $command,
            'expected' => $stage === '002' ? 'nonzero because behavior is absent' : 'exit 0',
        ]],
        'skills' => [[
            'id' => $executionMode === 'reviewer' ? 'code-review' : 'laravel-development',
            'applyAt' => ['analysis', 'verification'],
            'purpose' => 'Apply the task-specific quality method.',
            'expectedOutcome' => 'The task outcome follows the required method.',
        ], fastStrictPonytailSkill()],
        'requirementIds' => ['REQ-001'],
        'acceptanceIds' => ['AC-001'],
    ];
}

/** @return array{id: string, applyAt: list<string>, purpose: string, expectedOutcome: string} */
function fastStrictPonytailSkill(): array
{
    return [
        'id' => 'ponytail:ponytail',
        'applyAt' => ['analysis', 'verification'],
        'purpose' => 'Keep the solution minimal and sufficient.',
        'expectedOutcome' => 'No unnecessary layer, dependency or artifact is introduced.',
    ];
}

/** @return FastStrictFixtureReviewAxis */
function fastStrictReviewAxis(string $id, string $title): array
{
    return [
        'id' => $id,
        'title' => $title,
        'skills' => [[
            'id' => 'code-review',
            'applyAt' => ['review'],
            'purpose' => 'Review the frozen snapshot.',
            'expectedOutcome' => 'Findings are evidence-backed.',
        ], array_replace(fastStrictPonytailSkill(), ['applyAt' => ['review']]), [
            'id' => 'ponytail:ponytail-review',
            'applyAt' => ['review'],
            'purpose' => 'Review the snapshot for removable over-engineering.',
            'expectedOutcome' => 'No unnecessary layer or dependency remains.',
        ]],
    ];
}

/**
 * @param  FastStrictFixturePlan  $plan
 * @return FastStrictFixtureStatus
 */
function fastStrictStatus(string $root, array $plan, bool $complete): array
{
    $directory = $root.'/plan/example-fast-tdd';
    $protocolVersion = $plan['protocolVersion'];
    $v2 = $protocolVersion === 'FAST_STRICT/2';
    $manifestSha256 = $complete
        ? fastStrictSha256($directory.'/evidence/final.sha256')
        : null;
    $evidence = [];
    $taskEvidence = array_fill_keys(array_column($plan['tasks'], 'id'), []);

    if ($complete) {
        $definitions = [
            ['id' => 'EVID-ANALYSIS-001', 'stage' => '000', 'taskIndex' => 0, 'kind' => 'analysis', 'exitCode' => 0, 'path' => 'plan/example-fast-tdd/evidence/EVID-ANALYSIS-001.md'],
            ['id' => 'EVID-ANALYSIS-002', 'stage' => '000', 'taskIndex' => 1, 'kind' => 'analysis', 'exitCode' => 0, 'path' => 'plan/example-fast-tdd/evidence/EVID-ANALYSIS-002.md'],
            ['id' => 'EVID-ANALYSIS-003', 'stage' => '000', 'taskIndex' => 2, 'kind' => 'analysis', 'exitCode' => 0, 'path' => 'plan/example-fast-tdd/evidence/EVID-ANALYSIS-003.md'],
            ['id' => 'EVID-BASELINE', 'stage' => '001', 'taskIndex' => 3, 'kind' => 'baseline', 'exitCode' => 0, 'path' => 'plan/example-fast-tdd/evidence/baseline.sha256'],
            ['id' => 'EVID-RED-005', 'stage' => '002', 'taskIndex' => 4, 'kind' => 'red', 'exitCode' => 1, 'path' => 'plan/example-fast-tdd/evidence/EVID-RED-005.md'],
            ['id' => 'EVID-GREEN-005', 'stage' => '003', 'taskIndex' => 4, 'kind' => 'green', 'exitCode' => 0, 'path' => 'plan/example-fast-tdd/evidence/EVID-GREEN-005.md'],
            ['id' => 'EVID-QA-005', 'stage' => '004', 'taskIndex' => 4, 'kind' => 'qa', 'exitCode' => 0, 'path' => 'plan/example-fast-tdd/evidence/EVID-QA-005.md'],
            ['id' => 'EVID-GATE-PRECOMMIT', 'stage' => '005', 'taskIndex' => null, 'kind' => 'gate', 'exitCode' => 0, 'path' => 'plan/example-fast-tdd/evidence/EVID-GATE-PRECOMMIT.md'],
            ['id' => 'EVID-GATE-QUALITY', 'stage' => '005', 'taskIndex' => 5, 'kind' => 'gate', 'exitCode' => 0, 'path' => 'plan/example-fast-tdd/evidence/EVID-GATE-QUALITY.md'],
        ];

        foreach ($definitions as $definition) {
            $id = $definition['id'];
            $stage = $definition['stage'];
            $taskIndex = $definition['taskIndex'];
            $kind = $definition['kind'];
            $exitCode = $definition['exitCode'];
            $path = $definition['path'];
            $task = $taskIndex === null ? null : $plan['tasks'][$taskIndex];
            $command = match ($id) {
                'EVID-BASELINE' => $plan['tasks'][3]['checks'][0]['command'],
                'EVID-GATE-PRECOMMIT' => $plan['gates'][0]['command'],
                'EVID-GATE-QUALITY' => $plan['gates'][1]['command'],
                default => $task === null
                    ? throw new LogicException('Evidence task is required for '.$id)
                    : $task['checks'][0]['command'],
            };
            $gateId = match ($id) {
                'EVID-GATE-PRECOMMIT' => 'GATE-PRECOMMIT',
                'EVID-GATE-QUALITY' => 'GATE-QUALITY',
                default => null,
            };
            if (! str_ends_with($path, '.sha256')) {
                fastStrictWrite(
                    $root.'/'.$path,
                    "# {$id}\n\nStage: {$stage}\nTask: ".($task['id'] ?? 'none')."\nCheck: ".($task['checks'][0]['id'] ?? 'none')."\nCommand: {$command}\n".
                    ($v2 ? 'Gate: '.($gateId ?? 'none')."\nVerified manifest SHA-256: ".($gateId === null ? 'none' : $manifestSha256)."\n" : '').
                    "Expected and observed are redacted.\nExit code: {$exitCode}\nRedacted: yes\nLimitations: none.\n",
                );
            }
            $record = [
                'id' => $id,
                'stage' => $stage,
                'taskId' => $task['id'] ?? null,
                'checkId' => $task['checks'][0]['id'] ?? null,
                'kind' => $kind,
                'path' => $path,
                'sha256' => fastStrictSha256($root.'/'.$path),
                'command' => $command,
                'exitCode' => $exitCode,
                'observedAt' => '2026-08-20T00:10:00Z',
            ];
            if ($v2) {
                $record['gateId'] = $gateId ?? null;
                $record['verifiedManifestSha256'] = $gateId === null ? null : $manifestSha256;
            }
            $evidence[] = $record;
            if ($task !== null) {
                $taskEvidence[$task['id']][] = $id;
            }
        }

        foreach ($plan['tasks'] as $index => $task) {
            $id = 'EVID-HANDOFF-'.substr((string) $task['id'], 5);
            $path = 'plan/example-fast-tdd/evidence/'.$id.'.md';
            fastStrictWrite(
                $root.'/'.$path,
                "# {$id}\n\nStage: 007\nTask: {$task['id']}\nCheck: {$task['checks'][0]['id']}\nCommand: {$task['checks'][0]['command']}\n".
                ($v2 ? "Gate: none\nVerified manifest SHA-256: none\n" : '').
                "Expected and observed are redacted.\nExit code: 0\nRedacted: yes\nLimitations: none.\n",
            );
            $record = [
                'id' => $id,
                'stage' => '007',
                'taskId' => $task['id'],
                'checkId' => $task['checks'][0]['id'],
                'kind' => 'handoff',
                'path' => $path,
                'sha256' => fastStrictSha256($root.'/'.$path),
                'command' => $task['checks'][0]['command'],
                'exitCode' => 0,
                'observedAt' => '2026-08-20T00:30:00Z',
            ];
            if ($v2) {
                $record['gateId'] = null;
                $record['verifiedManifestSha256'] = null;
            }
            $evidence[] = $record;
            $taskEvidence[$task['id']][] = $id;
        }
        if (! $v2) {
            usort($evidence, static fn (array $left, array $right): int => $left['id'] <=> $right['id']);
        }
    }

    $taskStates = [];
    foreach ($plan['tasks'] as $index => $task) {
        $ids = $taskEvidence[$task['id']];
        sort($ids, SORT_STRING);
        $taskStates[] = [
            'id' => $task['id'],
            'state' => $complete ? 'complete' : 'pending',
            'executorId' => $complete ? 'agent-'.substr((string) $task['id'], 5) : null,
            'usedSkills' => $complete ? array_column($task['skills'], 'id') : [],
            'unavailableSkills' => [],
            'skippedSkills' => [],
            'evidenceIds' => $ids,
            'handoffEvidenceId' => $complete ? 'EVID-HANDOFF-'.substr((string) $task['id'], 5) : null,
        ];
    }

    $manifestPath = 'plan/example-fast-tdd/evidence/final.sha256';
    $bundleSha256 = $complete ? fastStrictBundleSha256($root, $evidence) : null;

    $reviews = [];
    foreach ($plan['reviewAxes'] as $index => $axis) {
        $suffixes = ['security-privacy-data', 'architecture-api-ponytail', 'spec-tdd-ux-a11y'];
        $reviewPath = $complete ? 'plan/example-fast-tdd/reviews/round-1-'.$suffixes[$index].'.md' : null;
        if ($reviewPath !== null) {
            fastStrictWrite($root.'/'.$reviewPath, "# {$axis['title']}\n\nRound: 1\nSnapshot: {$bundleSha256}\nB0: 0\nB1: 0\nB2: 0\n");
        }
        $reviews[] = [
            'axisId' => $axis['id'],
            'reviewerId' => $complete ? 'reviewer-'.($index + 1) : null,
            'verdict' => $complete ? 'go' : 'pending',
            'round' => $complete ? 1 : 0,
            'snapshotSha256' => $bundleSha256,
            'evidencePath' => $reviewPath,
            'evidenceSha256' => $reviewPath === null ? null : fastStrictSha256($root.'/'.$reviewPath),
            'usedSkills' => $complete ? array_column($axis['skills'], 'id') : [],
            'unavailableSkills' => [],
            'skippedSkills' => [],
            'b0' => 0,
            'b1' => 0,
            'b2' => 0,
        ];
    }

    return [
        'protocolVersion' => $protocolVersion,
        'planId' => $plan['planId'],
        'planRevision' => $plan['revision'],
        'planSha256' => fastStrictSha256($directory.'/PLAN.json'),
        'orchestratorId' => 'orchestrator-main',
        'overallState' => $complete ? 'complete' : 'draft',
        'currentStage' => $complete ? '007' : '000',
        'supersededBy' => null,
        'updatedAt' => '2026-08-20T00:20:00Z',
        'stages' => array_map(
            static fn (int $stage): array => [
                'id' => str_pad((string) $stage, 3, '0', STR_PAD_LEFT),
                'state' => $complete ? 'complete' : ($stage === 0 ? 'in_progress' : 'pending'),
            ],
            range(0, 7),
        ),
        'tasks' => $taskStates,
        'activeWriters' => [],
        'evidence' => $evidence,
        'gateResults' => [
            [
                'id' => 'GATE-PRECOMMIT',
                'state' => $complete ? 'passed' : 'pending',
                'evidenceId' => $complete ? 'EVID-GATE-PRECOMMIT' : null,
            ],
            [
                'id' => 'GATE-QUALITY',
                'state' => $complete ? 'passed' : 'pending',
                'evidenceId' => $complete ? 'EVID-GATE-QUALITY' : null,
            ],
        ],
        'snapshot' => [
            'state' => $complete ? 'frozen' : 'pending',
            'round' => $complete ? 1 : 0,
            'manifestPath' => $complete ? $manifestPath : null,
            'manifestSha256' => $complete ? $manifestSha256 : null,
            'bundleSha256' => $complete ? $bundleSha256 : null,
        ],
        'reviews' => $reviews,
        'blockers' => [],
    ];
}

function fastStrictRewindToStageTwo(string $root, bool $activeWriter): void
{
    $directory = $root.'/plan/example-fast-tdd';
    $keepEvidence = ['EVID-ANALYSIS-001', 'EVID-ANALYSIS-002', 'EVID-ANALYSIS-003', 'EVID-BASELINE'];
    fastStrictMutateStatus($root, static function (array &$status) use ($activeWriter, $keepEvidence): void {
        $status['overallState'] = 'active';
        $status['currentStage'] = '002';
        $status['supersededBy'] = null;
        $status['updatedAt'] = '2026-08-20T00:21:00Z';
        foreach ($status['stages'] as $index => &$stage) {
            $stage['state'] = $index < 2 ? 'complete' : ($index === 2 ? 'in_progress' : 'pending');
        }
        unset($stage);
        $status['evidence'] = array_values(array_filter(
            $status['evidence'],
            static fn (array $item): bool => in_array($item['id'], $keepEvidence, true),
        ));
        foreach ($status['tasks'] as $index => &$task) {
            $task['handoffEvidenceId'] = null;
            $task['unavailableSkills'] = [];
            $task['skippedSkills'] = [];
            if ($index < 3) {
                $task['state'] = 'complete';
                $task['evidenceIds'] = ['EVID-ANALYSIS-00'.($index + 1)];
            } elseif ($index === 3) {
                $task['state'] = 'complete';
                $task['evidenceIds'] = ['EVID-BASELINE'];
            } else {
                $task['state'] = 'pending';
                $task['executorId'] = null;
                $task['usedSkills'] = [];
                $task['evidenceIds'] = [];
            }
        }
        unset($task);
        if ($activeWriter) {
            $status['tasks'][4]['state'] = 'in_progress';
            $status['tasks'][4]['executorId'] = 'writer-1';
            $status['activeWriters'] = [['writerId' => 'writer-1', 'taskIds' => ['TASK-005']]];
        } else {
            $status['activeWriters'] = [];
        }
        foreach ($status['gateResults'] as &$gate) {
            $gate['state'] = 'pending';
            $gate['evidenceId'] = null;
        }
        unset($gate);
        $status['snapshot'] = [
            'state' => 'pending',
            'round' => 0,
            'manifestPath' => null,
            'manifestSha256' => null,
            'bundleSha256' => null,
        ];
        foreach ($status['reviews'] as &$review) {
            $review['reviewerId'] = null;
            $review['verdict'] = 'pending';
            $review['round'] = 0;
            $review['snapshotSha256'] = null;
            $review['evidencePath'] = null;
            $review['evidenceSha256'] = null;
            $review['usedSkills'] = [];
            $review['unavailableSkills'] = [];
            $review['skippedSkills'] = [];
            $review['b0'] = 0;
            $review['b1'] = 0;
            $review['b2'] = 0;
        }
        unset($review);
        $status['blockers'] = [];
    });

    $allowed = ['.', '..', 'command-log.md', 'baseline.sha256', 'EVID-ANALYSIS-001.md', 'EVID-ANALYSIS-002.md', 'EVID-ANALYSIS-003.md'];
    foreach ((array) scandir($directory.'/evidence') as $entry) {
        if (! in_array($entry, $allowed, true)) {
            unlink($directory.'/evidence/'.$entry);
        }
    }
    if (is_dir($directory.'/reviews')) {
        fastStrictDeleteTree($directory.'/reviews');
    }
}

/** @param FastStrictFixtureStatus $status */
function fastStrictEvidenceIndex(array $status, string $id): int
{
    foreach ($status['evidence'] as $index => $evidence) {
        if ($evidence['id'] === $id) {
            return $index;
        }
    }

    throw new RuntimeException('Evidence not found: '.$id);
}

/** @param list<FastStrictFixtureEvidence> $evidence */
function fastStrictBundleSha256(string $root, array $evidence): string
{
    $directory = $root.'/plan/example-fast-tdd';
    $lines = [
        'PLAN '.fastStrictSha256($directory.'/PLAN.json'),
    ];

    $documents = [
        'ARCHITECTURE_CONFORMANCE.md',
        'DECISIONS.md',
        'README.md',
        'TASKS.md',
        'TRACEABILITY.md',
    ];
    if (is_file($directory.'/OWNERSHIP.md')) {
        $documents[] = 'OWNERSHIP.md';
        sort($documents, SORT_STRING);
    }
    foreach ($documents as $document) {
        $lines[] = 'DOC '.$document.' '.fastStrictSha256($directory.'/'.$document);
    }

    $lines[] = 'MANIFEST '.fastStrictSha256($directory.'/evidence/final.sha256');

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

function fastStrictAddV2GateAttempt(
    string $root,
    string $evidenceId,
    string $gateId,
    int $exitCode,
    ?string $beforeEvidenceId = null,
): void {
    $directory = $root.'/plan/example-fast-tdd';
    /** @var FastStrictFixtureStatus $status */
    $status = json_decode((string) file_get_contents($directory.'/STATUS.json'), true, 512, JSON_THROW_ON_ERROR);
    /** @var FastStrictFixturePlan $plan */
    $plan = json_decode((string) file_get_contents($directory.'/PLAN.json'), true, 512, JSON_THROW_ON_ERROR);
    $gate = array_find($plan['gates'], static fn (array $item): bool => $item['id'] === $gateId);
    if (! is_array($gate)) {
        throw new RuntimeException('Gate not found: '.$gateId);
    }
    $manifestSha256 = fastStrictSha256($directory.'/evidence/final.sha256');
    $path = 'plan/example-fast-tdd/evidence/'.$evidenceId.'.md';
    fastStrictWrite(
        $root.'/'.$path,
        "# {$evidenceId}\n\nStage: {$gate['stage']}\nTask: none\nCheck: none\nCommand: {$gate['command']}\nGate: {$gateId}\nVerified manifest SHA-256: {$manifestSha256}\nExpected and observed are redacted.\nExit code: {$exitCode}\nRedacted: yes\nLimitations: none.\n",
    );
    $attempt = [
        'id' => $evidenceId,
        'stage' => $gate['stage'],
        'taskId' => null,
        'checkId' => null,
        'kind' => 'gate',
        'path' => $path,
        'sha256' => fastStrictSha256($root.'/'.$path),
        'command' => $gate['command'],
        'exitCode' => $exitCode,
        'observedAt' => '2026-08-20T00:15:00Z',
        'gateId' => $gateId,
        'verifiedManifestSha256' => $manifestSha256,
    ];

    if ($beforeEvidenceId === null) {
        $status['evidence'][] = $attempt;
    } else {
        $index = fastStrictEvidenceIndex($status, $beforeEvidenceId);
        array_splice($status['evidence'], $index, 0, [$attempt]);
    }
    fastStrictWriteJson($directory.'/STATUS.json', $status);
}

function fastStrictRefreshV2Snapshot(string $root): void
{
    $directory = $root.'/plan/example-fast-tdd';
    /** @var FastStrictFixtureStatus $status */
    $status = json_decode((string) file_get_contents($directory.'/STATUS.json'), true, 512, JSON_THROW_ON_ERROR);
    $bundleSha256 = fastStrictBundleSha256($root, $status['evidence']);
    $status['snapshot']['bundleSha256'] = $bundleSha256;
    $titles = [
        'Security / Privacy / Data / PostgreSQL',
        'Architecture / API / Ponytail',
        'Spec / TDD / UX / a11y',
    ];
    foreach ($status['reviews'] as $index => &$review) {
        $review['snapshotSha256'] = $bundleSha256;
        assert(is_string($review['evidencePath']));
        $path = $root.'/'.$review['evidencePath'];
        fastStrictWrite($path, "# {$titles[$index]}\n\nRound: 1\nSnapshot: {$bundleSha256}\nB0: 0\nB1: 0\nB2: 0\n");
        $review['evidenceSha256'] = fastStrictSha256($path);
    }
    unset($review);
    fastStrictWriteJson($directory.'/STATUS.json', $status);
}

/**
 * @param  FastStrictFixturePlan  $plan
 * @return list<string>
 */
function fastStrictOwnedPaths(array $plan): array
{
    $paths = [];
    foreach ($plan['tasks'] as $task) {
        foreach (array_merge($task['ownedPaths'], $task['createPaths'], $task['deletePaths']) as $path) {
            $paths[$path] = true;
        }
    }

    $paths = array_keys($paths);
    sort($paths, SORT_STRING);

    return $paths;
}

/** @param list<string> $paths */
function fastStrictManifest(string $root, array $paths): string
{
    $lines = [];
    foreach ($paths as $path) {
        $lines[] = fastStrictSha256($root.'/'.$path).'  '.$path;
    }

    return implode("\n", $lines)."\n";
}

function fastStrictClonePlan(string $root, string $slug): void
{
    $source = $root.'/plan/example-fast-tdd';
    $target = $root.'/plan/'.$slug.'-fast-tdd';
    fastStrictCopyTree($source, $target);

    fastStrictMutateJson($target.'/PLAN.json', static function (array &$plan) use ($slug): void {
        $plan['planId'] = $slug.'-fast-tdd';
        $plan['slug'] = $slug;
    });
    fastStrictMutateJson($target.'/STATUS.json', static function (array &$status) use ($target, $slug): void {
        $status['planId'] = $slug.'-fast-tdd';
        $status['planSha256'] = fastStrictSha256($target.'/PLAN.json');
    });
}

function fastStrictRun(string $root, string $phase = 'current'): int
{
    return Artisan::call('plan:validate', [
        'plan' => 'plan/example-fast-tdd',
        '--root' => $root,
        '--phase' => $phase,
        '--no-interaction' => true,
    ]);
}

/** @param Closure(FastStrictFixturePlan &$plan): void $mutate */
function fastStrictMutatePlan(string $root, Closure $mutate): void
{
    $path = $root.'/plan/example-fast-tdd/PLAN.json';
    fastStrictMutateJson($path, $mutate);
    fastStrictMutateJson($root.'/plan/example-fast-tdd/STATUS.json', static function (array &$status) use ($path): void {
        $status['planSha256'] = fastStrictSha256($path);
    });
}

/** @param Closure(FastStrictFixtureStatus &$status): void $mutate */
function fastStrictMutateStatus(string $root, Closure $mutate): void
{
    fastStrictMutateJson($root.'/plan/example-fast-tdd/STATUS.json', $mutate);
}

/**
 * @template T of array<string, mixed>
 *
 * @param  Closure(T &$value): void  $mutate
 */
function fastStrictMutateJson(string $path, Closure $mutate): void
{
    $value = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    if (! is_array($value) || array_is_list($value)) {
        throw new RuntimeException('Expected a JSON object fixture: '.$path);
    }

    /** @var T $value */
    $mutate($value);

    if (! is_array($value) || array_is_list($value)) {
        throw new RuntimeException('Mutation must preserve a JSON object fixture: '.$path);
    }

    /** @var array<string, mixed> $value */
    fastStrictWriteJson($path, $value);
}

/** @param array<string, mixed> $value */
function fastStrictWriteJson(string $path, array $value): void
{
    fastStrictWrite($path, json_encode(
        $value,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
    )."\n");
}

function fastStrictWrite(string $path, string $contents): void
{
    $directory = dirname($path);
    if (! is_dir($directory)) {
        mkdir($directory, 0o700, true);
    }

    file_put_contents($path, $contents);
}

function fastStrictCopyTree(string $source, string $target): void
{
    mkdir($target, 0o700, true);
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );

    foreach ($iterator as $item) {
        assert($item instanceof SplFileInfo);
        $destination = $target.'/'.substr((string) $item->getPathname(), strlen($source) + 1);
        if ($item->isDir()) {
            mkdir($destination, 0o700, true);
        } else {
            copy($item->getPathname(), $destination);
        }
    }
}

function fastStrictTreeDigest(string $root): string
{
    $entries = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
    );

    foreach ($iterator as $item) {
        assert($item instanceof SplFileInfo);
        $relative = substr((string) $item->getPathname(), strlen($root) + 1);
        $entries[] = $relative."\0".($item->isDir() ? 'directory' : fastStrictSha256($item->getPathname()));
    }

    sort($entries, SORT_STRING);

    return hash('sha256', implode("\n", $entries));
}

function fastStrictDeleteTree(string $root): void
{
    if (! is_dir($root)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $item) {
        assert($item instanceof SplFileInfo);
        if ($item->isLink() || $item->isFile()) {
            unlink($item->getPathname());
        } else {
            rmdir($item->getPathname());
        }
    }

    rmdir($root);
}

function fastStrictSha256(string $path): string
{
    $hash = hash_file('sha256', $path);
    if ($hash === false) {
        throw new RuntimeException('Cannot hash fixture file: '.$path);
    }

    return $hash;
}
