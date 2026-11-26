# EVID-RED-CALENDAR

Stage: 002
Task: TASK-005
Check: CHECK-005
Command: make test-suite suite=Calendar
Expected: Сценарии сначала обнаруживают отсутствие доменной операции, затем подтверждают публикацию, доступ и повторное открытие.
Observed: Код 2. Авторские маршруты отсутствуют, контейнер не находит Calendars. Существуют 8 падающих сценариев.
Exit code: 2
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Первый запуск выполнен через временный COMPOSE_FILE с локально доступным образом Jobgram и его vendor, базой PostgreSQL 19. Версии зависимостей итоговой сборки проверяются отдельно.
Gate: none
Verified manifest SHA-256: none
