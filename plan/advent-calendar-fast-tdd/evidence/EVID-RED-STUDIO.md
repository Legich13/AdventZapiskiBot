# EVID-RED-STUDIO

Stage: 002
Task: TASK-007
Check: CHECK-007
Command: make test-suite suite=StudioTest
Expected: Сначала локальный маршрут и защита отсутствуют. Готовая студия использует реальные интерфейсы, тестовый вход отсутствует в production.
Observed: Код 2. Команда advent:demo отсутствует; страница /admin отвечает 404.
Exit code: 2
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Первый запуск выполнен через временный COMPOSE_FILE с локально доступным образом Jobgram и его vendor, базой PostgreSQL 19. Версии зависимостей итоговой сборки проверяются отдельно.
Gate: none
Verified manifest SHA-256: none
