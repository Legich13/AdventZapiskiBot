# EVID-RED-TELEGRAM-LABEL

Stage: 002
Task: TASK-006
Check: CHECK-006
Command: make test-suite suite='TelegramWelcomeWidgetTest|StudioTest.*обычные сообщения'
Expected: Реальные команды библиотеки для текста и фото, кнопки и сообщения мастерской удовлетворяют новому контракту.
Observed: 2 сценария не прошли, 3 прошли (82 проверки). Кнопка утреннего напоминания называлась «Открыть подарок» вместо принятого «К подарку».
Exit code: 2
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
