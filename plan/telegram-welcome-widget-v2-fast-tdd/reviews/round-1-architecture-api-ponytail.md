# Architecture / API / Ponytail

Round: 1
Reviewer: /root/review_arch
Snapshot SHA-256: 82a183eb213ef7f818e180392fbf8f29b648b9bc745fe31082ad3d3834f1d8a9
Manifest SHA-256: 02ace9234abc1da9d574613a97bad8d286f48d9936dde9e23c4e3531c4eff607
Verdict: fix_required
B0 / B1 / B2: 0/3/2
Skills: ponytail:ponytail, ponytail:ponytail-review

Три B1. При переходе между реализациями /start журнал позиций 0 и 1..N теряет контекст частично отправленного ответа и совместимость с откатом. Отправка Telegram может начаться внутри внешней незавершённой транзакции. Локальный сбой открытия PNG расходует резерв отправки без сетевой попытки. Два B2. Порядок ресурсов и проверки будущей даты даёт разные 403/404 в связанных интерфейсах. Выборка всех authored для проверки, есть ли календарь, избыточна.

B2 rationale: Оба малых замечания приняты для проработки вместе с B1 до следующего снимка.
B2 owner: /root/app и /root/bot
