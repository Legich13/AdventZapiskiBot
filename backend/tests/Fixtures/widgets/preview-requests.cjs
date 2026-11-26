const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

async function check() {
    const window = {};
    let requests = 0;
    let settle;
    const fetch = () => {
        requests++;
        return new Promise(resolve => { settle = resolve; });
    };
    vm.runInNewContext(fs.readFileSync(0, 'utf8').match(/<script>([\s\S]*?)<\/script>/)[1], {window, fetch});
    const api = window.TelegramWidget;
    const first = api.request('state');
    const second = api.request('open:7');
    assert.equal(requests, 1, 'the preview sent two simultaneous HTTP requests');
    await assert.rejects(second, error => error.code === 'BUSY');
    settle({ok: false});
    await assert.rejects(first);
    const third = api.request('open:7');
    settle({ok: true, json: async () => 'ok'});
    assert.equal((await third).result, 'ok');
}

check().catch(error => {console.error(error); process.exitCode = 1;});
