# EVID-GREEN-4-10

Stage: 003
Task: TASK-004
Check: CHECK-004
Command: make test-suite suite=KazanDataMigration
Expected: Миграция создаёт 31 записку однократно и атомарно только в production.
Observed: Семь сценариев установки и повтора успешно прошли, 156 утверждений.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
