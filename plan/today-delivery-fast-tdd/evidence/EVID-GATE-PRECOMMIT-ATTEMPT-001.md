# EVID-GATE-PRECOMMIT-ATTEMPT-001

Stage: 005
Task: none
Check: none
Command: make pre-commit
Expected: Проверка согласованного поведения и целостности пакета.
Observed: Diffcheck, Composer audit и маршруты прошли. Aggregate validator отклонил ранее существовавший незавершённый welcome-widget пакет: его final manifest включает общие файлы, изменённые сегодняшней доставкой.
Exit code: 2
Redacted: Сохранён итог без персональных данных, токенов и исходных журналов.
Limitations: Изолированная контейнерная среда; настоящий Telegram не вызывается.
Gate: GATE-PRECOMMIT
Verified manifest SHA-256: 454e5e84360433affc21e0948a44f03f3dc7c5e3ae7fd745964c918dc9917875
