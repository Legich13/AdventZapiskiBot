# EVID-HANDOFF-TASK-005

Stage: 007
Task: TASK-005
Check: CHECK-005
Command: make test-suite suite=Calendar
Expected: RED указывает на отсутствие Action / DTO / Factory. GREEN сохраняет публикацию, привязку и открытие.
Observed: Переданы сохранение черновика, конкурирующая публикация, вручение, открытие и коды HTTP. Поля и таблицы отражены в документации Calendar.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
