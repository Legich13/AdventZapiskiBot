# EVID-GREEN-STUDIO-001

Stage: 003
Task: TASK-004
Check: CHECK-005
Command: make dev-test-filter filter=StudioTest
Expected: RED: мастерская не показывает приветственную карточку; GREEN: тот же шаблон и локальная CTA доступны.
Observed: Той же командой получено 9 passed, 57 assertions, Duration 1.73s. Публичный /dev не показывает приветствие до выбора профиля; выбранный профиль получает экранированный srcdoc с согласованным заголовком, локальным href и target=_top. WidgetMessage не создаётся. Существующие /app, cache и access-сценарии прошли.
Exit code: 0
Redacted: Итог команды без сырого вывода, токенов, cookies и частных данных.
Limitations: Локальная контейнерная проверка; новый виджет в живом Telegram не проверялся.
Gate: none
Verified manifest SHA-256: none
