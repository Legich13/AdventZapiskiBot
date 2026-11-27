(async () => {
    const telegram = window.Telegram?.WebApp;
    const status = document.getElementById("signin");
    const retry = document.getElementById("retry-login");
    retry.onclick = () => location.reload();
    if (!telegram?.initData) return;
    telegram.ready();

    async function login() {
        const response = await fetch("/app/session", {
            method: "POST",
            headers: {
                Accept: "application/json",
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({initData: telegram.initData}),
        });
        if (!response.ok) throw new Error("Вход не удался. Откройте приложение заново из чата с ботом.");
    }

    try {
        status.textContent = "Открываем календарь…";
        const digest = await crypto.subtle.digest("SHA-256", new TextEncoder().encode(telegram.initData));
        const proof = Array.from(new Uint8Array(digest), byte => byte.toString(16).padStart(2, "0")).join("");
        const load = () => fetch(location.pathname + location.search, {
            cache: "no-store",
            headers: {"X-Mini-App-Launch": proof},
        });
        let response = await load();
        if (response.status === 401) {
            await login();
            response = await load();
        }
        if (response.status === 403 || response.status === 404) {
            status.textContent = "Этот календарь или подарок пока недоступен.";
            const home = document.createElement("a");
            home.href = "/app";
            home.textContent = "Мои календари";
            status.after(home);
            return;
        }
        if (response.status === 401 || response.status === 419)
            throw new Error("Не удалось подтвердить вход. Закройте приложение и откройте его заново из чата с ботом.");
        if (!response.ok) throw new Error("Не удалось загрузить календарь.");
        const page = new DOMParser().parseFromString(await response.text(), "text/html");
        const content = page.querySelector(".shell");
        if (!content) throw new Error("Не удалось загрузить календарь.");
        document.querySelector('meta[name="csrf-token"]').content = page.querySelector('meta[name="csrf-token"]').content;
        document.querySelector(".shell").replaceWith(content);
        document.title = page.title;
        const script = document.createElement("script");
        script.launchProof = proof;
        script.src = "/studio.js";
        document.body.append(script);
        // При восстановлении истории браузером снова проверяем текущий аккаунт.
        window.addEventListener("pagehide", () => { content.hidden = true; });
        window.addEventListener("pageshow", (event) => { if (event.persisted) location.reload(); });
    } catch (error) {
        status.textContent = error.message || "Проверьте соединение и повторите попытку.";
        retry.hidden = false;
    }
})();
