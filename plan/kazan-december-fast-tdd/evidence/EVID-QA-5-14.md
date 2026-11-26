# EVID-QA-5-14

Stage: 004
Task: TASK-005
Check: CHECK-005
Command: make test-suite suite=TelegramReservedCalendarTest
Expected: Первый /start адресата вручает подарок по ID, открывает доступ и допускает утренние карточки.
Observed: 4 сценария, 44 утверждения. Дополнительная выборка (make test-suite suite=Telegram). 34 теста, 237 утверждений: штатный webhook, открытие, повторы, доставка.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
