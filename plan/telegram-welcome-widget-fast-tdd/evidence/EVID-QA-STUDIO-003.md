# EVID-QA-STUDIO-003

Stage: 004
Task: TASK-004
Check: CHECK-005
Command: make dev-test-filter filter=StudioTest
Expected: RED: мастерская не показывает приветственную карточку; GREEN: тот же шаблон и локальная CTA доступны.
Observed: Для focused QA переиспользован тот же фактический запуск EVID-GREEN-STUDIO-003: 9 passed, 57 assertions. Оркестратор подтвердил отсутствие других изменений и сохранность DOM-покрытия. Новый запуск только ради смены стадии не выполнялся.
Exit code: 0
Redacted: Итог команды без сырого вывода, токенов, cookies и частных данных.
Limitations: Успешное focused evidence переиспользовано по FAST_STRICT/2; основной quality ещё требуется на новом манифесте.
Gate: none
Verified manifest SHA-256: none
