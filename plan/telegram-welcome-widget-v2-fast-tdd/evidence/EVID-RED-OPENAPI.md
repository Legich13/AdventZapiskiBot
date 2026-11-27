# EVID-RED-OPENAPI

Stage: 002
Task: TASK-007
Check: CHECK-007
Command: make test-suite suite=DocumentationAccessTest
Expected: OpenAPI описывает доступ к выбранной записке, документация проходит контрактные проверки.
Observed: Документация: 1 падение, 6 успешных тестов (82 проверки), 1,84 с. В экспортированном контракте отсутствовала операция openReceivedGift.
Exit code: 2
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
