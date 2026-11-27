# Security / Privacy / Data / PostgreSQL

Round: 1
Reviewer: /root/review_privacy
Snapshot SHA-256: 82a183eb213ef7f818e180392fbf8f29b648b9bc745fe31082ad3d3834f1d8a9
Manifest SHA-256: 02ace9234abc1da9d574613a97bad8d286f48d9936dde9e23c4e3531c4eff607
Verdict: fix_required
B0 / B1 / B2: 0/3/0
Skills: ponytail:ponytail, ponytail:ponytail-review

Три B1. В двух WebView с одной cookie отложенный POST прежнего экрана может выполниться в новой сессии. Политика SameSite=Lax мешает отправить сессию из Telegram Web во внешнем iframe. При переходе с прежнего /start после 429 на позиции вручения уже занятая позиция 0 может оставить получателя без обещанного ответа. Недостатки затрагивают изоляцию адресата и воспроизводимость доставки.
