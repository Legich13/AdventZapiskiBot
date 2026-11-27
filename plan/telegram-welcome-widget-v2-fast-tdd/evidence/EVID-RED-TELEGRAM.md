# EVID-RED-TELEGRAM

Stage: 002
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite='TelegramWelcomeWidgetTest|StudioTest.*обычные сообщения'
Expected: Реальные команды библиотеки для текста и фото, кнопки и сообщения мастерской удовлетворяют новому контракту.
Observed: 5 тестов не прошли (26 проверок). Fake Nutgram зарегистрировал 0 вызовов sendMessage и sendPhoto вместо одного. Мастерская вывела приветствие до /start.
Exit code: 2
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
