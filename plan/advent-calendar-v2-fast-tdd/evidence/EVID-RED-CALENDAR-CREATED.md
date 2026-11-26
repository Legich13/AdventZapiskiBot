# EVID-RED-CALENDAR-CREATED

Stage: 002
Task: TASK-005
Check: CHECK-005
Command: make test-suite suite=Calendar
Expected: RED указывает на отсутствие Action / DTO / Factory. GREEN сохраняет публикацию, привязку и открытие.
Observed: При независимой сверке ответа POST обнаружено отсутствие 201 в экспорте созданного черновика. Команда для Calendar упала: 1 не прошёл, 11 прошли; 69 утверждений.
Exit code: 2
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
