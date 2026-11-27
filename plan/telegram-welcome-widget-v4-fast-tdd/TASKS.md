# Tasks

## TASK-001 — Вручение ранее созданного календаря

Проследить получение открытки и утреннего сообщения после обновления при частично исполненном /start.

Stage: 000 / business_system / read_only_subagent / inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-004

acceptanceIds: AC-001, AC-004

CHECK-001 / inspection

Сопоставить порядок сообщений, календарь и действия получателя при старом частичном вручении и повторном входе.

Выделены наблюдаемые изменения в чате и признаки неизменности Mini App.

ponytail:ponytail (analysis)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-002 — Журнал и хранилище сообщений

Проследить записи старого и нового обработчика по календарям и исходам отправки.

Stage: 000 / architecture_codebase / read_only_subagent / inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003

acceptanceIds: AC-001, AC-002, AC-003

CHECK-002 / inspection

Сопоставить журнал /start, московский день вручения, очереди, подтверждённый ответ Telegram и потерю ответа.

Для каждого состояния определены представление в базе и следующий разрешённый шаг.

ponytail:ponytail (analysis)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-003 — Риски перехода и проверка

Связать реальные замечания независимых ревью с проверяемыми переходами данных и перечнем исходных свидетельств.

Stage: 000 / qa_risk / read_only_subagent / inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004

acceptanceIds: AC-001, AC-002, AC-003, AC-004

CHECK-003 / inspection

Сверить три заключения v3, следы отправки, порядок старой очереди и форму неизменного пользовательского пути.

Определены тестовый вход, путь обновления и границы фактической проверки.

ponytail:ponytail (analysis)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-004 — Фиксация исходных байтов

Закрепить полный предыдущий снимок и точные новые пути до исправления.

Stage: 001 / orchestrator / orchestrator / inspection

dependsOn: TASK-001, TASK-002, TASK-003

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004

acceptanceIds: AC-001, AC-002, AC-003, AC-004

CHECK-004 / inspection

Сопоставить байты всех 71 файлов по SHA-256 манифеста v3 24e7aa4e и проверить отсутствие двух новых путей в рабочем дереве и исходном Git HEAD 29bc7c8.

Манифест охватывает предыдущий выпуск и добавленные файлы миграции и теста.

ponytail:ponytail (verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-005 — Обновление вручения и следов отправки

Зафиксировать ожидающие первые открытки ранее вручённых календарей и различить известный отказ и неопределённый результат.

Stage: 002 / telegram_delivery / writer / tdd

dependsOn: TASK-004

ownedPaths: backend/app/Telegram/Actions/HandleTelegramUpdateAction.php, backend/app/Telegram/DTO/StartReplyDTO.php, backend/app/Telegram/Infrastructure/Persistence/PostgresMorningDeliveryRepository.php, backend/app/Telegram/Infrastructure/Persistence/PostgresStartUpdateRepository.php, backend/database/migrations/2026_11_27_000001_support_bot_messages.php, backend/database/migrations/2026_11_27_000002_track_start_delivery.php, backend/tests/Feature/TelegramStartUpgradeTest.php

createPaths: backend/database/migrations/2026_11_27_000002_track_start_delivery.php, backend/tests/Feature/TelegramStartUpgradeTest.php

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003

acceptanceIds: AC-001, AC-002, AC-003

CHECK-005 / tdd

make test-suite suite=TelegramStartUpgradeTest

Поведенческий сценарий воспроизводит опережение открытки и различает остатки прежнего и нового журналов, повтор и фактический отказ.

ponytail:ponytail (design, red, green, refactor, verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

laravel:tdd-with-pest (red, green, refactor, verification)

Измерить целевое поведение на одном и том же входе до и после изменения. Есть диагностируемое падение, успех той же команды и целевая проверка после интеграции.

## TASK-006 — Проверка итогового выпуска

Сверить документы поддержки, исправления бота и весь пользовательский выпуск с предыдущим снимком. Проверить общие гейты.

Stage: 005 / orchestrator / orchestrator / inspection

dependsOn: TASK-005

ownedPaths: README.md, backend/.env.example, backend/app/Calendar/Actions/ClaimCalendarsAction.php, backend/app/Calendar/Actions/OpenGiftAction.php, backend/app/Calendar/Contracts/CalendarRepository.php, backend/app/Calendar/DTO/ClaimedCalendarsDTO.php, backend/app/Calendar/DTO/RecipientPageDTO.php, backend/app/Calendar/Infrastructure/Persistence/PostgresCalendarRepository.php, backend/app/Calendar/Services/RecipientPageService.php, backend/app/Http/Controllers/Calendar/CalendarController.php, backend/app/Http/Controllers/Calendar/MiniAppSessionController.php, backend/app/Http/Controllers/Calendar/ReceivedCalendarController.php, backend/app/Http/Controllers/DevController.php, backend/app/Http/Middleware/MiniAppLaunch.php, backend/app/Http/Requests/Calendar/EditorPageRequest.php, backend/app/Http/Resources/Calendar/OpenedGiftResource.php, backend/app/Models/WidgetMessage.php, backend/app/Policies/CalendarPolicy.php, backend/app/Providers/ApiDocumentationServiceProvider.php, backend/app/Studio/Actions/StartPreviewChatAction.php, backend/app/Telegram/Actions/DeliverMorningGiftAction.php, backend/app/Telegram/Actions/PrepareStartUpdateAction.php, backend/app/Telegram/Contracts/MorningDeliveryRepository.php, backend/app/Telegram/Contracts/WidgetRepository.php, backend/app/Telegram/DTO/BotMessageDTO.php, backend/app/Telegram/DTO/WidgetInboxItemDTO.php, backend/app/Telegram/Infrastructure/NutgramGateway.php, backend/app/Telegram/Infrastructure/Persistence/EloquentWidgetRepository.php, backend/app/Telegram/Infrastructure/PhotoPreparationException.php, backend/app/Telegram/Services/WidgetRenderer.php, backend/config/services.php, backend/config/session.php, backend/public/images/calendar-cover.png, backend/public/mini-app-launch.js, backend/public/studio.css, backend/public/studio.js, backend/resources/views/app.blade.php, backend/resources/views/dev.blade.php, backend/resources/views/launch.blade.php, backend/resources/views/received.blade.php, backend/routes/calendar.php, backend/tests/Feature/CalendarFlowTest.php, backend/tests/Feature/CalendarRecipientTest.php, backend/tests/Feature/Documentation/DocumentationAccessTest.php, backend/tests/Feature/StudioTest.php, backend/tests/Feature/TelegramReservedCalendarTest.php, backend/tests/Feature/TelegramStartReplayTest.php, backend/tests/Feature/TelegramTodayDeliveryTest.php, backend/tests/Feature/TelegramWelcomeWidgetTest.php, backend/tests/Feature/TelegramWidgetTest.php, backend/tests/Pest.php, backend/tests/Unit/Architecture/AdventArchitectureTest.php, docs/INFRASTRUCTURE.md, docs/backend/API_CONTRACT.md, docs/backend/ARCHITECTURE.md, docs/backend/TESTING.md, docs/backend/calendar.md, docs/backend/domain-models.md, docs/backend/model-business-evidence.md, docs/backend/repositories.md, docs/backend/telegram.md, docs/contracts/openapi.json, docs/flows/morning-delivery.md, docs/flows/open-gift.md, docs/flows/publish-calendar.md, docs/flows/staff-support.md

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004

acceptanceIds: AC-001, AC-002, AC-003, AC-004

CHECK-006 / inspection

make quality

Стиль, архитектура, типы, точность экспорта и все тесты проходят на итоговом манифесте.

CHECK-008 / inspection

make pre-commit

Diff, аудит зависимостей, маршруты и полный перечень планов проходят.

ponytail:ponytail (verification)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.

## TASK-007 — Локальная сдача

Передать подтверждённый локальный результат, ограничения окружения и независимые заключения.

Stage: 007 / orchestrator / orchestrator / inspection

dependsOn: TASK-006

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004

acceptanceIds: AC-001, AC-002, AC-003, AC-004

CHECK-007 / inspection

Сверить все проверки, путь штатного обновления, соответствие прежнему UX и три решения по замороженному пакету.

Решения, ограничения, исходы и проверенные пути отражены в итогах локальной сдачи.

ponytail:ponytail (handoff)

Использовать существующие компоненты и проверять только затронутые границы. Каждая проверка относится к пользовательскому действию или его публичному контракту.
