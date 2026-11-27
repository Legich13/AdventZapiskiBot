# Architecture / API / Ponytail

Round: 1
Reviewer: /root/review_arch
Snapshot SHA-256: 465a0a31c0fe82fc074ba753cc426751b9ec46b95a0bd5b4439a75d4c1590429
Manifest SHA-256: 24e7aa4e29f24896f5e12c60ebf0499d3e877dabed68ecc3b71da86cf48ca0a3
Verdict: fix_required
B0 / B1 / B2: 0/2/1
Skills: ponytail:ponytail, ponytail:ponytail-review

Два B1. Начатый старой версией /start с частью уже зарезервированных позиций и поставленной в очередь утренней задачей имеет NULL cover_pending_on. Воркер может отправить утреннее сообщение раньше восстановленной открытки. Подтверждённый отказ Telegram (403 или иная явная ошибка) и неопределённая потеря ответа при вручении оставляют одинаковый результат в журнале. B2: пересмотр уже применённой миграции 000001 не обновляет существующую локальную схему обычным запуском migrate.

B2 rationale: Принят штатный аддитивный переход для ранее созданных локальных БД в следующую ревизию исправления.
B2 owner: /root/bot и /root
