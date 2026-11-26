# EVID-RED-4-16

Stage: 002
Task: TASK-004
Check: CHECK-004
Command: make test-suite suite=KazanDataMigration
Expected: Миграция создаёт 31 записку однократно и атомарно только в production.
Observed: Обе проверки существующего владельца прежнего ника упали с ложным конфликтом календарей.
Exit code: 2
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
