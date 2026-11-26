# Tasks

## TASK-001 · Пользовательский путь

Определить правила публикации, получения и повторного открытия.

Этап 000 · business_system · read_only_subagent · inspection

Зависимости —

Требования REQ-002, REQ-003, REQ-005, REQ-006 · AC-002, AC-003, AC-005, AC-006

Создать (полный список разрешённых путей):

—

Удаление —

CHECK-001 · inspection · `Сопоставить правила создания, вручения и открытия с пользовательским сценарием.` — Разделены черновик, публикация, привязка и первое открытие.

ponytail:ponytail (analysis). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-002 · Архитектура

Установить границы авторизации, хранения и нативного транспорта.

Этап 000 · architecture_codebase · read_only_subagent · inspection

Зависимости —

Требования REQ-001, REQ-002, REQ-003, REQ-004, REQ-005, REQ-006 · AC-001, AC-002, AC-003, AC-004, AC-005, AC-006

Создать (полный список разрешённых путей):

—

Удаление —

CHECK-002 · inspection · `Сопоставить архитектуру приложения, контракт Jobgram и платформенные границы Telegram.` — Выделены общая операция открытия, изолированный стенд и независимое состояние отправки.

ponytail:ponytail (analysis). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-003 · Риски доставки

Определить проверяемые риски очереди, квот и смены даты.

Этап 000 · qa_risk · read_only_subagent · inspection

Зависимости —

Требования REQ-003, REQ-004, REQ-005 · AC-003, AC-004, AC-005

Создать (полный список разрешённых путей):

—

Удаление —

CHECK-003 · inspection · `Проверить пределы виджетов, коллизии отправки и последствия потери ответа.` — Выделены проверка размера, дедупликация и неопределённый исход.

ponytail:ponytail (analysis). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-004 · Валидатор плана

Подключить перенесённый Artisan-валидатор и негативные контрактные тесты.

Этап 002 · planning · orchestrator · contract

Зависимости TASK-001, TASK-002, TASK-003

Требования REQ-001 · AC-001

Создать (полный список разрешённых путей):

- backend/app/Console/Commands/ValidateFastPlanCommand.php
- backend/app/Support/Planning/FastPlanValidator.php
- backend/tests/Feature/Planning/ValidateFastPlanCommandTest.php
- plan.mk

Удаление —

CHECK-004 · contract · `make test-suite suite=ValidateFastPlanCommandTest` — Без команды тесты не проходят. После переноса валидные планы проходят, повреждённые получают ожидаемые FPV-коды.

ponytail:ponytail (red, green, refactor, verification). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-005 · Календарь и Mini App

Сохранить, вручить и открыть календарь с проверкой автора, даты и неизменяемого ID.

Этап 002 · calendar · writer · tdd

Зависимости TASK-001, TASK-002, TASK-003

Требования REQ-002, REQ-003 · AC-002, AC-003

Создать (полный список разрешённых путей):

- backend/app/Calendar/CalendarEditor.php
- backend/app/Calendar/CalendarInput.php
- backend/app/Calendar/CalendarRepository.php
- backend/app/Calendar/Calendars.php
- backend/app/Calendar/MiniAppIdentity.php
- backend/app/Http/Controllers/CalendarController.php
- backend/app/Http/Controllers/MiniAppSessionController.php
- backend/app/Http/Middleware/MiniAppSession.php
- backend/app/Http/Requests/SaveCalendarRequest.php
- backend/app/Models/Calendar.php
- backend/app/Models/GiftDay.php
- backend/app/Models/TelegramUser.php
- backend/database/migrations/2026_01_01_000001_create_calendars.php
- backend/routes/calendar.php
- backend/tests/Feature/CalendarFlowTest.php

Удаление —

CHECK-005 · tdd · `make test-suite suite=Calendar` — Сценарии сначала обнаруживают отсутствие доменной операции, затем подтверждают публикацию, доступ и повторное открытие.

ponytail:ponytail (red, green, refactor, verification). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-006 · Telegram и рассылка

Отобразить две нативные карточки и доставить утренний подарок однократно.

Этап 002 · telegram · writer · tdd

Зависимости TASK-001, TASK-002, TASK-003

Требования REQ-004, REQ-005 · AC-004, AC-005

Создать (полный список разрешённых путей):

- backend/app/Console/Commands/DispatchMorning.php
- backend/app/Http/Controllers/TelegramWebhookController.php
- backend/app/Jobs/SendMorningGift.php
- backend/app/Telegram/MorningReminders.php
- backend/app/Telegram/TelegramGateway.php
- backend/app/Telegram/WidgetMessages.php
- backend/app/Telegram/WidgetRenderer.php
- backend/database/migrations/2026_01_02_000001_create_widget_messages_and_deliveries.php
- backend/resources/views/widgets/calendar.blade.php
- backend/resources/views/widgets/gift.blade.php
- backend/resources/views/widgets/morning.blade.php
- backend/resources/views/widgets/script.blade.php
- backend/resources/views/widgets/style.blade.php
- backend/routes/telegram.php
- backend/tests/Feature/TelegramWidgetTest.php

Удаление —

CHECK-006 · tdd · `make test-suite suite=Telegram` — Сценарии выявляют отсутствующие виджеты/доставку, затем подтверждают требования по срокам, доступу и квотам.

ponytail:ponytail (red, green, refactor, verification). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-007 · Локальная студия и админка

Поднять приложение в Docker и пройти реальный пользовательский путь через dev и Filament.

Этап 002 · studio · writer · tdd

Зависимости TASK-001, TASK-002, TASK-003

Требования REQ-002, REQ-004, REQ-005, REQ-006 · AC-002, AC-004, AC-005, AC-006

Создать (полный список разрешённых путей):

- .gitignore
- Makefile
- README.md
- backend/.dockerignore
- backend/.editorconfig
- backend/.env.example
- backend/.gitattributes
- backend/.gitignore
- backend/Dockerfile
- backend/app/Console/Commands/CreateStaff.php
- backend/app/Console/Commands/SeedDemo.php
- backend/app/Filament/Pages/Overview.php
- backend/app/Http/Controllers/Controller.php
- backend/app/Http/Controllers/DevController.php
- backend/app/Http/Middleware/PreviewClock.php
- backend/app/Models/User.php
- backend/app/Providers/AppServiceProvider.php
- backend/app/Providers/Filament/AdminPanelProvider.php
- backend/artisan
- backend/bootstrap/app.php
- backend/bootstrap/cache/.gitignore
- backend/bootstrap/providers.php
- backend/composer.json
- backend/composer.lock
- backend/config/app.php
- backend/config/auth.php
- backend/config/cache.php
- backend/config/database.php
- backend/config/filesystems.php
- backend/config/logging.php
- backend/config/mail.php
- backend/config/queue.php
- backend/config/services.php
- backend/config/session.php
- backend/database/.gitignore
- backend/database/factories/UserFactory.php
- backend/database/migrations/0001_01_01_000000_create_users_table.php
- backend/database/migrations/0001_01_01_000001_create_cache_table.php
- backend/database/migrations/0001_01_01_000002_create_jobs_table.php
- backend/database/seeders/DatabaseSeeder.php
- backend/phpstan.neon
- backend/phpunit.xml
- backend/public/.htaccess
- backend/public/favicon.ico
- backend/public/index.php
- backend/public/robots.txt
- backend/public/studio.css
- backend/public/studio.js
- backend/resources/views/app.blade.php
- backend/resources/views/dev.blade.php
- backend/resources/views/filament/pages/overview.blade.php
- backend/resources/views/layouts/studio.blade.php
- backend/routes/console.php
- backend/routes/dev.php
- backend/routes/web.php
- backend/storage/app/.gitignore
- backend/storage/app/private/.gitignore
- backend/storage/app/public/.gitignore
- backend/storage/framework/.gitignore
- backend/storage/framework/cache/.gitignore
- backend/storage/framework/cache/data/.gitignore
- backend/storage/framework/cache/locks/.gitignore
- backend/storage/framework/sessions/.gitignore
- backend/storage/framework/testing/.gitignore
- backend/storage/framework/views/.gitignore
- backend/storage/logs/.gitignore
- backend/tests/Feature/StudioTest.php
- backend/tests/Pest.php
- backend/tests/TestCase.php
- compose.yaml
- infra/test-databases.sql

Удаление —

CHECK-007 · tdd · `make test-suite suite=StudioTest` — Сначала локальный маршрут и защита отсутствуют. Готовая студия использует реальные интерфейсы, тестовый вход отсутствует в production.

ponytail:ponytail (red, green, refactor, verification). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.
