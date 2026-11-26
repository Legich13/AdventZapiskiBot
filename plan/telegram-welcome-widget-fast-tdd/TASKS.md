# Tasks


## TASK-001 — Продукт и единое оформление

Stage: 000
Objective: Один статический приветственный виджет использует общий стиль и встроенную ссылку.
Role: business_system
Mode: read_only_subagent
Verification: inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-003

acceptanceIds: AC-001, AC-002, AC-005, AC-006


CHECK-001 | inspection
Command: Сопоставить запрос пользователя с существующими красными шаблонами, CTA и местом приветствия.
Expected: Один статический приветственный виджет использует общий стиль и встроенную ссылку.


Skill: ponytail:ponytail
applyAt: analysis
purpose: Использовать существующие границы и минимальный полный объём изменения.
expectedOutcome: Нет новых хранилищ, зависимостей или дублирования оформления.


## TASK-002 — Архитектура и доставка

Stage: 000
Objective: Рендер приветствия не зависит от календаря; отправка остаётся в шлюзе; БД и API не расширяются.
Role: architecture_codebase
Mode: read_only_subagent
Verification: inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-001, REQ-002

acceptanceIds: AC-001, AC-003, AC-004


CHECK-002 | inspection
Command: Проверить WidgetRenderer, NutgramGateway, start/sendOnce и границы хранилища виджетов.
Expected: Рендер приветствия не зависит от календаря; отправка остаётся в шлюзе; БД и API не расширяются.


Skill: ponytail:ponytail
applyAt: analysis
purpose: Использовать существующие границы и минимальный полный объём изменения.
expectedOutcome: Нет новых хранилищ, зависимостей или дублирования оформления.


## TASK-003 — TDD и риски приёмки

Stage: 000
Objective: Есть диагностируемые RED/GREEN на исходящей отправке и мастерской; внешняя проверка Telegram отделена от локальных доказательств.
Role: qa_risk
Mode: read_only_subagent
Verification: inspection

dependsOn: none

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-002, REQ-004

acceptanceIds: AC-003, AC-004, AC-007, AC-008


CHECK-003 | inspection
Command: Проверить публичные границы Pest, команды Make, локальный предпросмотр и ограничения запуска Mini App.
Expected: Есть диагностируемые RED/GREEN на исходящей отправке и мастерской; внешняя проверка Telegram отделена от локальных доказательств.


Skill: ponytail:ponytail
applyAt: analysis
purpose: Использовать существующие границы и минимальный полный объём изменения.
expectedOutcome: Нет новых хранилищ, зависимостей или дублирования оформления.


## TASK-004 — Приветствие и мастерская через TDD

Stage: 002
Objective: Общее приветствие отправляется нативным виджетом и отображается в локальной мастерской.
Role: backend_ui
Mode: writer
Verification: tdd

dependsOn: TASK-001, TASK-002, TASK-003

ownedPaths: README.md, backend/.env.example, backend/app/Http/Controllers/DevController.php, backend/app/Telegram/Infrastructure/NutgramGateway.php, backend/app/Telegram/Services/WidgetRenderer.php, backend/config/services.php, backend/public/studio.css, backend/resources/views/dev.blade.php, backend/resources/views/widgets/calendar.blade.php, backend/resources/views/widgets/garland.blade.php, backend/resources/views/widgets/style.blade.php, backend/resources/views/widgets/welcome.blade.php, backend/tests/Feature/StudioTest.php, backend/tests/Feature/TelegramWelcomeWidgetTest.php, backend/tests/Feature/TelegramWidgetTest.php, docs/backend/telegram.md, docs/flows/publish-calendar.md

createPaths: backend/resources/views/widgets/garland.blade.php, backend/resources/views/widgets/welcome.blade.php, backend/tests/Feature/TelegramWelcomeWidgetTest.php

deletePaths: none

requirementIds: REQ-001, REQ-002, REQ-003, REQ-004

acceptanceIds: AC-001, AC-002, AC-003, AC-004, AC-005, AC-006, AC-007


CHECK-004 | tdd
Command: make dev-test-filter filter=TelegramWelcomeWidgetTest
Expected: RED: вместо ожидаемого sendWidget отправляется sendMessage; GREEN: новое сообщение, ссылка и лимиты проходят.


CHECK-005 | tdd
Command: make dev-test-filter filter=StudioTest
Expected: RED: мастерская не показывает приветственную карточку; GREEN: тот же шаблон и локальная CTA доступны.


Skill: ponytail:ponytail
applyAt: design, red, green, refactor, verification
purpose: Использовать существующие границы и минимальный полный объём изменения.
expectedOutcome: Нет новых хранилищ, зависимостей или дублирования оформления.


Skill: tdd
applyAt: red, green, verification
purpose: Проверять новое поведение до реализации через публичные границы.
expectedOutcome: RED диагностирует отсутствие поведения, GREEN проходит той же Make-командой.


Skill: laravel-testing
applyAt: red, green, verification
purpose: Проверить исходящее сообщение и HTML мастерской существующими Pest-сценариями.
expectedOutcome: Русские test() проверяют наблюдаемый результат без живой сети.


Skill: russian-editor
applyAt: design, green
purpose: Сохранить согласованный русский текст приветствия и профильной документации.
expectedOutcome: Текст краткий, грамотный и соответствует поведению.


## TASK-005 — Регрессия и визуальная QA

Stage: 004
Objective: Завершённое поведение и непосредственно затронутые карточки проверены локально.
Role: qa_integration
Mode: orchestrator
Verification: inspection

dependsOn: TASK-004

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-002, REQ-003, REQ-004

acceptanceIds: AC-004, AC-005, AC-006, AC-007


CHECK-006 | inspection
Command: make test-suite suite=Telegram
Expected: Все Telegram-сценарии проходят, включая повторы /start, 429, календарь и утренние карточки.


CHECK-007 | inspection
Command: Проверить в локальной мастерской приветствие на ширинах 200, 320 и 440 px, высоту CTA, отсутствие обрезания, переход в /app и ошибки браузера.
Expected: Карточка помещается, CTA имеет высоту не менее 44 px и открывает локальный редактор; ошибок браузера нет.


Skill: ponytail:ponytail
applyAt: verification
purpose: Использовать существующие границы и минимальный полный объём изменения.
expectedOutcome: Нет новых хранилищ, зависимостей или дублирования оформления.


Skill: webapp-testing
applyAt: verification
purpose: Проверить реальное локальное отображение карточки и переход в редактор.
expectedOutcome: При заданных ширинах нет обрезания и ошибок браузера.


## TASK-006 — Внешняя приёмка запуска Mini App

Stage: 007
Objective: Авторизованный пользователь подтвердил реальное открытие редактора из нового виджета в Telegram.
Role: release_acceptance
Mode: orchestrator
Verification: inspection

dependsOn: TASK-004, TASK-005

ownedPaths: none

createPaths: none

deletePaths: none

requirementIds: REQ-004

acceptanceIds: AC-008


CHECK-008 | inspection
Command: В клиенте Telegram открыть новый приветственный виджет, нажать «Создать календарь» и подтвердить запуск существующего редактора с Telegram-авторизацией.
Expected: Главная Mini App направляет на существующий /app; новый виджет открывает авторизованный редактор.


Skill: ponytail:ponytail
applyAt: handoff
purpose: Использовать существующие границы и минимальный полный объём изменения.
expectedOutcome: Нет новых хранилищ, зависимостей или дублирования оформления.
