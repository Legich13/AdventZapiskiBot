# EVID-GREEN-STUDIO

Stage: 003
Task: TASK-007
Check: CHECK-007
Command: make test-suite suite=StudioTest
Expected: Сначала локальный маршрут и защита отсутствуют. Готовая студия использует реальные интерфейсы, тестовый вход отсутствует в production.
Observed: На текущем Docker-образе прошло 6 тестов и 38 утверждений за 2,71 с. Проверены доступ к Filament, локальное демо и кешированные маршруты production.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
