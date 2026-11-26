# EVID-GREEN-START

Stage: 003
Task: TASK-004
Check: CHECK-004
Command: make test-suite suite=TelegramTodayDeliveryTest
Expected: RED: отсутствует сегодняшнее задание после публикации/вручения; GREEN: реальные задания и воркер выполняют все согласованные сценарии.
Observed: Четыре теста, 42 проверки: поздний /start по нику и ID ставит одно задание; повтор update и новая команда не дублируют доставку.
Exit code: 0
Redacted: Сохранён итог без персональных данных, токенов и исходных журналов.
Limitations: Изолированная контейнерная среда; настоящий Telegram не вызывается.
Gate: none
Verified manifest SHA-256: none
