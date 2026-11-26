# EVID-GREEN-STUDIO-002

Stage: 003
Task: TASK-004
Check: CHECK-005
Command: make dev-test-filter filter=StudioTest
Expected: RED: мастерская не показывает приветственную карточку; GREEN: тот же шаблон и локальная CTA доступны.
Observed: После минимального исправления выбранного профиля без календарей той же командой получено 9 passed, 58 assertions, Duration 1.44s. Повторная загрузка браузера подтверждает новый empty-state; сырой HTML и CSRF не сохранялись.
Exit code: 0
Redacted: Итог команды без сырого вывода, токенов, cookies и частных данных.
Limitations: Локальная контейнерная проверка; новый виджет в живом Telegram не проверялся.
Gate: none
Verified manifest SHA-256: none
