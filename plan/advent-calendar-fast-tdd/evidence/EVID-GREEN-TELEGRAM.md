# EVID-GREEN-TELEGRAM

Stage: 003
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite=Telegram
Expected: Сценарии выявляют отсутствующие виджеты/доставку, затем подтверждают требования по срокам, доступу и квотам.
Observed: На PHP 8.6 и PostgreSQL 19 прошли 15 тестов, 80 утверждений. Проверены нативный SDK, явный retry_after, неизвестная доставка, старое сообщение, размер и приватность HTML.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
