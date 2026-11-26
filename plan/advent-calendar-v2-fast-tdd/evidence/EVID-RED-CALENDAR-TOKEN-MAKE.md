# EVID-RED-CALENDAR-TOKEN-MAKE

Stage: 002
Task: TASK-005
Check: CHECK-005
Command: make test-suite suite=Calendar
Expected: RED указывает на отсутствие Action / DTO / Factory. GREEN сохраняет публикацию, привязку и открытие.
Observed: Уточнение уровня команды в EVID-RED-CALENDAR-TOKEN: код подпроцесса Pest равен 1, оболочка Make вернула 2. Результат фактического make test-suite: 1 failed, 11 passed, 70 assertions; ожидался 401, получен 500.
Exit code: 2
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
