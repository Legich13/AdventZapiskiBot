# EVID-RED-TELEGRAM

Stage: 002
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite=Telegram
Expected: Сценарии выявляют отсутствующие виджеты/доставку, затем подтверждают требования по срокам, доступу и квотам.
Observed: Код 2. В PostgreSQL отсутствуют таблицы календаря; webhook отвечает 404 до регистрации обработчика.
Exit code: 2
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Первый запуск выполнен через временный COMPOSE_FILE с локально доступным образом Jobgram и его vendor, базой PostgreSQL 19. Версии зависимостей итоговой сборки проверяются отдельно.
Gate: none
Verified manifest SHA-256: none
