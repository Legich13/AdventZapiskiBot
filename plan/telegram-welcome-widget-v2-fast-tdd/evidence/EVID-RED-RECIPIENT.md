# EVID-RED-RECIPIENT

Stage: 002
Task: TASK-005
Check: CHECK-005
Command: make test-suite suite=CalendarRecipient
Expected: Наличие личной главной, безопасного перехода и открытия одного дня переводит целевой тест из RED в GREEN.
Observed: 4 целевых теста не прошли (6 проверок): главная и защищённая загрузка отсутствовали; чтение и открытие календаря возвращали 404.
Exit code: 2
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
