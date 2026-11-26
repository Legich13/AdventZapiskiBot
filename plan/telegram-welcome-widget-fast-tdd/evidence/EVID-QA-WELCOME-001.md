# EVID-QA-WELCOME-001

Stage: 004
Task: TASK-004
Check: CHECK-004
Command: make dev-test-filter filter=TelegramWelcomeWidgetTest
Expected: RED: вместо ожидаемого sendWidget отправляется sendMessage; GREEN: новое сообщение, ссылка и лимиты проходят.
Observed: Для focused QA использован тот же фактический успешный запуск, что зафиксирован в EVID-GREEN-WELCOME-001. Оркестратор подтвердил отсутствие последующих изменений входов целевого теста. Дополнительного запуска только ради смены стадии не было. Проверены 2 набора данных и 32 утверждения исходящего виджета.
Exit code: 0
Redacted: Итог команды без сырого вывода, токенов, cookies и частных данных.
Limitations: Повторно используется существующее успешное focused evidence согласно FAST_STRICT/2; отдельная Telegram-регрессия и браузерная QA ещё выполняются. Живая Telegram-авторизация не проверялась.
Gate: none
Verified manifest SHA-256: none
