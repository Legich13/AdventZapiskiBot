# EVID-GREEN-WELCOME-001

Stage: 003
Task: TASK-004
Check: CHECK-004
Command: make dev-test-filter filter=TelegramWelcomeWidgetTest
Expected: RED: вместо ожидаемого sendWidget отправляется sendMessage; GREEN: новое сообщение, ссылка и лимиты проходят.
Observed: Той же командой проверены два набора данных: 2 passed, 32 assertions, Duration 0.56s. Публичный транспорт отправляет один sendWidget и ноль sendMessage, пустую caption и allow_queries=false; DOM href сохраняет ссылку по умолчанию и экранированную настроенную ссылку. Заголовок, описание и CTA совпадают; scripts отсутствуют; raw/zlib бюджеты соблюдены.
Exit code: 0
Redacted: Итог команды без сырого вывода, токенов, cookies и частных данных.
Limitations: Локальная контейнерная проверка; новый виджет в живом Telegram не проверялся.
Gate: none
Verified manifest SHA-256: none
