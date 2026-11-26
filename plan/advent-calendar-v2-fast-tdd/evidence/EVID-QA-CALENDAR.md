# EVID-QA-CALENDAR

Stage: 004
Task: TASK-005
Check: CHECK-005
Command: make test-suite suite=Calendar
Expected: RED указывает на отсутствие Action / DTO / Factory. GREEN сохраняет публикацию, привязку и открытие.
Observed: На финальной границе домена make test-suite suite=Calendar: 12 тестов, 76 утверждений, код 0. Публикация календаря для себя, сохранение черновика при конфликте и чтение подарка из двух процессов проверены на PostgreSQL. Самостоятельный max Larastan предметного модуля прошёл.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
