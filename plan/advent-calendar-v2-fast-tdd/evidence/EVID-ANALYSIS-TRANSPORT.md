# EVID-ANALYSIS-TRANSPORT

Stage: 000
Task: TASK-003
Check: CHECK-003
Command: Проверить риск изменения повторов Telegram, выдачи чужого сообщения и байтовых лимитов.
Expected: Сохраняется привязка, дедупликация и допустимый размер.
Observed: telegram_impl сопоставил перенос с рисками отправки, throttle, длины ответа и подмены message/date. Нужны регрессии чужого получателя, 429 и утреннего повторного открытия. Применён ponytail.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
