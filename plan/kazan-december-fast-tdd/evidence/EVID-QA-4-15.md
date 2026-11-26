# EVID-QA-4-15

Stage: 004
Task: TASK-004
Check: CHECK-004
Command: make test-suite suite=KazanDataMigration
Expected: Миграция создаёт 31 записку однократно и атомарно только в production.
Observed: 9 сценариев, 170 утверждений. Дополнительная выборка (make test-suite 'suite=Calendar|KazanDataMigration'). Пройдены все 25 сценариев, 290 утверждений. Проверены коллизии с подтверждённым владельцем прежнего ника, публикация, повтор и открытие.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
