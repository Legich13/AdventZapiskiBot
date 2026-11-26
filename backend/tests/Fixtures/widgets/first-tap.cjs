// Executes the rendered widget with the v1 SDK's single-request and tap rules.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

class Element extends EventTarget {
    children = [];
    attributes = new Set();
    style = {};
    set textContent(value) { this.text = value; this.children = []; }
    get textContent() { return this.text || ''; }
    appendChild(child) { this.children.push(child); }
    setAttribute(name) { this.attributes.add(name); }
    removeAttribute(name) { this.attributes.delete(name); }
    get disabled() { return this.attributes.has('disabled'); }
}

async function check(html) {
    const nodes = new Map(Array.from(html.matchAll(/\bid="([^"]+)"/g), match => [match[1], new Element()]));
    const byId = id => nodes.get(id);
    const day = () => byId('unwrap') || byId('days').children[6];
    const commands = [];
    let pendingState;
    let inFlight = false;
    let gesture = false;
    function tap(button) {
        if (button.disabled) return;
        gesture = true;
        button.dispatchEvent(new Event('click'));
        gesture = false;
    }
    const api = {
        ready() {},
        request(command) {
            if (inFlight) return Promise.reject(new Error('BUSY'));
            commands.push(command);
            if (command === 'state') {
                inFlight = true;
                return new Promise((resolve, reject) => {
                    pendingState = () => {
                        inFlight = false;
                        if (process.argv[2] === 'failure') reject(new Error('TIMEOUT'));
                        else resolve({result: JSON.stringify({month: '2026-12', total: 31, days: {7: 1}})});
                    };
                });
            }
            assert.ok(gesture, 'opening must use the actual tap, not a background quota');
            return Promise.resolve({result: JSON.stringify({first: true, text: 'Твой подарок'})});
        },
    };

    const source = html.match(/<script>([\s\S]*?)<\/script>/)[1];
    vm.runInNewContext(source, {
        document: {getElementById: byId, createElement: () => new Element()},
        window: {TelegramWidget: api},
    });
    tap(day());
    await new Promise(setImmediate);
    assert.ok(day().disabled, 'the opening button was enabled while the state request was still pending');
    assert.match(byId('notice').textContent, /обнов|загру/i);
    pendingState();
    await new Promise(setImmediate);
    assert.ok(!day().disabled, 'the first tap must be available after any state result');

    tap(day());
    tap(day());
    await new Promise(setImmediate);
    assert.deepEqual(commands, ['state', 'open:7']);
    assert.equal(byId('letter').textContent, 'Твой подарок');
}

check(fs.readFileSync(0, 'utf8')).catch(error => {
    console.error(error);
    process.exitCode = 1;
});
