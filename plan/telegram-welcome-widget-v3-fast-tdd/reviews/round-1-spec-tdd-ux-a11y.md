# Spec / TDD / UX / a11y

Round: 1
Reviewer: /root/review_ux
Snapshot SHA-256: 465a0a31c0fe82fc074ba753cc426751b9ec46b95a0bd5b4439a75d4c1590429
Manifest SHA-256: 24e7aa4e29f24896f5e12c60ebf0499d3e877dabed68ecc3b71da86cf48ca0a3
Verdict: fix_required
B0 / B1 / B2: 0/1/0
Skills: ponytail:ponytail, ponytail:ponytail-review

Один B1. При частично исполненном старым ботом /start уже имеется позиция 0, но отсутствует часть позиций вручения и нет отметки cover_pending_on. Независимое чтение PostgreSQL подтвердило, что запланированное утреннее сообщение сможет опередить открытку ещё до первого повтора webhook. Остальная часть UX соответствует исправлениям: фактический iframe-вход, таймер доступности, reduced motion, кнопка-коробка, выделение 31 декабря и восстановление после 419.
