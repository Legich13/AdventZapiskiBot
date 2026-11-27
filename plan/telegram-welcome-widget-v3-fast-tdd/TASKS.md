# Tasks

## TASK-001 — Путь получателя после замечаний ревью

Выделить переходы, нуждающиеся в повторной авторизации, доступности и проверке полной даты.

Stage: 000 / business_system / read_only_subagent / inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-004

acceptanceIds: AC-001, AC-004

CHECK-001 / inspection

Проследить текущие GET и POST при смене аккаунта, истечении сессии и наступлении следующего дня.

Определены границы MiniAppLaunch, экран даты, состояние месяца и точные проверки.

ponytail:ponytail (analysis)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-002 — Совместимость сообщений и очередь

Выделить переход состояния старого /start, границу подтверждения вручения и момент запуска очереди.

Stage: 000 / architecture_codebase / read_only_subagent / inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-002, REQ-003

acceptanceIds: AC-002, AC-003

CHECK-002 / inspection

Сопоставить позиции старого и нового журнала, успешный commit, сбой 429 и подготовку файла обложки.

Для рисков первого снимка определены точные команды, изменения состояния и владеющие модули.

ponytail:ponytail (analysis)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-003 — Риски нового снимка и выпуск

Проверить новые сценарии, исходную ревизию и область измеримых интеграционных результатов.

Stage: 000 / qa_risk / read_only_subagent / inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004, REQ-005

acceptanceIds: AC-001, AC-002, AC-003, AC-004, AC-005

CHECK-003 / inspection

Сверить три заключения с контрактом Mini App, SHA-256 снимка, web-окружением и пограничными проверками.

Риск связан с проверяемым запросом или сообщением. Границы фактических сред указаны.

ponytail:ponytail (analysis)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-004 — Исходный снимок исправлений

Закрепить фактические байты, полученные рецензентами, и точные дополнительные файлы.

Stage: 001 / orchestrator / orchestrator / inspection

dependsOn: TASK-001, TASK-002, TASK-003

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004, REQ-005

acceptanceIds: AC-001, AC-002, AC-003, AC-004, AC-005

CHECK-004 / inspection

Взять байты трёхстороннего ревью 02ace923 для прежних путей и исходный Git HEAD для дополнительных существующих путей.

Манифест охватывает весь выпуск и новые исправления; новые пути имеют MISSING.

ponytail:ponytail (verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-005 — Изоляция запуска и раскрытие подарка

Привязать изменения к проверенной личности текущего запуска; сохранить отзывчивость и восстановление сессии.

Stage: 002 / calendar_ui / writer / tdd

dependsOn: TASK-004

ownedPaths: backend/app/Calendar/Actions/OpenGiftAction.php, backend/app/Calendar/DTO/RecipientPageDTO.php, backend/app/Calendar/Services/RecipientPageService.php, backend/app/Http/Controllers/Calendar/CalendarController.php, backend/app/Http/Controllers/Calendar/MiniAppSessionController.php, backend/app/Http/Controllers/Calendar/ReceivedCalendarController.php, backend/app/Http/Middleware/MiniAppLaunch.php, backend/app/Http/Requests/Calendar/EditorPageRequest.php, backend/app/Http/Resources/Calendar/OpenedGiftResource.php, backend/app/Policies/CalendarPolicy.php, backend/public/mini-app-launch.js, backend/public/studio.css, backend/public/studio.js, backend/resources/views/app.blade.php, backend/resources/views/launch.blade.php, backend/resources/views/received.blade.php, backend/routes/calendar.php, backend/tests/Feature/CalendarFlowTest.php, backend/tests/Feature/CalendarRecipientTest.php, backend/tests/Pest.php

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-004

acceptanceIds: AC-001, AC-004

CHECK-005 / tdd

make test-suite suite=CalendarRecipient

Целевой тест обнаруживает отсутствие нужной проверки и изменённой семантики UI, затем проходит на тех же получателях и датах.

ponytail:ponytail (design, red, green, refactor, verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

laravel:tdd-with-pest (red, green, refactor, verification)

Измерить целевое поведение на одном и том же входе до и после изменения. Есть диагностируемое падение, успех той же команды и целевая проверка после интеграции.

## TASK-006 — Восстановление вручения и порядок сообщений

Обрабатывать старые позиции, зафиксировать внешнюю границу отправки и порядок обложки и утреннего подарка.

Stage: 002 / telegram_studio / writer / tdd

dependsOn: TASK-004

ownedPaths: backend/app/Calendar/Actions/ClaimCalendarsAction.php, backend/app/Calendar/Contracts/CalendarRepository.php, backend/app/Calendar/DTO/ClaimedCalendarsDTO.php, backend/app/Calendar/Infrastructure/Persistence/PostgresCalendarRepository.php, backend/app/Http/Controllers/DevController.php, backend/app/Models/WidgetMessage.php, backend/app/Studio/Actions/StartPreviewChatAction.php, backend/app/Telegram/Actions/DeliverMorningGiftAction.php, backend/app/Telegram/Actions/HandleTelegramUpdateAction.php, backend/app/Telegram/Actions/PrepareStartUpdateAction.php, backend/app/Telegram/Contracts/MorningDeliveryRepository.php, backend/app/Telegram/Contracts/WidgetRepository.php, backend/app/Telegram/DTO/BotMessageDTO.php, backend/app/Telegram/DTO/StartReplyDTO.php, backend/app/Telegram/DTO/WidgetInboxItemDTO.php, backend/app/Telegram/Infrastructure/NutgramGateway.php, backend/app/Telegram/Infrastructure/Persistence/EloquentWidgetRepository.php, backend/app/Telegram/Infrastructure/Persistence/PostgresMorningDeliveryRepository.php, backend/app/Telegram/Infrastructure/Persistence/PostgresStartUpdateRepository.php, backend/app/Telegram/Infrastructure/PhotoPreparationException.php, backend/app/Telegram/Services/WidgetRenderer.php, backend/config/services.php, backend/database/migrations/2026_11_27_000001_support_bot_messages.php, backend/resources/views/dev.blade.php, backend/tests/Feature/StudioTest.php, backend/tests/Feature/TelegramReservedCalendarTest.php, backend/tests/Feature/TelegramStartReplayTest.php, backend/tests/Feature/TelegramTodayDeliveryTest.php, backend/tests/Feature/TelegramWelcomeWidgetTest.php, backend/tests/Feature/TelegramWidgetTest.php, backend/tests/Unit/Architecture/AdventArchitectureTest.php

createPaths: backend/app/Telegram/DTO/StartReplyDTO.php, backend/app/Telegram/Infrastructure/PhotoPreparationException.php

deletePaths: none

requirementIds: REQ-002, REQ-003

acceptanceIds: AC-002, AC-003

CHECK-006 / tdd

make test-suite suite='TelegramStartReplayTest|TelegramTodayDeliveryTest|TelegramWelcomeWidgetTest'

Проверка обнаруживает пропущенные вручения, преждевременные отправки и исчерпанные резервы, затем проходит без подмены транспорта в бизнес-логике.

ponytail:ponytail (design, red, green, refactor, verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

laravel:tdd-with-pest (red, green, refactor, verification)

Измерить целевое поведение на одном и том же входе до и после изменения. Есть диагностируемое падение, успех той же команды и целевая проверка после интеграции.

## TASK-007 — Web-сессия и контракт запросов

Документировать проверку запуска и задать условия работы сессии в защищённом стороннем iframe.

Stage: 002 / integration / writer / tdd

dependsOn: TASK-004

ownedPaths: README.md, backend/.env.example, backend/app/Providers/ApiDocumentationServiceProvider.php, backend/config/session.php, backend/public/images/calendar-cover.png, backend/tests/Feature/Documentation/DocumentationAccessTest.php, docs/INFRASTRUCTURE.md, docs/backend/API_CONTRACT.md, docs/backend/ARCHITECTURE.md, docs/backend/TESTING.md, docs/backend/calendar.md, docs/backend/domain-models.md, docs/backend/model-business-evidence.md, docs/backend/repositories.md, docs/backend/telegram.md, docs/contracts/openapi.json, docs/flows/morning-delivery.md, docs/flows/open-gift.md, docs/flows/publish-calendar.md, docs/flows/staff-support.md

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-005

acceptanceIds: AC-001, AC-002, AC-005

CHECK-007 / tdd

make test-suite suite=DocumentationAccessTest

Проверка определяет настоящий контракт и безопасное значение cookie рабочего окружения.

ponytail:ponytail (design, red, green, refactor, verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

laravel:tdd-with-pest (red, green, refactor, verification)

Измерить целевое поведение на одном и том же входе до и после изменения. Есть диагностируемое падение, успех той же команды и целевая проверка после интеграции.

## TASK-008 — Полная проверка

Подготовить манифест всего выпуска и сопоставить формат, контракты, поведение и документацию.

Stage: 005 / orchestrator / orchestrator / inspection

dependsOn: TASK-005, TASK-006, TASK-007

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004, REQ-005

acceptanceIds: AC-001, AC-002, AC-003, AC-004, AC-005

CHECK-008 / inspection

make quality

Проверки контейнерного окружения проходят на итоговых файлах ревизии.

CHECK-010 / inspection

make pre-commit

Diff, аудит зависимостей, маршруты и полный перечень планов проходят.

ponytail:ponytail (verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-009 — Локальная сдача

Передать результаты трёх независимых ревью, проверок и явно назвать охваченные среды.

Stage: 007 / orchestrator / orchestrator / inspection

dependsOn: TASK-008

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-005

acceptanceIds: AC-005

CHECK-009 / inspection

Сверить итоговую таблицу TDD, гейты и три заключения по одному снимку с изменениями относительно начального Git HEAD.

Отчёт подтверждает локальные результаты и отражает реальные границы Telegram-проверок.

ponytail:ponytail (handoff)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.
