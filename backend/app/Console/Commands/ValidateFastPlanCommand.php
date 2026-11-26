<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Planning\FastPlanValidator;
use Illuminate\Console\Command;

final class ValidateFastPlanCommand extends Command
{
    /** @var string */
    #[\Override]
    protected $signature = 'plan:validate
        {plan? : Exact repository-relative plan/<feature-slug>-fast-tdd directory}
        {--all : Validate every canonical plan}
        {--protocol= : With --all, validate only FAST_STRICT/1 or FAST_STRICT/2 plans}
        {--phase=current : current or stage 000 through 007}
        {--root= : Absolute repository root}';

    /** @var string */
    #[\Override]
    protected $description = 'Validate FAST_STRICT plan integrity without changing files.';

    public function handle(FastPlanValidator $validator): int
    {
        $plan = $this->argument('plan');
        $root = $this->option('root');
        $phase = $this->option('phase');
        $protocol = $this->option('protocol');
        $result = $validator->validate(
            is_string($root) && $root !== '' ? $root : dirname(base_path()),
            is_string($plan) ? $plan : null,
            (bool) $this->option('all'),
            is_string($phase) ? $phase : '',
            is_string($protocol) && $protocol !== '' ? $protocol : null,
        );

        foreach ($result['errors'] as $error) {
            $location = $error['path'].'#'.$error['pointer'];
            $this->line(sprintf('ERROR %s %s: %s', $error['code'], $location, $error['message']));
        }

        $this->line(sprintf(
            'SUMMARY plans=%d errors=%d',
            $result['plans'],
            count($result['errors']),
        ));

        if ($result['errors'] === []) {
            return self::SUCCESS;
        }

        foreach ($result['errors'] as $error) {
            if (str_starts_with($error['code'], 'FPV-CLI-')) {
                return 2;
            }
        }

        return self::FAILURE;
    }
}
