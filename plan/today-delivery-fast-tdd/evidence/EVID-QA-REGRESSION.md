# EVID-QA-REGRESSION

Stage: 004
Task: none
Check: none
Command: make test-suite suite='CalendarFlowTest|TelegramWidgetTest|TelegramReservedCalendarTest|TelegramStartReplayTest|TelegramWelcomeWidgetTest|StudioTest'
Expected: Проверка согласованного поведения и целостности пакета.
Observed: 56 тестов, 398 проверок прошли. Сохранены 429, unknown, восстановление очереди, доступ и приветственный виджет; старые тесты различают утреннюю и основную карточки.
Exit code: 0
Redacted: Сохранён итог без персональных данных, токенов и исходных журналов.
Limitations: Изолированная контейнерная среда; настоящий Telegram не вызывается.
Gate: none
Verified manifest SHA-256: none
