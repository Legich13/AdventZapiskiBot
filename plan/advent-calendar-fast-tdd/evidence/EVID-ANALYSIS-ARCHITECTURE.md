# EVID-ANALYSIS-ARCHITECTURE

Stage: 000
Task: TASK-002
Check: CHECK-002
Command: Сопоставить архитектуру приложения, контракт Jobgram и платформенные границы Telegram.
Expected: Выделены общая операция открытия, изолированный стенд и независимое состояние отправки.
Observed: Проверены исходная пустая папка, обязательные файлы FAST_STRICT/2, CLI и регрессионные тесты Jobgram. PostgreSQL хранит данные и очередь. Подписанная Mini App, секрет webhook и локальная сессия встречаются в общих операциях календаря.
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
