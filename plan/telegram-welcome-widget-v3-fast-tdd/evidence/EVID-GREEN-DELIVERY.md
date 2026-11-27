# EVID-GREEN-DELIVERY

Stage: 003
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite='TelegramStartReplayTest|TelegramTodayDeliveryTest|TelegramWelcomeWidgetTest'
Expected: Проверка обнаруживает пропущенные вручения, преждевременные отправки и исчерпанные резервы, затем проходит без подмены транспорта в бизнес-логике.
Observed: После исправлений 26 тестов прошли (235 проверок), 2,79 с. Проверены состояние старого /start, подготовка фото, rollback, commit и порядок утренней отправки.
Exit code: 0
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
