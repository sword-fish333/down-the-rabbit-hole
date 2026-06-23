/* Down the Rabbit Hole — the descent.
   Streams the guide's turn over SSE and reveals the right control when it lands.
   Vanilla, no build step. Pairs with resources/views/frontend/chat/show.blade.php. */
(function () {
    'use strict';

    var cfg = document.querySelector('[data-chat]');
    if (!cfg) return;

    var maxDepth = parseInt(cfg.getAttribute('data-max-depth'), 10) || 1;

    var streamRow = document.getElementById('dth-stream-row');
    var streamEl  = document.getElementById('dth-stream');
    var cursor    = document.getElementById('dth-cursor');
    var thinking  = document.getElementById('dth-thinking');
    var errorEl   = document.getElementById('dth-error');

    var controls = {
        checkpoint_pending: document.getElementById('dth-control-proof'),
        exploring:          document.getElementById('dth-control-deeper'),
        surfaced:           document.getElementById('dth-control-surfaced')
    };

    function show(el, display) { if (el) el.style.display = display; }
    function hide(el) { if (el) el.style.display = 'none'; }

    function setDepth(depth) {
        if (window.DTH) window.DTH.setDepth(depth / maxDepth);
    }

    /* Reveal exactly one control for the conversation's state. */
    function reveal(status, surfaced) {
        hide(controls.checkpoint_pending);
        hide(controls.exploring);
        hide(controls.surfaced);
        hide(thinking);

        if (surfaced || status === 'surfaced') {
            show(controls.surfaced, 'flex');
        } else if (status === 'checkpoint_pending') {
            show(controls.checkpoint_pending, 'block');
            var ta = document.getElementById('dth-proof');
            if (ta) ta.focus();
        } else {
            show(controls.exploring, 'block');
        }
    }

    function fail(message) {
        hide(cursor);
        hide(thinking);
        if (errorEl) { errorEl.textContent = message; show(errorEl, 'block'); }
    }

    /* --- SSE over fetch ----------------------------------------------------- */
    function startStream(url) {
        hide(controls.checkpoint_pending);
        hide(controls.exploring);
        hide(controls.surfaced);
        hide(errorEl);
        show(thinking, 'flex');
        show(streamRow, 'flex');
        show(cursor, 'inline-block');
        streamEl.textContent = '';

        var full = '';

        function onEvent(name, data) {
            if (name === 'token') {
                full += (data.text || '');
                // The guide ends with a hidden ```json control block — never show it.
                streamEl.textContent = full.split('```json')[0];
                hide(thinking);
            } else if (name === 'done') {
                hide(cursor);
                setDepth(data.depth);
                reveal(data.status, data.surfaced);
            } else if (name === 'error') {
                fail(data.message || 'Something went wrong.');
            }
        }

        fetch(url, { headers: { 'Accept': 'text/event-stream' }, credentials: 'same-origin' })
            .then(function (res) {
                if (!res.ok || !res.body) throw new Error('stream failed');
                var reader = res.body.getReader();
                var decoder = new TextDecoder();
                var buffer = '';

                function pump() {
                    return reader.read().then(function (chunk) {
                        if (chunk.done) return;
                        buffer += decoder.decode(chunk.value, { stream: true });

                        var blocks = buffer.split('\n\n');
                        buffer = blocks.pop();
                        blocks.forEach(function (block) {
                            var name = 'message', data = '';
                            block.split('\n').forEach(function (line) {
                                if (line.indexOf('event:') === 0) name = line.slice(6).trim();
                                else if (line.indexOf('data:') === 0) data += line.slice(5).trim();
                            });
                            if (!data) return;
                            try { onEvent(name, JSON.parse(data)); } catch (e) { /* ignore malformed frame */ }
                        });
                        return pump();
                    });
                }
                return pump();
            })
            .catch(function () { fail('The connection dropped. Refresh to continue.'); });
    }

    /* --- Boot --------------------------------------------------------------- */
    setDepth(parseInt(cfg.getAttribute('data-depth'), 10) || 0);

    if (cfg.getAttribute('data-autostream') === '1') {
        startStream(cfg.getAttribute('data-stream-url'));
    } else {
        reveal(cfg.getAttribute('data-status'), false);
    }
})();
