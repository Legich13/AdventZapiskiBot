# EVID-GREEN-5-9

Stage: 003
Task: TASK-005
Check: CHECK-005
Command: make test-suite suite=TelegramReservedCalendarTest
Expected: Первый /start адресата вручает подарок по ID, открывает доступ и допускает утренние карточки.
Observed: Подтверждены вручение по изменённому и удалённому нику, отказ новому владельцу ника, время первого открытия и утренняя доставка. 3 теста, 42 утверждения.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
