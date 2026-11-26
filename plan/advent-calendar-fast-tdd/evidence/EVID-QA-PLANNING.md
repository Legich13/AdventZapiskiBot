# EVID-QA-PLANNING

Stage: 004
Task: TASK-004
Check: CHECK-004
Command: make test-suite suite=ValidateFastPlanCommandTest
Expected: Без команды тесты не проходят. После переноса валидные планы проходят, повреждённые получают ожидаемые FPV-коды.
Observed: После сборки PHP 8.6 с mbstring и установки зависимостей из composer.lock вновь выполнены 127 успешных тестов и 277 утверждений, 5 секунд. Код выхода 0, включая негативные фикстуры FPV.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
