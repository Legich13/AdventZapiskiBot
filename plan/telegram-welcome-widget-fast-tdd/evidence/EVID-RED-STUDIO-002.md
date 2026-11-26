# EVID-RED-STUDIO-002

Stage: 002
Task: TASK-004
Check: CHECK-005
Command: make dev-test-filter filter=StudioTest
Expected: RED: мастерская не показывает приветственную карточку; GREEN: тот же шаблон и локальная CTA доступны.
Observed: После целевой QA добавлено отдельное поведенческое утверждение для выбранного профиля без календарей: отсутствует ожидаемый текст «Полученных календарей пока нет.» (StudioTest:74). Итог 1 failed, 8 passed, 51 assertions, Duration 1.46s. Это диагностируемое отсутствие нужного empty-state; сырой HTML и временный CSRF не сохранялись.
Exit code: 2
Redacted: Итог команды без сырого вывода, токенов, cookies и частных данных.
Limitations: Локальная контейнерная проверка; новый виджет в живом Telegram не проверялся.
Gate: none
Verified manifest SHA-256: none
