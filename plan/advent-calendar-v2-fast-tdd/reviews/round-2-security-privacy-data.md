# Security / Privacy / Data / PostgreSQL

Reviewer: /root/review_security
Axis: REV-SECURITY-PRIVACY-DATA
Snapshot SHA-256: e6f5d8635eaa1cd44c7e3ed1bbd291b87efb9922bf4566d50ce0691a56d10964
Manifest SHA-256: eed74774cb7fb38d8d9599641ae8180f74d09f42a3ade72119166117fd010573
Skills: ponytail:ponytail, ponytail:ponytail-review
Verdict: go
Counts: B0=0, B1=0, B2=0

Проверены контрольные суммы всех 211 файлов второго снимка. Четыре замечания первого раунда закрыты.

Журнал первоначального /start удерживает исходный набор выдаваемых календарей при 429 и повторном обновлении. Подготовка виджета происходит до перехода к HTTP-отправке. Известное подтверждение корректно фиксируется после проверки зависшей доставки. Общий лимит сохраняет более поздний срок двух Retry-After. Действующее задание PostgreSQL не дублируется при повторном восстановлении.

Проверенные участки: backend/app/Telegram/Actions/PrepareStartUpdateAction.php:24, backend/tests/Feature/TelegramStartReplayTest.php:37, backend/app/Telegram/Actions/DeliverMorningGiftAction.php:42, backend/app/Telegram/Services/MorningRateLimiter.php:35, backend/app/Telegram/Infrastructure/Persistence/PostgresMorningDeliveryRepository.php:26, backend/tests/Feature/TelegramWidgetTest.php:347, :373.
