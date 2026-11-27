# EVID-RED-DELIVERY

Stage: 002
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite='TelegramStartReplayTest|TelegramTodayDeliveryTest|TelegramWelcomeWidgetTest'
Expected: Проверка обнаруживает пропущенные вручения, преждевременные отправки и исчерпанные резервы, затем проходит без подмены транспорта в бизнес-логике.
Observed: 6 падений, 20 прошло, 216 проверок, 2,40 с. Частичная запись старого журнала не завершила вручение; rollback видит незарезервированную позицию; сеть вызывается до внешнего commit и при rollback; отказ 429 доступен планировщику раньше обложки, а локальная ошибка PNG не восстанавливает вручение.
Exit code: 2
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
