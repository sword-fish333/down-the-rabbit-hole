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

    /* --- The subject rail -------------------------------------------------- */
    /* Static from `lg` up, off-canvas below it. Position is CSS; this only
       handles the drawer state, and only on the screens that have one. */
    var sidebar = document.getElementById('dth-sidebar');
    var scrim = document.querySelector('[data-sidebar-scrim]');
    var railTrigger = null;

    function setSidebar(open) {
        if (!sidebar) return;

        sidebar.classList.toggle('is-open', open);
        if (scrim) scrim.hidden = !open;
        body.classList.toggle('is-rail-open', open);

        document.querySelectorAll('[data-sidebar-open]').forEach(function (el) {
            el.setAttribute('aria-expanded', String(open));
        });

        if (open) {
            var first = sidebar.querySelector('a, button');
            if (first) first.focus();
        } else if (railTrigger) {
            railTrigger.focus();
            railTrigger = null;
        }
    }

    /* Which view the rail shows is a server-rendered decision (the folder tree
       is only queried when it is on screen), so the preference goes in a cookie
       and the page is asked again. One reload on a rare, deliberate toggle. */
    function setSubjectView(view) {
        document.cookie = 'dth_subject_view=' + view + '; path=/; max-age=31536000; samesite=lax';
        window.location.reload();
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

        var railOpen = target.closest('[data-sidebar-open]');
        if (railOpen) {
            railTrigger = railOpen;
            setSidebar(true);
        }

        if (target.closest('[data-sidebar-close], [data-sidebar-scrim]')) setSidebar(false);

        var subjectView = target.closest('[data-subject-view]');
        if (subjectView && subjectView.getAttribute('aria-pressed') !== 'true') {
            setSubjectView(subjectView.getAttribute('data-subject-view'));
        }

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

    /* Escape closes the open menu or the rail, and returns focus to its trigger. */
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        var open = document.querySelector('[data-menu-toggle][aria-expanded="true"]');
        if (open) {
            toggleMenu(open, false);
            open.focus();
            return;
        }

        if (sidebar && sidebar.classList.contains('is-open')) setSidebar(false);
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
    /* `field-sizing: content` is the native version of this, so where the browser
       has it the browser owns the height and JS never writes one. The fallback
       grows from scrollHeight — but ONLY while the field is actually rendered: a
       hidden element measures 0, and writing that back collapses the box and
       clips its own placeholder the moment it is revealed. */
    var nativeFieldSizing = !!(window.CSS && CSS.supports && CSS.supports('field-sizing', 'content'));

    function autoGrow(field) {
        if (nativeFieldSizing || !field || field.offsetParent === null) return;

        field.style.height = 'auto';
        field.style.height = Math.min(field.scrollHeight, 416) + 'px';
    }

    /* Back to the resting height after the value is cleared. */
    function resetGrow(field) {
        if (!field) return;
        field.style.height = '';
        autoGrow(field);
    }

    window.DTH.autoGrow = autoGrow;
    window.DTH.resetGrow = resetGrow;

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
