/* Down the Rabbit Hole — the learning workspace.

   Two transports, because the two jobs are different:
     GET  /hole/{id}/stream      SSE, prose, rendered as it arrives
     POST /hole/{id}/checkpoint  JSON, a structured verdict rendered in place

   Rules this file exists to enforce, all of them about not breaking
   concentration while someone is trying to think:
     - completed text NEVER reflows once it has been rendered;
     - the scroll position is the learner's, not ours — we only follow if they
       were already at the bottom;
     - exactly one control is offered at a time;
     - every state change is announced to assistive tech;
     - nothing animates while reading except the caret.

   Pairs with resources/views/frontend/chat/show.blade.php. */
(function () {
    'use strict';

    var config = document.querySelector('[data-chat]');
    if (!config) return;

    var md = window.DTHMarkdown;
    var maxDepth = parseInt(config.getAttribute('data-max-depth'), 10) || 1;
    var streamUrl = config.getAttribute('data-stream-url');
    var checkpointUrl = config.getAttribute('data-checkpoint-url');
    var csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content;
    var text = JSON.parse(config.getAttribute('data-strings') || '{}');

    var thread = document.getElementById('dth-thread');
    var rail = document.getElementById('dth-rail');
    var errorBox = document.getElementById('dth-error');
    var thinking = document.getElementById('dth-thinking');

    var controls = {
        checkpoint: document.getElementById('dth-control-checkpoint'),
        deeper: document.getElementById('dth-control-deeper'),
        surfaced: document.getElementById('dth-control-surfaced'),
    };

    var verdictPanel = document.getElementById('dth-verdict');
    var proofField = document.getElementById('dth-proof');
    var proofForm = document.getElementById('dth-proof-form');
    var busy = false;

    /* --- Small DOM helpers -------------------------------------------------- */
    function show(el) { if (el) el.hidden = false; }
    function hide(el) { if (el) el.hidden = true; }

    function announce(message) {
        if (window.DTH) window.DTH.announce(message);
    }

    /* --- Scroll ownership --------------------------------------------------- */
    /* Auto-scroll only when the learner was already near the bottom. Yanking
       the viewport away from a paragraph someone is mid-sentence in is the
       single most disruptive thing a streaming UI can do. */
    function nearBottom() {
        return window.innerHeight + window.scrollY >= document.body.offsetHeight - 220;
    }

    function follow(wasNearBottom) {
        if (wasNearBottom) window.scrollTo({ top: document.body.scrollHeight, behavior: 'auto' });
    }

    /* --- The depth rail ---------------------------------------------------- */
    function paintRail(depth, passed) {
        if (window.DTH) window.DTH.setDepth(depth / maxDepth);
        if (!rail) return;

        var nodes = rail.querySelectorAll('.dth-rail-node');

        nodes.forEach(function (node, index) {
            var state = index < depth ? 'cleared' : (index === depth ? 'current' : 'locked');
            /* An unresolved concept from this layer keeps it flagged for review
               even after it was cleared — that is the resurfacing signal. */
            if (node.dataset.review === '1' && state === 'cleared') state = 'review';
            node.setAttribute('data-state', state);
        });

        /* Semantic light trail: cleared layer → newly opened layer. Explains
           causality, then removes itself. One-shot, never a loop. */
        if (passed && depth > 0 && !window.DTH.prefersReducedMotion()) {
            var from = nodes[depth - 1];
            var to = nodes[depth];
            if (!from || !to) return;

            var trail = document.createElement('span');
            trail.className = 'dth-rail-trail';
            trail.style.top = (from.offsetTop + from.offsetHeight / 2) + 'px';
            trail.style.height = Math.max(2, to.offsetTop - from.offsetTop) + 'px';
            rail.appendChild(trail);
            trail.addEventListener('animationend', function () { trail.remove(); });
        }
    }

    /* --- Controls ---------------------------------------------------------- */
    /* Exactly one is ever visible. */
    function reveal(state) {
        hide(controls.checkpoint);
        hide(controls.deeper);
        hide(controls.surfaced);
        hide(thinking);

        if (state.surfaced || state.status === 'surfaced') {
            show(controls.surfaced);
            announce(text.surfaced || '');
            return;
        }

        if (state.status === 'checkpoint_pending') {
            show(controls.checkpoint);
            if (proofField) proofField.focus({ preventScroll: true });
            announce(text.checkpointReady || '');
            return;
        }

        show(controls.deeper);
    }

    function fail(message) {
        hide(thinking);
        if (!errorBox) return;
        errorBox.textContent = message;
        show(errorBox);
        announce(message);
    }

    /* --- Streaming a teaching turn ----------------------------------------- */
    function startStream(reframe) {
        if (busy) return;
        busy = true;

        hide(errorBox);
        hide(controls.checkpoint);
        hide(controls.deeper);
        hide(verdictPanel);
        show(thinking);
        announce(text.thinking || '');
        if (window.DTH) window.DTH.setStudying(true);

        /* One turn element, created up front. Its height grows; nothing above
           it ever moves, so no cumulative layout shift. */
        var turn = document.createElement('article');
        turn.className = 'dth-turn measure mx-auto w-full';
        turn.setAttribute('aria-live', 'off');
        turn.innerHTML = '<div class="prose-dth" data-stream-body></div>';
        thread.appendChild(turn);

        var bodyEl = turn.querySelector('[data-stream-body]');
        var buffer = '';
        var pending = false;
        var stuck = nearBottom();

        /* Re-render at most once per frame: a token-per-render loop on a long
           turn is the difference between 60fps and a stuttering read. */
        function paint() {
            pending = false;
            bodyEl.innerHTML = md.render(buffer) + '<span class="dth-caret" aria-hidden="true"></span>';
            follow(stuck);
        }

        function schedule() {
            if (pending) return;
            pending = true;
            window.requestAnimationFrame(paint);
        }

        function onEvent(name, data) {
            if (name === 'token') {
                buffer += (data.text || '');
                hide(thinking);
                stuck = nearBottom() || stuck;
                schedule();
                return;
            }

            if (name === 'done') {
                busy = false;
                bodyEl.innerHTML = md.render(buffer);
                paintRail(data.depth, false);
                reveal(data);
                return;
            }

            if (name === 'error') {
                busy = false;
                if (!buffer) turn.remove();
                fail(data.message || text.genericError);
            }
        }

        var url = streamUrl + (reframe ? (streamUrl.indexOf('?') === -1 ? '?' : '&') + 'reframe=' + encodeURIComponent(reframe) : '');

        readEventStream(url, onEvent).catch(function () {
            busy = false;
            fail(text.connectionLost || '');
        });
    }

    /* SSE over fetch. EventSource can't send credentials-aware POSTs or custom
       headers, and we need the reader anyway to keep rendering incremental. */
    function readEventStream(url, onEvent) {
        return fetch(url, {
            headers: { Accept: 'text/event-stream' },
            credentials: 'same-origin',
        }).then(function (response) {
            if (!response.ok || !response.body) throw new Error('stream failed');

            var reader = response.body.getReader();
            var decoder = new TextDecoder();
            var buffered = '';

            function pump() {
                return reader.read().then(function (chunk) {
                    if (chunk.done) return;

                    buffered += decoder.decode(chunk.value, { stream: true });

                    var frames = buffered.split('\n\n');
                    buffered = frames.pop();

                    frames.forEach(function (frame) {
                        var name = 'message';
                        var payload = '';

                        frame.split('\n').forEach(function (line) {
                            if (line.indexOf('event:') === 0) name = line.slice(6).trim();
                            else if (line.indexOf('data:') === 0) payload += line.slice(5).trim();
                        });

                        if (!payload) return;
                        try { onEvent(name, JSON.parse(payload)); } catch (e) { /* skip malformed frame */ }
                    });

                    return pump();
                });
            }

            return pump();
        });
    }

    /* --- Submitting the proof ---------------------------------------------- */
    function submitProof(event) {
        event.preventDefault();
        if (busy) return;

        var answer = (proofField.value || '').trim();
        if (answer.length < 2) {
            proofField.focus();
            return;
        }

        busy = true;
        hide(errorBox);
        hide(verdictPanel);

        var submit = proofForm.querySelector('[type="submit"]');
        if (submit) { submit.disabled = true; submit.setAttribute('aria-busy', 'true'); }
        controls.checkpoint.classList.add('dth-analyzing');
        announce(text.analyzing || '');

        var confidence = proofForm.querySelector('[name="self_rating"]:checked');

        fetch(checkpointUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({
                message: answer,
                self_rating: confidence ? Number(confidence.value) : null,
            }),
        })
            .then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) throw new Error(data.message || text.genericError);
                    return data;
                });
            })
            .then(function (data) {
                appendLearnerTurn(answer);
                renderVerdict(data.attempt, data.state);
                paintRail(data.state.depth, data.state.passed);
                reveal(data.state);
                proofField.value = '';
                proofField.style.height = 'auto';
            })
            .catch(function (error) {
                fail(error.message || text.genericError);
            })
            .finally(function () {
                busy = false;
                controls.checkpoint.classList.remove('dth-analyzing');
                if (submit) { submit.disabled = false; submit.removeAttribute('aria-busy'); }
            });
    }

    /* The learner's answer joins the transcript, visually distinct from the
       guide's teaching so the two are never confused when scrolling back. */
    function appendLearnerTurn(answer) {
        var stuck = nearBottom();
        var turn = document.createElement('article');
        turn.className = 'dth-turn measure mx-auto w-full';
        turn.innerHTML =
            '<div class="rounded-2xl border border-primary/25 bg-primary/8 px-4 py-3">' +
            '<p class="dth-coord mb-1.5">' + md.escape(text.yourAnswer || '') + '</p>' +
            '<div class="prose-dth text-[0.95rem]">' + md.render(answer) + '</div>' +
            '</div>';
        thread.appendChild(turn);
        follow(stuck);
    }

    /* --- The verdict ------------------------------------------------------- */
    /* Criterion-by-criterion, with "incomplete" and "misconception detected"
       kept visually distinct — they call for different next moves, and
       conflating them is how a learner concludes they are simply bad at this. */
    function renderVerdict(attempt, state) {
        if (!verdictPanel) return;

        var passed = attempt.verdict === 'pass';
        var parts = [];

        parts.push(
            '<div class="flex items-start justify-between gap-4">' +
            '<p class="flex items-center gap-2 font-display text-sm font-semibold ' +
            (passed ? 'text-success' : 'text-accent') + '">' +
            '<span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">' +
            (passed ? 'check_circle' : (attempt.misconceptions.length ? 'error' : 'incomplete_circle')) +
            '</span>' +
            md.escape(passed ? text.verdictPass : (attempt.misconceptions.length ? text.verdictMisconception : text.verdictIncomplete)) +
            '</p>' +
            '<p class="dth-coord shrink-0">' + md.escape(text.scoreLabel || '') + ' ' + attempt.score + '/100</p>' +
            '</div>'
        );

        if (attempt.feedback) {
            parts.push('<div class="prose-dth mt-3 text-[0.95rem]">' + md.render(attempt.feedback) + '</div>');
        }

        if (attempt.criteria && attempt.criteria.length) {
            parts.push('<ul class="mt-4 space-y-2">' + attempt.criteria.map(function (criterion) {
                return '<li class="flex items-start gap-2 text-sm">' +
                    '<span class="material-symbols-outlined mt-px text-[1.05rem] ' +
                    (criterion.met ? 'text-success' : 'text-foreground-muted') + '" aria-hidden="true">' +
                    (criterion.met ? 'check' : 'remove') + '</span>' +
                    '<span><span class="' + (criterion.met ? 'text-foreground' : 'text-foreground-muted') + '">' +
                    md.escape(criterion.name || '') + '</span>' +
                    (criterion.note ? '<span class="block text-xs text-foreground-muted/80">' + md.escape(criterion.note) + '</span>' : '') +
                    '</span></li>';
            }).join('') + '</ul>');
        }

        if (attempt.misconceptions && attempt.misconceptions.length) {
            parts.push('<div class="mt-4 space-y-2">' + attempt.misconceptions.map(function (item) {
                return '<div class="dth-misconception rounded-r-xl px-3.5 py-2.5 text-sm">' +
                    '<p class="font-medium text-foreground">' + md.escape(item.concept || '') + '</p>' +
                    (item.belief ? '<p class="mt-1 text-xs text-foreground-muted">' + md.escape(text.youThought || '') + ' ' + md.escape(item.belief) + '</p>' : '') +
                    (item.correction ? '<p class="mt-1 text-foreground/90">' + md.escape(item.correction) + '</p>' : '') +
                    '</div>';
            }).join('') + '</div>');
        }

        /* Confidence vs. demonstrated understanding — calibration is a skill,
           and it only improves if you can see the gap. */
        if (attempt.calibration_gap !== null && attempt.calibration_gap !== undefined) {
            var gap = attempt.calibration_gap;
            var label = Math.abs(gap) <= 15 ? text.calibrationGood
                : (gap > 0 ? text.calibrationOver : text.calibrationUnder);
            parts.push('<p class="dth-coord mt-4">' + md.escape(label || '') + '</p>');
        }

        verdictPanel.innerHTML = parts.join('');
        verdictPanel.classList.remove('dth-cleared', 'dth-held');
        verdictPanel.classList.add(passed ? 'dth-cleared' : 'dth-held');
        show(verdictPanel);

        announce((passed ? text.verdictPass : text.verdictIncomplete) + '. ' + (attempt.feedback || ''));

        if (state.surfaced) announce(text.surfaced || '');
    }

    /* --- Wiring ------------------------------------------------------------ */
    if (proofForm) proofForm.addEventListener('submit', submitProof);

    document.addEventListener('click', function (event) {
        var deeper = event.target.closest('[data-descend-deeper]');
        if (deeper) {
            event.preventDefault();
            startStream(null);
            return;
        }

        var reframe = event.target.closest('[data-reframe]');
        if (reframe) {
            event.preventDefault();
            startStream(reframe.getAttribute('data-reframe'));
        }
    });

    /* Reading or writing counts as studying; ambient motion stops either way. */
    ['focusin', 'input'].forEach(function (type) {
        document.addEventListener(type, function (event) {
            if (event.target.closest('#dth-thread, #dth-control-checkpoint')) {
                if (window.DTH) window.DTH.setStudying(true);
            }
        });
    });

    /* --- Boot -------------------------------------------------------------- */
    var initialDepth = parseInt(config.getAttribute('data-depth'), 10) || 0;
    paintRail(initialDepth, false);

    if (config.getAttribute('data-autostream') === '1') {
        startStream(null);
    } else {
        reveal({
            status: config.getAttribute('data-status'),
            depth: initialDepth,
            surfaced: config.getAttribute('data-status') === 'surfaced',
        });
    }
})();
