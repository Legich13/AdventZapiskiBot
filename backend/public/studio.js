const csrf = document.querySelector('meta[name="csrf-token"]').content;
async function request(url, method, body) {
    const response = await fetch(url, {
        method,
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": csrf,
        },
        body: JSON.stringify(body),
    });
    if (!response.ok) {
        const problem = await response.json().catch(() => ({}));
        throw new Error(
            Object.values(problem.errors || {}).flat()[0] ||
                problem.message ||
                "Не удалось сохранить. Попробуйте ещё раз.",
        );
    }
    return response.status === 204 ? null : response.json();
}

const signin = document.getElementById("signin");
if (signin && window.Telegram?.WebApp?.initData) {
    window.Telegram.WebApp.ready();
    signin.textContent = "Открываем ваш календарь…";
    request("/app/session", "POST", {
        initData: window.Telegram.WebApp.initData,
    })
        .then(() => location.reload())
        .catch(() => {
            signin.textContent =
                "Время входа истекло. Откройте редактор ещё раз из чата с ботом.";
        });
}

const editor = document.getElementById("editor");
if (editor) setupEditor(editor);

function setupEditor(form) {
    const initial = JSON.parse(document.getElementById("initial").textContent);
    let calendarId = initial.id;
    const notes = new Map(Object.entries(initial.notes));
    const month = document.getElementById("month");
    const dates = document.getElementById("dates");
    const fields = document.getElementById("notes");
    const feedback = document.getElementById("feedback");
    const dateFormat = new Intl.DateTimeFormat("ru", {
        day: "numeric",
        month: "long",
        timeZone: "UTC",
    });
    const target = () => form.querySelector("[name=target]:checked").value;
    function updateAddress() {
        document.getElementById("address").hidden = target() === "self";
    }
    function selected() {
        return [...notes]
            .filter(([day]) => day.startsWith(month.value + "-"))
            .sort(([a], [b]) => a.localeCompare(b));
    }
    function bytes(text) {
        // The longest valid success envelope includes "first": false.
        return new TextEncoder().encode(
            JSON.stringify({ text, first: false })
                .replace(/\u2028/g, "\\u2028")
                .replace(/\u2029/g, "\\u2029"),
        ).length;
    }
    function updatePublish() {
        const publish = form.querySelector("button[value=publish]");
        const days = selected();
        if (publish)
            publish.disabled =
                days.length === 0 ||
                days.some(([, text]) => !text.trim() || bytes(text) > 1024);
    }
    function draw() {
        dates.replaceChildren();
        fields.replaceChildren();
        if (!month.value) return;
        const [year, number] = month.value.split("-").map(Number);
        const count = new Date(Date.UTC(year, number, 0)).getUTCDate();
        for (let day = 1; day <= count; day++) {
            const date = month.value + "-" + String(day).padStart(2, "0");
            const tile = document.createElement("button");
            tile.type = "button";
            tile.textContent = day;
            tile.setAttribute("aria-label", dateFormat.format(new Date(date)));
            tile.setAttribute("aria-pressed", String(notes.has(date)));
            tile.disabled = initial.published;
            tile.onclick = () => {
                if (notes.has(date)) {
                    if (
                        notes.get(date).trim() &&
                        !window.confirm("Убрать этот день вместе с запиской?")
                    )
                        return;
                    notes.delete(date);
                } else {
                    notes.set(date, "");
                }
                draw();
                (
                    document.getElementById("note-" + date) ||
                    dates.children[day - 1]
                ).focus();
            };
            dates.append(tile);
        }
        for (const [date, text] of selected()) {
            const entry = document.createElement("div");
            entry.className = "date-note";
            const label = document.createElement("label");
            const input = document.createElement("textarea");
            const counter = document.createElement("small");
            input.id = "note-" + date;
            label.htmlFor = input.id;
            label.textContent = dateFormat.format(new Date(date));
            input.value = text;
            input.readOnly = initial.published && date <= initial.today;
            input.placeholder = "Что ждёт получателя в этот день?";
            counter.id = input.id + "-count";
            input.setAttribute("aria-describedby", counter.id);
            function countBytes() {
                const remaining = 1024 - bytes(input.value);
                counter.textContent =
                    remaining >= 0
                        ? "Осталось " + remaining + " байт"
                        : "Превышение на " + -remaining + " байт";
                counter.classList.toggle("over", remaining < 0);
            }
            input.oninput = () => {
                notes.set(date, input.value);
                countBytes();
                updatePublish();
            };
            countBytes();
            entry.append(label, input, counter);
            fields.append(entry);
        }
        updatePublish();
    }
    form.querySelectorAll("[name=target]").forEach(
        (option) => (option.onchange = updateAddress),
    );
    month.onchange = draw;
    updateAddress();
    draw();
    let saving = false;
    form.onsubmit = async (event) => {
        event.preventDefault();
        if (saving) return;
        saving = true;
        const publish = event.submitter?.value === "publish";
        feedback.hidden = false;
        feedback.textContent = "Сохраняем…";
        try {
            const payload = {
                target: target(),
                username: document.getElementById("username").value,
                month: month.value,
                days: selected().map(([date, text]) => ({ date, text })),
            };
            const result = await request(
                calendarId ? "/app/calendars/" + calendarId : "/app/calendars",
                calendarId ? "PUT" : "POST",
                payload,
            );
            calendarId = result.id;
            history.replaceState(null, "", "/app?calendar=" + calendarId);
            if (publish)
                await request(
                    "/app/calendars/" + calendarId + "/publish",
                    "POST",
                    {},
                );
            location.reload();
        } catch (error) {
            feedback.textContent = error.message;
        } finally {
            saving = false;
        }
    };
}
