/* Down the Rabbit Hole — the learning workspace.

   Two transports, because the two jobs are different:
     GET  /subject/{id}/stream      SSE, prose, rendered as it arrives
     POST /subject/{id}/checkpoint  JSON, a structured verdict rendered in place

   Rules this file exists to enforce, all of them about not breaking
   concentration while someone is trying to think:
     - completed text NEVER reflows once it has been rendered;
     - the scroll position is the learner's, not ours — we only follow if they
       were already at the bottom;
     - exactly one control is offered at a time;
     - every state change is announced to assistive tech;
     - nothing animates while reading except the caret and the arriving block.

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
    var thinkingPanel = thinking && thinking.querySelector('[data-thinking-panel]');
    var thinkingLabel = thinking && thinking.querySelector('[data-thinking-label]');
    var thinkingStages = thinking && thinking.querySelector('[data-thinking-stages]');

    var controls = {
        checkpoint: document.getElementById('dth-control-checkpoint'),
        survey: document.getElementById('dth-control-survey'),
        deeper: document.getElementById('dth-control-deeper'),
        surfaced: document.getElementById('dth-control-surfaced'),
        stop: document.getElementById('dth-control-stop'),
    };

    var verdictPanel = document.getElementById('dth-verdict');
    var proofField = document.getElementById('dth-proof');
    var proofForm = document.getElementById('dth-proof-form');
    var teachLayer = document.getElementById('dth-teach-layer');
    var busy = false;
    var aborter = null;

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

    /* --- The thinking panel ------------------------------------------------- */
    /* What the guide is doing, before there is prose to show. Every line here
       arrives from the server as it happens — nothing is on a timer, and a
       subject with no source never claims to be reading one. The panel is moved
       to the end of the thread each turn so it always sits directly above the
       answer it belongs to. */
    function openThinking() {
        if (!thinking) return;

        thread.appendChild(thinking);
        thinkingStages.replaceChildren();
        thinkingLabel.textContent = text.thinking || '';
        thinking.removeAttribute('data-done');
        if (thinkingPanel) thinkingPanel.open = true;
        show(thinking);
    }

    function addStage(label) {
        if (!thinkingStages || !label) return;

        var previous = thinkingStages.lastElementChild;
        if (previous) previous.setAttribute('data-state', 'done');

        var item = document.createElement('li');
        item.setAttribute('data-state', 'active');
        item.innerHTML = '<span class="dth-stage-mark" aria-hidden="true"></span>' +
            '<span class="dth-stage-text">' + md.escape(label) + '</span>';
        thinkingStages.appendChild(item);
    }

    /* Collapsed, not removed: the trace of how a layer was built is worth being
       able to reopen, and it is worth nothing while you are reading. */
    function closeThinking() {
        if (!thinking) return;

        var stages = thinkingStages.children;
        for (var i = 0; i < stages.length; i++) stages[i].setAttribute('data-state', 'done');

        thinking.setAttribute('data-done', '1');
        thinkingLabel.textContent = text.thoughtFor || '';
        if (thinkingPanel) thinkingPanel.open = false;

        if (!stages.length) hide(thinking);
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
        hide(controls.survey);
        hide(controls.deeper);
        hide(controls.surfaced);
        hide(controls.stop);

        if (state.surfaced || state.status === 'surfaced') {
            show(controls.surfaced);
            announce(text.surfaced || '');
            return;
        }

        if (state.status === 'checkpoint_pending') {
            /* The offer of a lesson belongs to a checkpoint that was posed and
               never taught, so it is read from server state every time rather
               than left as it was rendered — one teach turn retires it. */
            if (teachLayer) teachLayer.hidden = !state.awaits_teaching;

            show(controls.checkpoint);
            // Only measurable now that it is on screen — see DTH.autoGrow.
            if (window.DTH) window.DTH.autoGrow(proofField);
            if (proofField) proofField.focus({ preventScroll: true });
            announce(text.checkpointReady || '');
            return;
        }

        /* The ground before the first layer. Read from server state like every
           other control: only the descent knows whether it is still owed. */
        if (state.awaits_survey && controls.survey) {
            show(controls.survey);
            return;
        }

        show(controls.deeper);
    }

    function fail(message) {
        closeThinking();
        hide(controls.stop);
        if (!errorBox) return;
        errorBox.textContent = message;
        show(errorBox);
        announce(message);
    }

    /* --- Streaming a teaching turn ----------------------------------------- */
    /* Text arrives token by token but is REVEALED a block at a time: everything
       up to the last completed block is committed into stable DOM once (and
       animates in once), and only the unfinished tail is re-rendered per frame.
       That is what makes a long layer read like it is being written rather than
       flickering, and it means nothing already read is ever re-laid-out. */
    function startStream(reframe) {
        if (busy) return;
        busy = true;

        hide(errorBox);
        hide(controls.checkpoint);
        hide(controls.survey);
        hide(controls.deeper);
        hide(verdictPanel);
        openThinking();
        show(controls.stop);
        if (window.DTH) window.DTH.setStudying(true);

        var turn = document.createElement('article');
        turn.className = 'dth-turn measure mx-auto w-full';
        turn.setAttribute('aria-live', 'off');
        turn.innerHTML = '<div class="prose-dth" data-stream-body></div>';
        thread.appendChild(turn);

        var bodyEl = turn.querySelector('[data-stream-body]');
        var tailEl = document.createElement('div');
        bodyEl.appendChild(tailEl);

        var buffer = '';
        var committed = 0;
        var pending = false;
        var stuck = nearBottom();

        /* Re-render at most once per frame: a token-per-render loop on a long
           turn is the difference between 60fps and a stuttering read. */
        function paint(final) {
            pending = false;

            var cut = final ? buffer.length : commitPoint(buffer, committed);

            if (cut > committed) {
                var block = document.createElement('div');
                block.innerHTML = md.render(buffer.slice(committed, cut));
                committed = cut;

                /* The rendered nodes are moved in, not wrapped: the prose rules
                   are written for `.prose-dth > p + p`, and a wrapper div would
                   quietly change every margin in the reading column. */
                while (block.firstChild) {
                    var node = block.firstChild;
                    if (node.nodeType === 1) node.classList.add('dth-block-in');
                    bodyEl.insertBefore(node, tailEl);
                }
            }

            var rest = buffer.slice(committed);
            tailEl.innerHTML = rest
                ? md.render(rest) + (final ? '' : '<span class="dth-caret" aria-hidden="true"></span>')
                : '';
            tailEl.hidden = !rest;

            follow(stuck);
        }

        function schedule() {
            if (pending) return;
            pending = true;
            window.requestAnimationFrame(function () { paint(false); });
        }

        function onEvent(name, data) {
            if (name === 'stage') {
                addStage(data.label);
                return;
            }

            if (name === 'token') {
                buffer += (data.text || '');
                stuck = nearBottom() || stuck;
                schedule();
                return;
            }

            if (name === 'done') {
                busy = false;
                paint(true);
                closeThinking();
                /* A layer that arrived as its checkpoint says so, or the learner
                   reads a question with no lesson above it and assumes the turn
                   broke. Only a posed layer leaves teaching still on offer. */
                if (data.phase === 'survey') markSurvey(turn);
                else if (data.awaits_teaching) markPosed(turn);
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

        aborter = new AbortController();

        readEventStream(url, onEvent, aborter.signal).catch(function (error) {
            busy = false;
            /* A deliberate stop is not a failure. The page reloads so what the
               learner sees is what the server actually kept — the alternative is
               prose on screen that may not exist any more. */
            if (error && error.name === 'AbortError') {
                announce(text.stopped || '');
                window.location.reload();
                return;
            }
            fail(text.connectionLost || '');
        });
    }

    /* Mirrors the server-rendered marker on a question-first turn, so a layer
       reloaded from the transcript and one just streamed look identical. */
    /* A survey is not a layer, and it looks exactly like one until it says so:
       prose about the subject, arriving where a lesson normally arrives. The
       mark names it and links the method it comes from. */
    function markSurvey(turn) {
        var mark = document.createElement('p');
        mark.className = 'dth-coord mb-3 flex flex-wrap items-center gap-x-2.5 gap-y-1';
        mark.innerHTML = '<span class="inline-flex items-center gap-1.5">' +
            '<span class="material-symbols-outlined text-[1rem] text-primary" aria-hidden="true">travel_explore</span>' +
            md.escape(text.survey || '') + '</span>';

        if (text.surveyUrl) {
            var link = document.createElement('a');
            link.href = text.surveyUrl;
            link.target = '_blank';
            link.rel = 'noopener';
            link.className = 'normal-case tracking-normal text-foreground-muted/85 underline decoration-border underline-offset-4 transition duration-(--motion-feedback) hover:text-foreground hover:decoration-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring';
            link.textContent = text.surveyLink || '';
            mark.appendChild(link);
        }

        turn.insertBefore(mark, turn.firstChild);
    }

    function markPosed(turn) {
        var mark = document.createElement('p');
        mark.className = 'dth-coord mb-3 flex items-center gap-1.5';
        mark.innerHTML = '<span class="material-symbols-outlined text-[1rem] text-primary" aria-hidden="true">psychology_alt</span>' +
            md.escape(text.posed || '');
        turn.insertBefore(mark, turn.firstChild);
    }

    /* The furthest point in the buffer that is safe to freeze: the last blank
       line that is NOT inside an open code fence. Committing inside a fence
       would render half a code block as prose and then re-render it as code. */
    function commitPoint(buffer, from) {
        var index = buffer.lastIndexOf('\n\n');

        while (index > from) {
            var head = buffer.slice(0, index);
            if ((head.split('```').length - 1) % 2 === 0) return index + 2;
            index = buffer.lastIndexOf('\n\n', index - 1);
        }

        return from;
    }

    /* SSE over fetch. EventSource can't send credentials-aware POSTs or custom
       headers, and we need the reader anyway to keep rendering incremental. */
    function readEventStream(url, onEvent, signal) {
        return fetch(url, {
            headers: { Accept: 'text/event-stream' },
            credentials: 'same-origin',
            signal: signal,
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
                if (window.DTH) window.DTH.resetGrow(proofField);
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
            '<summary data-verdict-toggle class="-m-1 flex cursor-pointer select-none list-none items-center gap-2 rounded-xl p-1 transition-colors duration-(--motion-feedback) ease-(--ease-snap) hover:bg-surface-muted/50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring sm:gap-3">' +
            '<span class="flex min-w-0 flex-1 items-center gap-2 font-display text-sm font-semibold ' +
            (passed ? 'text-success' : 'text-accent') + '">' +
            '<span class="material-symbols-outlined text-[1.15rem]" aria-hidden="true">' +
            (passed ? 'check_circle' : (attempt.misconceptions.length ? 'error' : 'incomplete_circle')) +
            '</span>' +
            md.escape(passed ? text.verdictPass : (attempt.misconceptions.length ? text.verdictMisconception : text.verdictIncomplete)) +
            '</span>' +
            '<span class="dth-coord shrink-0">' + md.escape(text.scoreLabel || '') + ' ' + attempt.score + '/100</span>' +
            '<span class="material-symbols-outlined shrink-0 text-[1.1rem] text-foreground-muted transition-transform duration-(--motion-state) ease-(--ease-out) group-open/verdict:rotate-180" aria-hidden="true">expand_more</span>' +
            '<span class="sr-only">' + md.escape(text.verdictToggle || '') + '</span>' +
            '</summary>' +
            '<div class="pt-3 [&>*:first-child]:mt-0">'
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

        parts.push('</div>');
        verdictPanel.innerHTML = parts.join('');
        // Never leave newly arrived feedback hidden by an earlier choice.
        verdictPanel.open = true;
        verdictPanel.classList.remove('dth-cleared', 'dth-held');
        verdictPanel.classList.add(passed ? 'dth-cleared' : 'dth-held');
        show(verdictPanel);

        announce((passed ? text.verdictPass : text.verdictIncomplete) + '. ' + (attempt.feedback || ''));

        if (state.surfaced) announce(text.surfaced || '');
    }

    /* --- Copying a layer --------------------------------------------------- */
    /* The rendered text, not the source: it is what the learner read, and it
       keeps the markup out of their notes app. */
    function copyTurn(button) {
        var prose = button.closest('.dth-turn, .group\\/turn');
        var body = prose && prose.querySelector('.prose-dth');
        if (!body || !navigator.clipboard) return;

        navigator.clipboard.writeText(body.innerText.trim()).then(function () {
            var icon = button.querySelector('.material-symbols-outlined');
            if (icon) icon.textContent = 'check';
            announce(text.copied || '');
            window.setTimeout(function () { if (icon) icon.textContent = 'content_copy'; }, 1600);
        });
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

        /* Same stream, no argument: the server derives that a layer already
           posed and not yet taught is due its lesson. One rule, one endpoint. */
        var teach = event.target.closest('[data-teach-layer]');
        if (teach) {
            event.preventDefault();
            startStream(null);
            return;
        }

        var reframe = event.target.closest('[data-reframe]');
        if (reframe) {
            event.preventDefault();
            startStream(reframe.getAttribute('data-reframe'));
            return;
        }

        var stop = event.target.closest('[data-stop-stream]');
        if (stop) {
            event.preventDefault();
            stop.disabled = true;
            if (aborter) aborter.abort();
            return;
        }

        var copy = event.target.closest('[data-copy-turn]');
        if (copy) {
            event.preventDefault();
            copyTurn(copy);
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
            awaits_teaching: config.getAttribute('data-awaits-teaching') === '1',
            awaits_survey: config.getAttribute('data-awaits-survey') === '1',
        });
    }
})();
