# Tasks

## TASK-001 · Бизнес-сценарии

Публикация себе с сегодняшним подарком с 10:00 сразу запускает доставку после коммита.

000 · business_system · read_only_subagent · inspection

REQ-001, AC-001



Create: none
Delete: none

CHECK-001 · Проверить бизнес-сценарии публикации себе и позднего вручения календаря.

Независимый анализ подтверждает согласованное поведение и указывает проверяемые риски.

ponytail:ponytail · analysis · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.

russian-editor · analysis · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.

## TASK-002 · Архитектура и данные

Поздний /start вручает календарь и сразу запускает сегодняшнюю доставку по нику или закреплённому ID.

000 · architecture_codebase · read_only_subagent · inspection

REQ-002, AC-002



Create: none
Delete: none

CHECK-002 · Проверить транзакции, scoped dispatcher, очередь afterCommit и потребителей.

Независимый анализ подтверждает согласованное поведение и указывает проверяемые риски.

ponytail:ponytail · analysis · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.

## TASK-003 · ТДД и риски

Одна карточка на получателя и день; границы времени, rollback, scheduler и локальные часы сохраняют корректность.

000 · qa_risk · read_only_subagent · inspection

REQ-003, AC-003



Create: none
Delete: none

CHECK-003 · Проверить поведенческие тесты, реальную очередь, rollback, гонки и виртуальные часы.

Независимый анализ подтверждает согласованное поведение и указывает проверяемые риски.

ponytail:ponytail · analysis · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.

tdd · analysis · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.

laravel:tdd-with-pest · analysis · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.

## TASK-004 · Сегодняшняя доставка без ожидания планировщика

Оба публичных пути запускают одну сегодняшнюю доставку после коммита; мастерская воспроизводит их.

002 · runtime · orchestrator · tdd

TASK-001, TASK-002, TASK-003, REQ-001, REQ-002, REQ-003, AC-001, AC-002, AC-003

- README.md
- backend/app/Calendar/Actions/PublishCalendarAction.php
- backend/app/Studio/Actions/StartPreviewChatAction.php
- backend/app/Telegram/Actions/DispatchMorningRemindersAction.php
- backend/app/Telegram/Actions/PrepareStartUpdateAction.php
- backend/app/Telegram/Contracts/MorningDeliveryRepository.php
- backend/app/Telegram/Infrastructure/Persistence/PostgresMorningDeliveryRepository.php
- backend/tests/Feature/TelegramReservedCalendarTest.php
- backend/tests/Feature/TelegramStartReplayTest.php
- backend/tests/Feature/TelegramTodayDeliveryTest.php
- backend/tests/Feature/TelegramWidgetTest.php
- docs/INFRASTRUCTURE.md
- docs/backend/model-business-evidence.md
- docs/backend/telegram.md
- docs/contracts/openapi.json
- docs/flows/morning-delivery.md
- docs/flows/publish-calendar.md
- docs/flows/staff-support.md

Create: backend/tests/Feature/TelegramTodayDeliveryTest.php
Delete: none

CHECK-004 · make test-suite suite=TelegramTodayDeliveryTest

RED: отсутствует сегодняшнее задание после публикации/вручения; GREEN: реальные задания и воркер выполняют все согласованные сценарии.

ponytail:ponytail · design, red, green, verification · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.

tdd · red, green, verification · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.

laravel:tdd-with-pest · red, green, verification · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.

russian-editor · verification · Проверить согласованное поведение через существующие границы. · Минимальное изменение с поведенческим доказательством без дублирования отправки.
