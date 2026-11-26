DC = docker compose
RUN = $(DC) run --rm --no-deps -T app
.PHONY: setup up down test test-suite quality pre-commit dev-demo
setup:
	@test -f backend/.env || cp backend/.env.example backend/.env
	$(DC) build app
	$(DC) up -d --wait db
	$(RUN) composer install --no-interaction --prefer-dist
	@grep -Eq '^APP_KEY=.+$$' backend/.env || $(RUN) php artisan key:generate
	$(RUN) php artisan migrate --force
	$(DC) up -d app worker scheduler
up:
	$(DC) up -d
down:
	$(DC) down
dev-demo:
	$(RUN) php artisan advent:demo
test:
	$(RUN) vendor/bin/pest --colors=never
test-suite:
	@case "$(suite)" in Calendar*) db=advent_test_calendar ;; Telegram*) db=advent_test_telegram ;; Studio*) db=advent_test_studio ;; *) db=advent_test ;; esac; \
	$(DC) run --rm --no-deps -T -e DB_DATABASE=$$db app vendor/bin/pest --colors=never --filter="$(suite)"
quality:
	$(RUN) composer validate --strict
	$(RUN) vendor/bin/pint --test
	$(RUN) vendor/bin/rector process --dry-run --no-progress-bar
	$(RUN) vendor/bin/phpstan analyse --no-progress --memory-limit=1G
	$(MAKE) dev-api-check
	$(MAKE) test
pre-commit:
	git diff --check
	$(RUN) composer audit
	$(RUN) php artisan route:list --except-vendor
	$(MAKE) dev-plan-check-all
-include plan.mk

.PHONY: dev-lint dev-stan dev-test-filter dev-docs-check dev-api-generate dev-api-check
.PHONY: boost-mcp
boost-mcp:
	@$(DC) exec -T app php artisan boost:mcp

dev-lint:
	$(RUN) vendor/bin/pint --test
	$(RUN) vendor/bin/rector process --dry-run --no-progress-bar

dev-stan:
	$(RUN) vendor/bin/phpstan analyse --no-progress --memory-limit=1G $(paths)

dev-test-filter:
	$(MAKE) test-suite suite="$(filter)"

dev-docs-check:
	$(MAKE) test-suite suite='Documentation|AdventArchitectureTest'
	$(MAKE) dev-api-check

dev-api-generate:
	@mkdir -p docs/contracts
	$(RUN) php artisan scramble:export --path=/workspace/docs/contracts/openapi.json

dev-api-check:
	$(RUN) sh -ec 'spec=$$(mktemp); trap "rm -f $$spec" EXIT; php artisan scramble:export --path="$$spec"; cmp "$$spec" /workspace/docs/contracts/openapi.json'
