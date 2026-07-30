/* Down the Rabbit Hole — frontend behaviour.

   Vanilla, no build step (served straight from /public). The pre-paint theme
   class is set inline in the layout head so there is no flash of the wrong UI.

   Everything here is either (a) state the CSS can't reach on its own, or (b) a
   progressive enhancement with a working no-JS baseline. There are no
   decorative loops, no permanent mousemove listeners, and nothing that keeps
   running while the tab is hidden.  */
(function () {
    'use strict';

    var root = document.documentElement;
    var body = document.body;
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');

    /* --- Theme ------------------------------------------------------------- */
    function syncSwitches() {
        var on = root.classList.contains('dark');
        document.querySelectorAll('[data-theme-switch]').forEach(function (el) {
            el.setAttribute('aria-checked', String(on));
        });
    }

    function setTheme(dark) {
        root.classList.toggle('dark', dark);
        try { localStorage.setItem('dth-theme', dark ? 'dark' : 'light'); } catch (e) {}
        syncSwitches();
    }

    /* --- Depth: the signature mechanic ------------------------------------- */
    window.DTH = {
        /* 0 = surface, 1 = the deep. The atmosphere in custom.css reacts. */
        setDepth: function (fraction) {
            var d = Math.max(0, Math.min(1, Number(fraction) || 0));
            root.style.setProperty('--depth', String(d));
        },

        /* Suspends ambient motion. Set while the learner reads, types, or waits
           on a turn — concentration outranks atmosphere. */
        setStudying: function (on) {
            body.classList.toggle('is-studying', !!on);
        },

        /* Announce to screen readers without stealing focus. */
        announce: function (message) {
            var region = document.getElementById('dth-live');
            if (region) region.textContent = message;
        },

        prefersReducedMotion: function () {
            return reduced.matches;
        },
    };

    /* --- Deep-work mode ---------------------------------------------------- */
    var FOCUS_KEY = 'dth-focus';

    function setFocusMode(on) {
        body.classList.toggle('is-focus', on);
        try { localStorage.setItem(FOCUS_KEY, on ? '1' : '0'); } catch (e) {}
        document.querySelectorAll('[data-focus-toggle]').forEach(function (el) {
            el.setAttribute('aria-pressed', String(on));
        });
        window.DTH.announce(on
            ? (body.dataset.focusOnLabel || 'Deep-work mode on')
            : (body.dataset.focusOffLabel || 'Deep-work mode off'));
    }

    if (body.hasAttribute('data-workspace')) {
        try {
            if (localStorage.getItem(FOCUS_KEY) === '1') setFocusMode(true);
        } catch (e) {}
    }

    /* --- Menus ------------------------------------------------------------- */
    function toggleMenu(button, open) {
        var menu = document.getElementById(button.getAttribute('aria-controls'));
        if (!menu) return;

        button.setAttribute('aria-expanded', String(open));
        menu.hidden = !open;

        if (open) {
            var first = menu.querySelector('a, button');
            if (first) first.focus();
        }
    }

    /* --- Tabs (profile) ---------------------------------------------------- */
    function activateTab(name) {
        document.querySelectorAll('[data-tab]').forEach(function (el) {
            var active = el.getAttribute('data-tab') === name;
            el.setAttribute('aria-selected', String(active));
            el.tabIndex = active ? 0 : -1;
        });
        document.querySelectorAll('[data-panel]').forEach(function (el) {
            el.hidden = el.getAttribute('data-panel') !== name;
        });
    }

    /* --- Delegated clicks -------------------------------------------------- */
    document.addEventListener('click', function (event) {
        var target = event.target;

        var themeSwitch = target.closest('[data-theme-switch]');
        if (themeSwitch) setTheme(!root.classList.contains('dark'));

        var focusToggle = target.closest('[data-focus-toggle]');
        if (focusToggle) setFocusMode(!body.classList.contains('is-focus'));

        /* Topic suggestion: fill the composer and hand focus to it, so the
           learner can edit rather than being committed by one tap. */
        var chip = target.closest('[data-topic]');
        if (chip) {
            var field = document.getElementById('dth-prompt');
            if (field) {
                field.value = chip.getAttribute('data-topic');
                field.focus();
                field.setSelectionRange(field.value.length, field.value.length);
            }
        }

        var pwToggle = target.closest('[data-password-toggle]');
        if (pwToggle) {
            var input = document.querySelector(pwToggle.getAttribute('data-password-toggle'));
            if (input) {
                var reveal = input.type === 'password';
                input.type = reveal ? 'text' : 'password';
                var icon = pwToggle.querySelector('.material-symbols-outlined');
                if (icon) icon.textContent = reveal ? 'visibility_off' : 'visibility';
                pwToggle.setAttribute('aria-pressed', String(reveal));
            }
        }

        var tab = target.closest('[data-tab]');
        if (tab) {
            event.preventDefault();
            activateTab(tab.getAttribute('data-tab'));
        }

        var menuToggle = target.closest('[data-menu-toggle]');
        if (menuToggle) {
            event.preventDefault();
            toggleMenu(menuToggle, menuToggle.getAttribute('aria-expanded') !== 'true');
        } else {
            /* A click anywhere outside an open menu closes it. */
            document.querySelectorAll('[data-menu-toggle][aria-expanded="true"]').forEach(function (el) {
                if (!target.closest('#' + el.getAttribute('aria-controls'))) toggleMenu(el, false);
            });
        }

        var dismiss = target.closest('[data-dismiss]');
        if (dismiss) {
            var box = dismiss.closest('[data-flash]');
            if (box) box.remove();
        }
    });

    /* Escape closes the open menu and returns focus to its trigger. */
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        var open = document.querySelector('[data-menu-toggle][aria-expanded="true"]');
        if (open) {
            toggleMenu(open, false);
            open.focus();
        }
    });

    var initialTab = body.getAttribute('data-active-tab');
    if (initialTab) activateTab(initialTab);

    /* --- Learning-mode picker ---------------------------------------------- */
    /* A native radio group under the hood: the labels are real <label>s, so
       keyboard and screen-reader behaviour is the platform's, not ours. This
       only mirrors the checked state onto the card for styling. */
    function syncModePicker() {
        document.querySelectorAll('[data-mode-option] input[type="radio"]').forEach(function (input) {
            input.closest('[data-mode-option]').setAttribute('data-selected', String(input.checked));
        });
    }
    document.addEventListener('change', function (event) {
        if (event.target.matches('[data-mode-option] input[type="radio"]')) syncModePicker();
    });
    syncModePicker();

    /* --- The descent transition -------------------------------------------- */
    /* The typed topic becomes the session title via a shared-element View
       Transition. Without support (or with reduced motion) the form simply
       submits and the browser paints the next page — that is the fallback, not
       a degraded path. */
    var composerForm = document.querySelector('[data-descend]');
    if (composerForm) {
        composerForm.addEventListener('submit', function (event) {
            var field = document.getElementById('dth-prompt');
            var cta = composerForm.querySelector('[data-descend-cta]');

            if (field && !field.value.trim()) {
                event.preventDefault();
                field.focus();
                return;
            }

            /* Disabled + labelled so a slow first turn can't be double-posted. */
            if (cta) {
                cta.disabled = true;
                cta.setAttribute('aria-busy', 'true');
                var label = cta.querySelector('[data-cta-label]');
                if (label && cta.dataset.loadingLabel) label.textContent = cta.dataset.loadingLabel;
            }

            if (field && !reduced.matches && 'startViewTransition' in document) {
                field.style.viewTransitionName = 'dth-subject';
            }
        });
    }

    /* --- Composer: submit on Cmd/Ctrl+Enter -------------------------------- */
    document.addEventListener('keydown', function (event) {
        if (!(event.metaKey || event.ctrlKey) || event.key !== 'Enter') return;

        var field = event.target.closest('textarea[data-submit-on-enter]');
        if (field && field.form) field.form.requestSubmit();
    });

    /* --- Auto-growing textareas -------------------------------------------- */
    /* Height comes from scrollHeight on input only, so the composer grows with
       the answer instead of scrolling inside a fixed box. */
    function autoGrow(field) {
        field.style.height = 'auto';
        field.style.height = Math.min(field.scrollHeight, 420) + 'px';
    }
    document.querySelectorAll('textarea[data-autogrow]').forEach(autoGrow);
    document.addEventListener('input', function (event) {
        if (event.target.matches('textarea[data-autogrow]')) autoGrow(event.target);
    });

    /* --- Suspend everything decorative when the tab is hidden -------------- */
    document.addEventListener('visibilitychange', function () {
        body.classList.toggle('is-hidden', document.hidden);
    });

    /* --- Flash messages ---------------------------------------------------- */
    document.querySelectorAll('[data-flash][data-autohide]').forEach(function (box) {
        window.setTimeout(function () { box.remove(); }, 6000);
    });

    syncSwitches();
})();
