# EVID-RED-SELF

Stage: 002
Task: TASK-004
Check: CHECK-004
Command: make test-suite suite=TelegramTodayDeliveryTest
Expected: RED: отсутствует сегодняшнее задание после публикации/вручения; GREEN: реальные задания и воркер выполняют все согласованные сценарии.
Observed: Два HTTP-сценария (10:00 и 14:00) упали: jobs=0 вместо 1 после успешной публикации.
Exit code: 2
Redacted: Сохранён итог без персональных данных, токенов и исходных журналов.
Limitations: Изолированная контейнерная среда; настоящий Telegram не вызывается.
Gate: none
Verified manifest SHA-256: none
