# EVID-QA-DELIVERY

Stage: 004
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite='TelegramStartReplayTest|TelegramTodayDeliveryTest|TelegramWelcomeWidgetTest'
Expected: Проверка обнаруживает пропущенные вручения, преждевременные отправки и исчерпанные резервы, затем проходит без подмены транспорта в бизнес-логике.
Observed: На той же команде после исправлений прошли 27 тестов (241 проверка), 2,56 с. Отдельно проверены планировщик из другого процесса, порядок очереди и неопределённый исход.
Exit code: 0
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
