<meta name="viewport" content="width=device-width,initial-scale=1">
<script>
let requestPending = false;
window.TelegramWidget = {
    ready() {},
    onEvent() {},
    async request(command) {
        if (requestPending) throw Object.assign(new Error('Запрос выполняется'), {code: 'BUSY'});
        requestPending = true;
        try {
            const response = await fetch(@js(url('/dev/widget/'.$message.'/query')), {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @js(csrf_token())},
                body: JSON.stringify({command})
            });
            if (!response.ok) throw new Error('Ошибка запроса');
            return { result: await response.json() };
        } finally {
            requestPending = false;
        }
    }
};
</script>
{!! $html !!}
