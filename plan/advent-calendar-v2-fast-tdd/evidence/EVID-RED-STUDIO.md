# EVID-RED-STUDIO

Stage: 002
Task: TASK-007
Check: CHECK-007
Command: make test-suite suite='StudioBoundaryTest|DocumentationAccessTest'
Expected: RED обнаруживает доступ к БД из панели и отсутствующий справочник. GREEN подтверждает границы и защиту справки.
Observed: Страница /admin/docs возвращает 404 для запроса, ожидавшего перенаправление. В Overview обнаружен прямой запрос в PostgreSQL.
Exit code: 2
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
