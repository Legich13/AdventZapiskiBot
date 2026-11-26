# EVID-RED-CALENDAR-TOKEN

Stage: 002
Task: TASK-005
Check: CHECK-005
Command: make test-suite suite=Calendar
Expected: RED указывает на отсутствие Action / DTO / Factory. GREEN сохраняет публикацию, привязку и открытие.
Observed: Каноническая проверка входа при отсутствии токена ожидала 401, приложение возвращало 500. Исходный запуск Pest упал.
Exit code: 1
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
