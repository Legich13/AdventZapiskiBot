# Tasks

## TASK-001 — Сценарии автора и получателя

Проследить вручение, прежний путь автора и переход к конкретной записке.

Stage: 000 / business_system / read_only_subagent / inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-004

acceptanceIds: AC-001, AC-004

CHECK-001 / inspection

Сопоставить экран автора и права получателя с текущими операциями календаря.

Указаны раздельные роли, пригодные операции и точный переход к дате.

ponytail:ponytail (analysis)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-002 — Маршруты сообщений и повторная доставка

Выявить путь каждого исходящего сообщения и влияние замены транспорта.

Stage: 000 / architecture_codebase / read_only_subagent / inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-002, REQ-003

acceptanceIds: AC-002, AC-003

CHECK-002 / inspection

Проследить обработку /start и утреннюю доставку в транзакциях и транспортном адаптере.

Определены проверяемые границы, повторы, неопределённый результат и старые виджеты.

ponytail:ponytail (analysis)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-003 — Проверки и переход с прежних версий

Определить риски приватности, состояний, повторов и подготовки к выпуску.

Stage: 000 / qa_risk / read_only_subagent / inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-003, REQ-004, REQ-005

acceptanceIds: AC-001, AC-003, AC-004, AC-005

CHECK-003 / inspection

Сверить проверки входа, раскрытия записки и повторов после смены дня, аккаунта и транспорта.

Выделены пропущенные сценарии и граница между локальным доказательством и рабочим ботом.

ponytail:ponytail (analysis)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-004 — Исходный снимок

Зафиксировать исходные файлы, новый перечень и ответственных.

Stage: 001 / orchestrator / orchestrator / inspection

dependsOn: TASK-001, TASK-002, TASK-003

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004, REQ-005

acceptanceIds: AC-001, AC-002, AC-003, AC-004, AC-005

CHECK-004 / inspection

Проверить назначение точных путей и их исходные SHA-256 по первоначальному чистому 29bc7c8.

Каждая аренда имеет исходные байты или MISSING.

ponytail:ponytail (verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-005 — Главная, календарь и записка

Показать месяцы, состояния дней, открытие и повторное чтение через зарегистрированную сессию.

Stage: 002 / calendar_ui / writer / tdd

dependsOn: TASK-004

ownedPaths: backend/app/Calendar/Actions/OpenGiftAction.php, backend/app/Calendar/Contracts/CalendarRepository.php, backend/app/Calendar/DTO/RecipientPageDTO.php, backend/app/Calendar/Infrastructure/Persistence/PostgresCalendarRepository.php, backend/app/Calendar/Services/RecipientPageService.php, backend/app/Http/Controllers/Calendar/CalendarController.php, backend/app/Http/Controllers/Calendar/MiniAppSessionController.php, backend/app/Http/Controllers/Calendar/ReceivedCalendarController.php, backend/app/Http/Middleware/MiniAppLaunch.php, backend/app/Http/Requests/Calendar/EditorPageRequest.php, backend/app/Http/Resources/Calendar/OpenedGiftResource.php, backend/app/Policies/CalendarPolicy.php, backend/public/mini-app-launch.js, backend/public/studio.css, backend/public/studio.js, backend/resources/views/app.blade.php, backend/resources/views/launch.blade.php, backend/resources/views/received.blade.php, backend/routes/calendar.php, backend/tests/Feature/CalendarFlowTest.php, backend/tests/Feature/CalendarRecipientTest.php

createPaths: backend/app/Calendar/DTO/RecipientPageDTO.php, backend/app/Calendar/Services/RecipientPageService.php, backend/app/Http/Controllers/Calendar/ReceivedCalendarController.php, backend/app/Http/Middleware/MiniAppLaunch.php, backend/app/Http/Resources/Calendar/OpenedGiftResource.php, backend/public/mini-app-launch.js, backend/resources/views/launch.blade.php, backend/resources/views/received.blade.php, backend/tests/Feature/CalendarRecipientTest.php

deletePaths: none

requirementIds: REQ-001, REQ-003, REQ-004

acceptanceIds: AC-001, AC-003, AC-004

CHECK-005 / tdd

make test-suite suite=CalendarRecipient

Наличие личной главной, безопасного перехода и открытия одного дня переводит целевой тест из RED в GREEN.

ponytail:ponytail (design, red, green, refactor, verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

laravel:tdd-with-pest (red, green, refactor, verification)

Измерить целевое поведение на одном и том же входе до и после изменения. Есть диагностируемое падение, успех той же команды и целевая проверка после интеграции.

## TASK-006 — Сообщения бота и чат мастерской

Доставить одну открытку, компактные уведомления и воспроизводимый локальный чат.

Stage: 002 / telegram_studio / writer / tdd

dependsOn: TASK-004

ownedPaths: backend/.env.example, backend/app/Calendar/Actions/ClaimCalendarsAction.php, backend/app/Calendar/DTO/ClaimedCalendarsDTO.php, backend/app/Http/Controllers/DevController.php, backend/app/Models/WidgetMessage.php, backend/app/Studio/Actions/StartPreviewChatAction.php, backend/app/Telegram/Actions/DeliverMorningGiftAction.php, backend/app/Telegram/Actions/HandleTelegramUpdateAction.php, backend/app/Telegram/Actions/PrepareStartUpdateAction.php, backend/app/Telegram/Actions/SendWidgetAction.php, backend/app/Telegram/Contracts/WidgetRepository.php, backend/app/Telegram/DTO/BotMessageDTO.php, backend/app/Telegram/DTO/WidgetInboxItemDTO.php, backend/app/Telegram/Infrastructure/NutgramGateway.php, backend/app/Telegram/Infrastructure/Persistence/EloquentWidgetRepository.php, backend/app/Telegram/Infrastructure/Persistence/PostgresStartUpdateRepository.php, backend/app/Telegram/Services/WidgetRenderer.php, backend/config/services.php, backend/database/migrations/2026_11_27_000001_support_bot_messages.php, backend/resources/views/dev.blade.php, backend/tests/Feature/StudioTest.php, backend/tests/Feature/TelegramReservedCalendarTest.php, backend/tests/Feature/TelegramStartReplayTest.php, backend/tests/Feature/TelegramTodayDeliveryTest.php, backend/tests/Feature/TelegramWelcomeWidgetTest.php, backend/tests/Feature/TelegramWidgetTest.php, backend/tests/Unit/Architecture/AdventArchitectureTest.php

createPaths: backend/app/Calendar/DTO/ClaimedCalendarsDTO.php, backend/app/Telegram/DTO/BotMessageDTO.php, backend/database/migrations/2026_11_27_000001_support_bot_messages.php

deletePaths: backend/app/Telegram/Actions/SendWidgetAction.php

requirementIds: REQ-002, REQ-003, REQ-004

acceptanceIds: AC-002, AC-003, AC-004

CHECK-006 / tdd

make test-suite suite='TelegramWelcomeWidgetTest|StudioTest.*обычные сообщения'

Реальные команды библиотеки для текста и фото, кнопки и сообщения мастерской удовлетворяют новому контракту.

ponytail:ponytail (design, red, green, refactor, verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

laravel:tdd-with-pest (red, green, refactor, verification)

Измерить целевое поведение на одном и том же входе до и после изменения. Есть диагностируемое падение, успех той же команды и целевая проверка после интеграции.

## TASK-007 — Контракт открытия и материалы выпуска

Согласовать защищённое открытие, экспорт Scramble, обложку для вручения и сценарии поддержки.

Stage: 002 / integration / writer / tdd

dependsOn: TASK-004

ownedPaths: README.md, backend/app/Providers/ApiDocumentationServiceProvider.php, backend/public/images/calendar-cover.png, backend/tests/Feature/Documentation/DocumentationAccessTest.php, docs/INFRASTRUCTURE.md, docs/backend/API_CONTRACT.md, docs/backend/ARCHITECTURE.md, docs/backend/TESTING.md, docs/backend/calendar.md, docs/backend/domain-models.md, docs/backend/model-business-evidence.md, docs/backend/repositories.md, docs/backend/telegram.md, docs/contracts/openapi.json, docs/flows/morning-delivery.md, docs/flows/open-gift.md, docs/flows/publish-calendar.md, docs/flows/staff-support.md

createPaths: backend/public/images/calendar-cover.png

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-004, REQ-005

acceptanceIds: AC-001, AC-002, AC-004, AC-005

CHECK-007 / tdd

make test-suite suite=DocumentationAccessTest

OpenAPI описывает доступ к выбранной записке, документация проходит контрактные проверки.

ponytail:ponytail (design, red, green, refactor, verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

laravel:tdd-with-pest (red, green, refactor, verification)

Измерить целевое поведение на одном и том же входе до и после изменения. Есть диагностируемое падение, успех той же команды и целевая проверка после интеграции.

## TASK-008 — Полная проверка

Подготовить манифест и проверить единое состояние приложения в контейнерах.

Stage: 005 / orchestrator / orchestrator / inspection

dependsOn: TASK-005, TASK-006, TASK-007

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004, REQ-005

acceptanceIds: AC-001, AC-002, AC-003, AC-004, AC-005

CHECK-008 / inspection

make quality

Код, контракты и все сценарии проходят полный quality.

CHECK-010 / inspection

make pre-commit

Diff, аудит зависимостей, маршруты и полный перечень планов проходят.

ponytail:ponytail (verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-009 — Передача результата

Свести проверки, результаты независимых ревью и сведения о реальном окружении.

Stage: 007 / orchestrator / orchestrator / inspection

dependsOn: TASK-008

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-005

acceptanceIds: AC-005

CHECK-009 / inspection

Сверить готовый манифест с результатами проверок и перечислить фактически проверенные среды.

Состояние проекта и внешней проверки указаны раздельно.

ponytail:ponytail (handoff)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.
