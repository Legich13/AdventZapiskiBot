# EVID-RED-WELCOME-001

Stage: 002
Task: TASK-004
Check: CHECK-004
Command: make dev-test-filter filter=TelegramWelcomeWidgetTest
Expected: RED: вместо ожидаемого sendWidget отправляется sendMessage; GREEN: новое сообщение, ссылка и лимиты проходят.
Observed: Целевой тест выполнен до изменений production-кода. Два набора данных (ссылка по умолчанию и настроенная ссылка) завершились ожидаемым падением: Nutgram фактически отправил sendMessage вместо ожидаемого sendWidget; SDK assertion сообщает Method name not found. 2 failed, 2 assertions, Duration 0.72s. Ошибок синтаксиса или инфраструктуры нет.
Exit code: 2
Redacted: Итог команды без сырого вывода, токенов, cookies и частных данных.
Limitations: Локальная контейнерная проверка; новый виджет в живом Telegram не проверялся.
Gate: none
Verified manifest SHA-256: none
