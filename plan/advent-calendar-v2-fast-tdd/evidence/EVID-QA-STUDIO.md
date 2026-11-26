# EVID-QA-STUDIO

Stage: 004
Task: TASK-007
Check: CHECK-007
Command: make test-suite suite='StudioBoundaryTest|DocumentationAccessTest'
Expected: RED обнаруживает доступ к БД из панели и отсутствующий справочник. GREEN подтверждает границы и защиту справки.
Observed: После фактического выдачи и отзыва страниц в форме ролей Shield каноническая пара StudioBoundaryTest|DocumentationAccessTest прошла 6 тестов и 66 утверждений. В отдельном цикле make test-suite suite=DocumentationAccess сначала выявил запрет выданной страницы, затем недостающую команду назначения существующей роли. Итог 5/54. Тест схемы не находил 422, после исправления прошёл (1/16). На независимом PG18 стенде Chromium 390 px прошёл создание январского подарка, сохранение после reload, /start, открытие перед 10:00 и перечитывание при одной доставке/одном открытии. Отдельно прошли оба декабрьских сценария. Локальные часы: целевой make test-suite suite='StudioTest.*часы мастерской' падал (системные часы показывали 2032-02-29), после использования GiftClock прошёл с 3 утверждениями. Экспорт Scramble и проверка доступа к справочнику на PostgreSQL 18 прошли. В процессе исправления контрактов и доступа возникли дополнительные исходные имена вне исходного закрытого списка путей. Хеши приложены и входят в тот же проверяемый bundle.

313a5e9cd374499abd05994bcb2d46bf86d38072b0bce8015c3484a9932cc527  .mcp.json
fb8f7c972fc199aa6c53b34530b8211529099692da5d84a1c43f246e7700b0d9  backend/app/Admin/Actions/AssignStaffRoleAction.php
e867bdd7760937b570ab906a30846dddb01a561861b0208b01df3ce4ab5ff6b4  backend/app/Admin/Infrastructure/Persistence/StaffAccessRepository.php
0ff83e4c80eb7414a79a7bcd1247580e196c16ad3d7fea69392433ba4f0d9e9a  backend/app/Admin/StaffPermission.php
9b970395c4ca217bc51650c425f966881d2d5f3a3e250c20a43253056692d8ed  backend/app/Calendar/DTO/CalendarWriteResultDTO.php
be46cb5d6646531857ebb7be7ebdcaa19524351457d3d23b189758d1a5d30162  backend/app/Console/Commands/AssignStaffRole.php
2f7879def5e5d48229833910cd3f7726a3987223e123015cd19cf28729debf90  backend/app/Support/GiftClock.php
0408f813ff4140f7facea3ff71d2dbdbc43a1538b0a99038474ee1395d9b50b0  backend/database/migrations/2026_01_04_000001_initialize_staff_page_permissions.php
Exit code: 0
Redacted: Только итог наблюдения; данные учётных записей и личные сообщения исключены.
Limitations: Локальная среда. Внешний Telegram в этой проверке не участвовал.
Gate: none
Verified manifest SHA-256: none
