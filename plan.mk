phase ?= current
plan ?= plan/advent-calendar-v2-fast-tdd

.PHONY: dev-plan-check dev-plan-check-all
dev-plan-check:
	$(RUN) php artisan plan:validate "$(plan)" --phase="$(phase)" --root=/workspace

dev-plan-check-all:
	$(RUN) php artisan plan:validate --all --protocol=FAST_STRICT/2 --phase=current --root=/workspace
