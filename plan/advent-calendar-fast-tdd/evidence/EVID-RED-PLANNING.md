# EVID-RED-PLANNING

Stage: 002
Task: TASK-004
Check: CHECK-004
Command: make test-suite suite=ValidateFastPlanCommandTest
Expected: Без команды тесты не проходят. После переноса валидные планы проходят, повреждённые получают ожидаемые FPV-коды.
Observed: Код 2. Контрактная проверка получает CommandNotFoundException: plan:validate.
Exit code: 2
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Первый запуск выполнен через временный COMPOSE_FILE с локально доступным образом Jobgram и его vendor, базой PostgreSQL 19. Версии зависимостей итоговой сборки проверяются отдельно.
Gate: none
Verified manifest SHA-256: none
