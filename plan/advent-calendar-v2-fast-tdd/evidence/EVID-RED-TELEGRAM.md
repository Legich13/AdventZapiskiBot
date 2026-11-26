# EVID-RED-TELEGRAM

Stage: 002
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite=Telegram
Expected: RED выявляет прежний вход webhook и отсутствие новой границы. GREEN подтверждает доставку и права.
Observed: 2 сценария упали: прежняя обработка неверного webhook (500 вместо 422) и отсутствующий Action.
Exit code: 2
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
