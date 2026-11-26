# EVID-HANDOFF-TASK-006

Stage: 007
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite=Telegram
Expected: RED выявляет прежний вход webhook и отсутствие новой границы. GREEN подтверждает доставку и права.
Observed: Переданы нативный webhook и очередь напоминаний, обработка частоты, старых сообщений и неизвестного ответа Telegram. Контрольная отправка на устройство ожидает появления доступа к настоящему боту.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
