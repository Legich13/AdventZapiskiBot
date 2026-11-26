# Tasks

## TASK-001 · Бизнес-анализ миграции

Названы неизменяемые пользовательские правила.

000 · business_system · read_only_subagent · inspection

Зависимости —

Критерии REQ-002, REQ-003, REQ-005, REQ-006, REQ-007 · AC-002, AC-003, AC-005, AC-006, AC-007

CHECK-001 · inspection · `Проверить правила автора, получателя и неизменность сценариев после выделения DTO.` · Названы неизменяемые пользовательские правила.

ponytail:ponytail (analysis). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-002 · Архитектура кода Jobgram

Определены архитектурные границы, проверяемые через код и контракты.

000 · architecture_codebase · read_only_subagent · inspection

Зависимости —

Критерии REQ-001, REQ-002, REQ-003, REQ-004, REQ-005, REQ-006, REQ-007 · AC-001, AC-002, AC-003, AC-004, AC-005, AC-006, AC-007

CHECK-002 · inspection · `Сопоставить файлы, PHPDoc, границы слоёв и требования документации с Jobgram.` · Определены архитектурные границы, проверяемые через код и контракты.

ponytail:ponytail (analysis). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-003 · Риски Telegram

Сохраняется привязка, дедупликация и допустимый размер.

000 · qa_risk · read_only_subagent · inspection

Зависимости —

Критерии REQ-003, REQ-004, REQ-005, REQ-007 · AC-003, AC-004, AC-005, AC-007

CHECK-003 · inspection · `Проверить риск изменения повторов Telegram, выдачи чужого сообщения и байтовых лимитов.` · Сохраняется привязка, дедупликация и допустимый размер.

ponytail:ponytail (analysis). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-004 · Контракты архитектуры и документы

RED выявляет несоответствие кодовых и документальных границ. GREEN подтверждает их устранение.

002 · planning · orchestrator · contract

Зависимости TASK-001, TASK-002, TASK-003

Критерии REQ-001, REQ-007 · AC-001, AC-007

- проверить/изменить AGENTS.md

- проверить/изменить ARCHITECTURE.md

- создать backend/AGENTS.md

- проверить/изменить backend/app/Console/Commands/ValidateFastPlanCommand.php

- проверить/изменить backend/app/Support/Planning/FastPlanValidator.php

- проверить/изменить backend/tests/Feature/Planning/ValidateFastPlanCommandTest.php

- создать backend/tests/Unit/Architecture/AdventArchitectureTest.php

- создать backend/tests/Unit/Architecture/TestNamingLanguageContractTest.php

- создать docs/COMMANDS.md

- создать docs/INFRASTRUCTURE.md

- создать docs/backend/API_CONTRACT.md

- создать docs/backend/ARCHITECTURE.md

- создать docs/backend/README.md

- создать docs/backend/TESTING.md

- создать docs/backend/domain-models.md

- создать docs/backend/model-business-evidence.md

- создать docs/backend/repositories.md

- создать docs/backend/seeders-and-factories.md

- создать docs/flows/morning-delivery.md

- создать docs/flows/open-gift.md

- создать docs/flows/publish-calendar.md

- создать docs/flows/staff-support.md

- проверить/изменить plan.mk

CHECK-004 · contract · `make test-suite suite=AdventArchitectureTest` · RED выявляет несоответствие кодовых и документальных границ. GREEN подтверждает их устранение.

ponytail:ponytail (red, green, refactor, verification). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-005 · Архитектура модуля Calendar

RED указывает на отсутствие Action / DTO / Factory. GREEN сохраняет публикацию, привязку и открытие.

002 · calendar · writer · tdd

Зависимости TASK-001, TASK-002, TASK-003

Критерии REQ-002, REQ-003, REQ-007 · AC-002, AC-003, AC-007

- создать backend/app/Calendar/Actions/ClaimCalendarsAction.php

- создать backend/app/Calendar/Actions/OpenGiftAction.php

- создать backend/app/Calendar/Actions/PublishCalendarAction.php

- создать backend/app/Calendar/Actions/RegisterTelegramUserAction.php

- создать backend/app/Calendar/Actions/SaveCalendarAction.php

- удалить backend/app/Calendar/CalendarEditor.php

- удалить backend/app/Calendar/CalendarInput.php

- удалить backend/app/Calendar/CalendarRepository.php

- удалить backend/app/Calendar/Calendars.php

- создать backend/app/Calendar/Contracts/CalendarRepository.php

- создать backend/app/Calendar/DTO/CalendarDTO.php

- создать backend/app/Calendar/DTO/CalendarInputDTO.php

- создать backend/app/Calendar/DTO/EditorPageDTO.php

- создать backend/app/Calendar/DTO/OpenedGiftDTO.php

- создать backend/app/Calendar/DTO/TelegramIdentityDTO.php

- создать backend/app/Calendar/Infrastructure/Persistence/PostgresCalendarRepository.php

- удалить backend/app/Calendar/MiniAppIdentity.php

- создать backend/app/Calendar/Providers/CalendarServiceProvider.php

- создать backend/app/Calendar/Services/CalendarPublicationService.php

- создать backend/app/Calendar/Services/EditorPageService.php

- создать backend/app/Calendar/Services/MiniAppIdentityVerifier.php

- создать backend/app/Http/Controllers/Calendar/CalendarController.php

- создать backend/app/Http/Controllers/Calendar/MiniAppSessionController.php

- удалить backend/app/Http/Controllers/CalendarController.php

- удалить backend/app/Http/Controllers/MiniAppSessionController.php

- проверить/изменить backend/app/Http/Middleware/MiniAppSession.php

- создать backend/app/Http/Requests/Calendar/EditorPageRequest.php

- создать backend/app/Http/Requests/Calendar/MiniAppLoginRequest.php

- создать backend/app/Http/Requests/Calendar/PublishCalendarRequest.php

- создать backend/app/Http/Requests/Calendar/SaveCalendarRequest.php

- удалить backend/app/Http/Requests/SaveCalendarRequest.php

- создать backend/app/Http/Resources/Calendar/CalendarIdResource.php

- проверить/изменить backend/app/Models/Calendar.php

- проверить/изменить backend/app/Models/GiftDay.php

- проверить/изменить backend/app/Models/TelegramUser.php

- создать backend/app/Policies/CalendarPolicy.php

- создать backend/database/factories/CalendarFactory.php

- создать backend/database/factories/GiftDayFactory.php

- создать backend/database/factories/TelegramUserFactory.php

- проверить/изменить backend/database/migrations/2026_01_01_000001_create_calendars.php

- проверить/изменить backend/resources/views/app.blade.php

- проверить/изменить backend/routes/calendar.php

- проверить/изменить backend/tests/Feature/CalendarFlowTest.php

- создать docs/backend/calendar.md

CHECK-005 · tdd · `make test-suite suite=Calendar` · RED указывает на отсутствие Action / DTO / Factory. GREEN сохраняет публикацию, привязку и открытие.

ponytail:ponytail (red, green, refactor, verification). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-006 · Архитектура модуля Telegram

RED выявляет прежний вход webhook и отсутствие новой границы. GREEN подтверждает доставку и права.

002 · telegram · writer · tdd

Зависимости TASK-001, TASK-002, TASK-003

Критерии REQ-004, REQ-005, REQ-007 · AC-004, AC-005, AC-007

- проверить/изменить backend/app/Console/Commands/DispatchMorning.php

- проверить/изменить backend/app/Http/Controllers/TelegramWebhookController.php

- создать backend/app/Http/Requests/TelegramWebhookRequest.php

- проверить/изменить backend/app/Jobs/SendMorningGift.php

- создать backend/app/Models/MorningDelivery.php

- создать backend/app/Models/WidgetMessage.php

- создать backend/app/Providers/TelegramServiceProvider.php

- создать backend/app/Telegram/Actions/AnswerWidgetAction.php

- создать backend/app/Telegram/Actions/DeliverMorningGiftAction.php

- создать backend/app/Telegram/Actions/DispatchMorningRemindersAction.php

- создать backend/app/Telegram/Actions/HandleTelegramUpdateAction.php

- создать backend/app/Telegram/Actions/SendWidgetAction.php

- создать backend/app/Telegram/Contracts/MorningDeliveryRepository.php

- создать backend/app/Telegram/Contracts/WidgetRepository.php

- создать backend/app/Telegram/DTO/MorningDeliveryDTO.php

- создать backend/app/Telegram/DTO/RenderedWidgetDTO.php

- создать backend/app/Telegram/DTO/StartUpdateDTO.php

- создать backend/app/Telegram/DTO/WidgetInboxItemDTO.php

- создать backend/app/Telegram/DTO/WidgetQueryDTO.php

- создать backend/app/Telegram/DTO/WidgetStateDTO.php

- создать backend/app/Telegram/DTO/WidgetTargetDTO.php

- создать backend/app/Telegram/Infrastructure/NutgramGateway.php

- создать backend/app/Telegram/Infrastructure/Persistence/EloquentWidgetRepository.php

- создать backend/app/Telegram/Infrastructure/Persistence/PostgresMorningDeliveryRepository.php

- удалить backend/app/Telegram/MorningReminders.php

- создать backend/app/Telegram/Services/MorningRateLimiter.php

- создать backend/app/Telegram/Services/WidgetInboxService.php

- создать backend/app/Telegram/Services/WidgetRenderer.php

- удалить backend/app/Telegram/TelegramGateway.php

- удалить backend/app/Telegram/WidgetMessages.php

- удалить backend/app/Telegram/WidgetRenderer.php

- создать backend/database/factories/MorningDeliveryFactory.php

- создать backend/database/factories/WidgetMessageFactory.php

- проверить/изменить backend/database/migrations/2026_01_02_000001_create_widget_messages_and_deliveries.php

- проверить/изменить backend/resources/views/widgets/calendar.blade.php

- проверить/изменить backend/resources/views/widgets/gift.blade.php

- проверить/изменить backend/resources/views/widgets/morning.blade.php

- проверить/изменить backend/resources/views/widgets/script.blade.php

- проверить/изменить backend/resources/views/widgets/style.blade.php

- проверить/изменить backend/routes/telegram.php

- проверить/изменить backend/tests/Feature/TelegramWidgetTest.php

- создать docs/backend/telegram.md

CHECK-006 · tdd · `make test-suite suite=Telegram` · RED выявляет прежний вход webhook и отсутствие новой границы. GREEN подтверждает доставку и права.

ponytail:ponytail (red, green, refactor, verification). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.

## TASK-007 · Архитектура Studio, Filament и OpenAPI

RED обнаруживает доступ к БД из панели и отсутствующий справочник. GREEN подтверждает границы и защиту справки.

002 · studio · writer · tdd

Зависимости TASK-001, TASK-002, TASK-003

Критерии REQ-002, REQ-004, REQ-005, REQ-006, REQ-007 · AC-002, AC-004, AC-005, AC-006, AC-007

- проверить/изменить .gitignore

- проверить/изменить Makefile

- проверить/изменить README.md

- проверить/изменить backend/.dockerignore

- проверить/изменить backend/.editorconfig

- проверить/изменить backend/.env.example

- проверить/изменить backend/.gitattributes

- проверить/изменить backend/.gitignore

- проверить/изменить backend/Dockerfile

- создать backend/app/Admin/DTO/CalendarOverviewRowDTO.php

- создать backend/app/Admin/DTO/MorningDeliveryRowDTO.php

- создать backend/app/Admin/DTO/TelegramPersonDTO.php

- создать backend/app/Admin/Infrastructure/Persistence/AdminOverviewRepository.php

- создать backend/app/Admin/Services/AdminOverviewService.php

- проверить/изменить backend/app/Console/Commands/CreateStaff.php

- проверить/изменить backend/app/Console/Commands/SeedDemo.php

- создать backend/app/Documentation/ProjectHandbook.php

- создать backend/app/Filament/Pages/Handbook.php

- проверить/изменить backend/app/Filament/Pages/Overview.php

- проверить/изменить backend/app/Http/Controllers/Controller.php

- проверить/изменить backend/app/Http/Controllers/DevController.php

- создать backend/app/Http/Middleware/LocalStudioOnly.php

- проверить/изменить backend/app/Http/Middleware/PreviewClock.php

- создать backend/app/Http/Requests/Studio/PreviewSessionRequest.php

- создать backend/app/Http/Requests/Studio/PreviewWidgetQueryRequest.php

- создать backend/app/Http/Requests/Studio/SelectPreviewProfileRequest.php

- проверить/изменить backend/app/Models/User.php

- создать backend/app/Providers/ApiDocumentationServiceProvider.php

- проверить/изменить backend/app/Providers/AppServiceProvider.php

- проверить/изменить backend/app/Providers/Filament/AdminPanelProvider.php

- создать backend/app/Studio/Actions/OpenPreviewPanelAction.php

- создать backend/app/Studio/Actions/RunPreviewMorningAction.php

- создать backend/app/Studio/Actions/SelectPreviewProfileAction.php

- создать backend/app/Studio/Actions/StartPreviewChatAction.php

- создать backend/app/Studio/DemoProfiles.php

- создать backend/app/Studio/Infrastructure/Persistence/PreviewStaffRepository.php

- проверить/изменить backend/artisan

- проверить/изменить backend/bootstrap/app.php

- проверить/изменить backend/bootstrap/cache/.gitignore

- проверить/изменить backend/bootstrap/providers.php

- проверить/изменить backend/composer.json

- проверить/изменить backend/composer.lock

- проверить/изменить backend/config/app.php

- проверить/изменить backend/config/auth.php

- проверить/изменить backend/config/cache.php

- проверить/изменить backend/config/database.php

- проверить/изменить backend/config/filesystems.php

- проверить/изменить backend/config/logging.php

- проверить/изменить backend/config/mail.php

- проверить/изменить backend/config/nutgram.php

- проверить/изменить backend/config/queue.php

- создать backend/config/scramble.php

- проверить/изменить backend/config/services.php

- проверить/изменить backend/config/session.php

- проверить/изменить backend/database/.gitignore

- проверить/изменить backend/database/factories/UserFactory.php

- проверить/изменить backend/database/migrations/0001_01_01_000000_create_users_table.php

- проверить/изменить backend/database/migrations/0001_01_01_000001_create_cache_table.php

- проверить/изменить backend/database/migrations/0001_01_01_000002_create_jobs_table.php

- проверить/изменить backend/database/migrations/2026_01_03_000001_create_permission_tables.php

- проверить/изменить backend/database/seeders/DatabaseSeeder.php

- проверить/изменить backend/phpstan.neon

- проверить/изменить backend/phpunit.xml

- создать backend/pint.json

- проверить/изменить backend/public/.htaccess

- проверить/изменить backend/public/favicon.ico

- проверить/изменить backend/public/index.php

- проверить/изменить backend/public/robots.txt

- проверить/изменить backend/public/studio.css

- проверить/изменить backend/public/studio.js

- создать backend/rector.php

- проверить/изменить backend/resources/views/dev.blade.php

- создать backend/resources/views/filament/pages/handbook.blade.php

- проверить/изменить backend/resources/views/filament/pages/overview.blade.php

- проверить/изменить backend/resources/views/layouts/studio.blade.php

- создать backend/resources/views/preview-widget.blade.php

- проверить/изменить backend/routes/console.php

- проверить/изменить backend/routes/dev.php

- проверить/изменить backend/routes/web.php

- проверить/изменить backend/storage/app/.gitignore

- проверить/изменить backend/storage/app/private/.gitignore

- проверить/изменить backend/storage/app/public/.gitignore

- проверить/изменить backend/storage/framework/.gitignore

- проверить/изменить backend/storage/framework/cache/.gitignore

- проверить/изменить backend/storage/framework/cache/data/.gitignore

- проверить/изменить backend/storage/framework/cache/locks/.gitignore

- проверить/изменить backend/storage/framework/sessions/.gitignore

- проверить/изменить backend/storage/framework/testing/.gitignore

- проверить/изменить backend/storage/framework/views/.gitignore

- проверить/изменить backend/storage/logs/.gitignore

- создать backend/tests/Feature/Documentation/DocumentationAccessTest.php

- проверить/изменить backend/tests/Feature/StudioTest.php

- проверить/изменить backend/tests/Pest.php

- проверить/изменить backend/tests/TestCase.php

- создать backend/tests/Unit/Architecture/StudioBoundaryTest.php

- проверить/изменить compose.yaml

- создать docs/contracts/openapi.json

- проверить/изменить infra/test-databases.sql

CHECK-007 · tdd · `make test-suite suite='StudioBoundaryTest|DocumentationAccessTest'` · RED обнаруживает доступ к БД из панели и отсутствующий справочник. GREEN подтверждает границы и защиту справки.

ponytail:ponytail (red, green, refactor, verification). Использовать общие операции для Telegram и локальной среды. В каждом пользовательском действии действует одна реализация бизнес-правила.
