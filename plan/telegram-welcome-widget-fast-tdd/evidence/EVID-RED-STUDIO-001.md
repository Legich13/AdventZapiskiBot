# EVID-RED-STUDIO-001

Stage: 002
Task: TASK-004
Check: CHECK-005
Command: make dev-test-filter filter=StudioTest
Expected: RED: мастерская не показывает приветственную карточку; GREEN: тот же шаблон и локальная CTA доступны.
Observed: Целевой тест выполнен до изменений Studio. Ожидаемо отсутствует «Приветствие бота» и приветственный iframe: StudioTest:74. Итог 1 failed, 8 passed, 50 assertions, Duration 1.58s; ошибок инфраструктуры или синтаксиса нет. Сырой HTML и временный CSRF не сохранялись.
Exit code: 2
Redacted: Итог команды без сырого вывода, токенов, cookies и частных данных.
Limitations: Локальная контейнерная проверка; новый виджет в живом Telegram не проверялся.
Gate: none
Verified manifest SHA-256: none
