# EVID-ANALYSIS-002

Stage: 000
Task: TASK-002
Check: CHECK-002
Command: Проверить транзакции, scoped dispatcher, очередь afterCommit и потребителей.
Expected: Независимый анализ подтверждает согласованное поведение и указывает проверяемые риски.
Observed: Общий dispatcher внутри транзакции резервирует доставку, а job afterCommit откладывает очередь. Обязателен recipient scope и claimed_at <= GiftClock now. Новые таблицы и классы не нужны.
Exit code: 0
Redacted: Сохранён итог без персональных данных, токенов и исходных журналов.
Limitations: Изолированная контейнерная среда; настоящий Telegram не вызывается.
Gate: none
Verified manifest SHA-256: none
