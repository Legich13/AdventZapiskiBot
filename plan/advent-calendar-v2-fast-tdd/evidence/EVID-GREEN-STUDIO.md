# EVID-GREEN-STUDIO

Stage: 003
Task: TASK-007
Check: CHECK-007
Command: make test-suite suite='StudioBoundaryTest|DocumentationAccessTest'
Expected: RED обнаруживает доступ к БД из панели и отсутствующий справочник. GREEN подтверждает границы и защиту справки.
Observed: Каноническая команда той же пары прошла: 2 теста, 27 утверждений. Дополнительно объединённый запуск StudioTest|StudioBoundaryTest|DocumentationAccessTest проверил 8 сценариев и 65 утверждений. Файл Markdown защищён сессией сотрудника, OpenAPI включает ожидаемые operationId.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
