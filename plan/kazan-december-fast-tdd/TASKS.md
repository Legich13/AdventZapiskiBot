# Tasks

## TASK-001 · Состав декабрьской подборки

Миграция создаёт 31 записку однократно и атомарно только в production.

000 · business_system · read_only_subagent · inspection

REQ-001, AC-001

CHECK-001 · Сопоставить дни переписки и афиши организаторов.

ponytail:ponytail · analysis · Определить изменения в рамках существующих модулей. · Изменение использует существующую схему данных и интерфейс.

## TASK-002 · Модель вручения

Первый /start адресата вручает подарок по ID, открывает доступ и допускает утренние карточки.

000 · architecture_codebase · read_only_subagent · inspection

REQ-002, AC-002

CHECK-002 · Проверить поля Calendar, обработчик /start и выборку для рассылки.

ponytail:ponytail · analysis · Определить изменения в рамках существующих модулей. · Изменение использует существующую схему данных и интерфейс.

## TASK-003 · Повторы и конфликты

Миграция создаёт 31 записку однократно и атомарно только в production.

000 · qa_risk · read_only_subagent · inspection

REQ-001, AC-001

CHECK-003 · Проверить уникальность календаря и сохранность профилей в PostgreSQL.

ponytail:ponytail · analysis · Определить изменения в рамках существующих модулей. · Изменение использует существующую схему данных и интерфейс.

## TASK-004 · Публикация и 31 записка

Миграция создаёт 31 записку однократно и атомарно только в production.

002 · migration · writer · tdd

TASK-001, TASK-002, TASK-003, REQ-001, AC-001

- backend/database/data/kazan_december_2026.php
- backend/database/migrations/2026_11_26_000001_publish_kazan_december_calendar.php
- backend/tests/Feature/KazanDataMigrationTest.php

CHECK-004 · make test-suite suite=KazanDataMigration

ponytail:ponytail · verification · Проверить результат через обычную точку входа. · Изменение использует существующую схему данных и интерфейс.

## TASK-005 · Вручение и права редактирования

Первый /start адресата вручает подарок по ID, открывает доступ и допускает утренние карточки.

002 · runtime · writer · tdd

TASK-001, TASK-002, TASK-003, REQ-002, AC-002

- backend/app/Calendar/Actions/ClaimCalendarsAction.php
- backend/app/Calendar/Actions/SaveCalendarAction.php
- backend/app/Calendar/Contracts/CalendarRepository.php
- backend/app/Calendar/Infrastructure/Persistence/PostgresCalendarRepository.php
- backend/app/Models/Calendar.php
- backend/tests/Feature/TelegramReservedCalendarTest.php

CHECK-005 · make test-suite suite=TelegramReservedCalendarTest

ponytail:ponytail · verification · Проверить результат через обычную точку входа. · Изменение использует существующую схему данных и интерфейс.

## TASK-006 · Поле получателя и справочник

Редактор фиксирует адресата до вручения и позволяет исправить будущую записку.

002 · editor · orchestrator · tdd

TASK-001, TASK-002, TASK-003, REQ-003, AC-003

- README.md
- backend/resources/views/app.blade.php
- backend/tests/Feature/StudioTest.php
- docs/backend/calendar.md
- docs/flows/publish-calendar.md

CHECK-006 · make test-suite suite=StudioTest

ponytail:ponytail · verification · Проверить результат через обычную точку входа. · Изменение использует существующую схему данных и интерфейс.
