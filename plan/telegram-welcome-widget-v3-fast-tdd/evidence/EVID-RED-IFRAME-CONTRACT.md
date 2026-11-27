# EVID-RED-IFRAME-CONTRACT

Stage: 002
Task: TASK-007
Check: CHECK-007
Command: make test-suite suite=DocumentationAccessTest
Expected: Проверка определяет настоящий контракт и безопасное значение cookie рабочего окружения.
Observed: 2 сценария завершились ошибкой, 7 прошли (89 проверок). Публичный контракт не содержал miniAppLaunch, HTTPS-сессия выдавала SameSite=Lax вместо None.
Exit code: 2
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
