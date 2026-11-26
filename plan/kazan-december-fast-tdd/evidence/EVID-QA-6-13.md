# EVID-QA-6-13

Stage: 004
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite=StudioTest
Expected: Редактор фиксирует адресата до вручения и позволяет исправить будущую записку.
Observed: 8 сценариев, 46 утверждений. Дополнительная выборка (make test-suite 'suite=StudioTest|DocumentationAccessTest'). 15 тестов, 128 утверждений: защита поля, штатные сценарии редактора, доступ к справочнику.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
