# EVID-GATE-REMEDIATION-R1-START

Stage: 005
Task: none
Check: none
Command: make test-suite suite='Calendar^|TelegramStartReplayTest'
Expected: RED 1 ошибка, 12 утверждений, прежний владелец получил чужую запись после смены ника. GREEN 1/22. Дополнительный конкурентный RED 1 не прошёл, 1 прошёл, 26 утверждений. После замены истекающей блокировки на сеансовую advisory lock PostgreSQL GREEN 2/30; тест проверяет конкуренцию и два 429 подряд. Соседние тесты 6/120, проверки Pint, Rector, Larastan прошли.
Observed: RED 1 ошибка, 12 утверждений, прежний владелец получил чужую запись после смены ника. GREEN 1/22. Дополнительный конкурентный RED 1 не прошёл, 1 прошёл, 26 утверждений. После замены истекающей блокировки на сеансовую advisory lock PostgreSQL GREEN 2/30; тест проверяет конкуренцию и два 429 подряд. Соседние тесты 6/120, проверки Pint, Rector, Larastan прошли.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
