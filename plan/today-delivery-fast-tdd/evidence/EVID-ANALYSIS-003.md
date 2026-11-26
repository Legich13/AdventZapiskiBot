# EVID-ANALYSIS-003

Stage: 000
Task: TASK-003
Check: CHECK-003
Command: Проверить поведенческие тесты, реальную очередь, rollback, гонки и виртуальные часы.
Expected: Независимый анализ подтверждает согласованное поведение и указывает проверяемые риски.
Observed: Нужны реальные PostgreSQL queue/worker, HTTP publish и webhook; отдельно rollback, виртуальное время и пересечение scheduler. Старые sole() различают основную и утреннюю карточки.
Exit code: 0
Redacted: Сохранён итог без персональных данных, токенов и исходных журналов.
Limitations: Изолированная контейнерная среда; настоящий Telegram не вызывается.
Gate: none
Verified manifest SHA-256: none
