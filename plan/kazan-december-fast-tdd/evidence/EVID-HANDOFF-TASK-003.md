# EVID-HANDOFF-TASK-003

Stage: 007
Task: TASK-003
Check: CHECK-003
Command: Проверить уникальность календаря и сохранность профилей в PostgreSQL.
Expected: Миграция создаёт 31 записку однократно и атомарно только в production.
Observed: При передаче сверены ранее полученные результаты этой проверки. Миграция создаёт 31 записку однократно и атомарно только в production.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
