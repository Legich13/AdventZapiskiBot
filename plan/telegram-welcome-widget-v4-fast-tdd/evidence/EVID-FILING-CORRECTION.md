# EVID-FILING-CORRECTION

Stage: 003
Task: none
Check: none
Command: Сверить исходные хеши двух отклонённых вспомогательных записей и сохранить точную историю коррекции этапа.
Expected: Зафиксирован результат проверки.
Observed: На начальном этапе параллельно получены два дополнительных отчёта. Агрегат отказался считать их исполнением будущего этапа 004. Исправлена привязка именно этих записей к фактическому этапу 002. Первоначальные Markdown сохранены целиком в начале каждого файла; выписанные длина и хеш воспроизводят точные исходные байты. Далее следуют исправления учётной классификации. Тестовые результаты и их время совпадают с исходными. Исправление не добавляет успешный GREEN, общую проверку или мнение ревьюера к отозванному плану.

Исходные поля и новые контрольные суммы

```json
[
  {
    "originalBytes": 995,
    "originalStatusRecord": {
      "id": "EVID-QA-OPERATIONS-DOCS",
      "stage": "004",
      "taskId": null,
      "checkId": null,
      "kind": "gate",
      "path": "plan/telegram-welcome-widget-v4-fast-tdd/evidence/EVID-QA-OPERATIONS-DOCS.md",
      "sha256": "012d201b23f2cbd7dd256857b37d853c79a3cda2f08cd6a894ae3b689c347e8f",
      "command": "make dev-docs-check",
      "exitCode": 0,
      "observedAt": "2026-11-27T08:36:40Z",
      "gateId": null,
      "verifiedManifestSha256": null
    },
    "correctedSha256": "0c0325e820e572a66823264ae2a0397028e2eb12c1a77b420b53dff9242000fe"
  },
  {
    "originalBytes": 1936,
    "originalStatusRecord": {
      "id": "EVID-QA-PRIOR-INTERFACE",
      "stage": "004",
      "taskId": null,
      "checkId": null,
      "kind": "gate",
      "path": "plan/telegram-welcome-widget-v4-fast-tdd/evidence/EVID-QA-PRIOR-INTERFACE.md",
      "sha256": "87bf080ee51637b6942541eb09b3c1098fc48229f527e8b2741403b26666af69",
      "command": "Сопоставить SHA-256 восьми браузерных файлов со снимком v3 и прочитать исходные результаты мобильной, iframe-проверки и восстановления 419.",
      "exitCode": 0,
      "observedAt": "2026-11-27T08:36:51Z",
      "gateId": null,
      "verifiedManifestSha256": null
    },
    "correctedSha256": "f72907b68921245be091e18588b69807c6a621ae0c3bdcf34a104304fb4e6784"
  }
]
```
Exit code: 0
Redacted: Приведён итог без токенов, идентификаторов пользователей, текстов личных записок и сырого вывода.
Limitations: Проверена указанная граница в рабочей копии и локальном окружении.
Gate: none
Verified manifest SHA-256: none
