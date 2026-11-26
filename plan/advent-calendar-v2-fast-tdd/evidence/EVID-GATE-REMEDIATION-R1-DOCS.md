# EVID-GATE-REMEDIATION-R1-DOCS

Stage: 005
Task: none
Check: none
Command: make test-suite suite='DocumentationAccessTest.*документация'
Expected: RED два теста – 404 по ссылкам и отсутствие условных полей в OpenAPI. Проверка webhook дополнительно выявила 204 на некорректную структуру вместо 422. Те же проверки GREEN 2/28. make dev-docs-check: 13/400, экспорт совпал. Выполнена повторная сквозная проверка в Chromium на изолированном PostgreSQL 18.
Observed: RED два теста – 404 по ссылкам и отсутствие условных полей в OpenAPI. Проверка webhook дополнительно выявила 204 на некорректную структуру вместо 422. Те же проверки GREEN 2/28. make dev-docs-check: 13/400, экспорт совпал. Выполнена повторная сквозная проверка в Chromium на изолированном PostgreSQL 18.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
