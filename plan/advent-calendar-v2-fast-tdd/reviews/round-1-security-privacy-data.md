# Security / Privacy / Data / PostgreSQL

Reviewer: /root/review_security
Axis: REV-SECURITY-PRIVACY-DATA
Snapshot SHA-256: 3dc7ee3cc683a16af646c503a2ea6a657341fe0014192a47e091e49e7520d9ed
Manifest SHA-256: 6a88fb1a81919c2054ae2f1791f85f9bdbd51b5e2161327c44ec105fd25b06b0
Skills: ponytail:ponytail, ponytail:ponytail-review
Verdict: fix_required
Counts: B0=0, B1=3, B2=1

Проверены контрольные суммы 203 файлов, подпись и возраст initData, секрет webhook, владение, уникальные индексы и доступность /dev. Уведомление на устройстве остаётся отдельной внешней проверкой.

B1.1. После 429 повтор /start заново привязывает новые календари бывшему владельцу ника. Неизменяемый список первого вручения должен быть закреплён за update_id. Источник: backend/app/Telegram/Actions/HandleTelegramUpdateAction.php:72.

B1.2. DeliverMorningGiftAction.php:39 меняет статус на sending до SendWidgetAction.php:18. Ошибки подготовки получают unknown, подтверждённо неотправленный подарок теряется. Нужна подготовка карточки до атомарного перехода непосредственно перед HTTP, чтобы можно было повторить подтверждённо неотправленную доставку.

B1.3. В backend/app/Telegram/Services/MorningRateLimiter.php:35 короткий второй Retry-After перезаписывает длинную паузу. Требуется общий lock и выбор наибольшего срока.

B2. В backend/app/Telegram/Infrastructure/Persistence/PostgresMorningDeliveryRepository.php:24 восстановление каждые пять минут ставит в очередь дополнительные задания для всё ещё ожидающих отправок.
B2 rationale: Уникальная запись доставки защищает получателя, но в очереди при отставании накапливаются дублирующие задания. Решение принято в этом же цикле исправлений.
B2 owner: Владелец подсистемы Telegram и очереди.
