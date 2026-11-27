# EVID-QA-IFRAME-CHROME

Stage: 004
Task: none
Check: none
Command: node /tmp/calendar-iframe.LsjzhI/check.cjs --proof
Expected: Зафиксирован результат проверки.
Observed: Chrome 158.0.8127.37 с ограниченными сторонними cookie, два локальных HTTPS-сайта, официальный Telegram SDK, Laravel staging и PostgreSQL. Холодная ссылка дня показала общую оболочку, подписанный тестовый вход вернул 204, календарь с launch proof и partitioned cookie — 200; переходы через главную успешно повторились. При входе другого участника во второй вкладке запрос с прежней подписью получил 401. Личный Telegram-аккаунт не использовался.
Exit code: 0
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
