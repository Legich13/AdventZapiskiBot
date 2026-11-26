# Architecture / API / Ponytail

Reviewer: /root/review_arch
Axis: REV-ARCHITECTURE-API-PONYTAIL
Snapshot SHA-256: 3dc7ee3cc683a16af646c503a2ea6a657341fe0014192a47e091e49e7520d9ed
Manifest SHA-256: 6a88fb1a81919c2054ae2f1791f85f9bdbd51b5e2161327c44ec105fd25b06b0
Skills: ponytail:ponytail, ponytail:ponytail-review
Verdict: fix_required
Counts: B0=0, B1=2, B2=0

OpenAPI расходится с серверной валидацией. Для подарка себе сервер принимает запрос без ника, а схема требует username. Для widget_query схема допускает один id, хотя сервер также требует from.id и data. Нужно описать условные поля и проверить оба сценария на фактическом HTTP-ответе и экспортированной схеме. Источники: docs/contracts/openapi.json:349, backend/app/Http/Requests/Calendar/SaveCalendarRequest.php:37, backend/app/Http/Requests/TelegramWebhookRequest.php:32.

Три ссылки справочника Filament ведут на 404. Из опубликованных глав недоступны корневая архитектура, README и снимок OpenAPI. Рендерер преобразует ссылки только на Markdown внутри docs. Нужны разрешённые адреса для корневых документов, переход на защищённую схему и тест по фактическому href. Источники: backend/app/Documentation/ProjectHandbook.php:37, docs/backend/ARCHITECTURE.md:3, docs/backend/API_CONTRACT.md:13.

Путь сохранения календаря соответствует архитектуре Jobgram: Form Request проверяет ввод, Action управляет транзакцией, репозиторий записывает данные, Resource формирует ответ. Модели, фабрики и DTO охвачены исполняемыми архитектурными проверками. Проверялись backend/app/Http/Controllers/Calendar/CalendarController.php:29 и backend/tests/Unit/Architecture/AdventArchitectureTest.php:40.
