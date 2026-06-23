/* Down the Rabbit Hole — frontend behaviour.
   Vanilla, no build step (served straight from /public). jQuery is on the page
   but not needed here. Pre-paint theme class is set inline in the layout head. */
(function () {
    'use strict';

    /* --- Theme toggle ------------------------------------------------------- */
    var root = document.documentElement;

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

    document.addEventListener('click', function (e) {
        var sw = e.target.closest('[data-theme-switch]');
        if (sw) setTheme(!root.classList.contains('dark'));

        /* Topic chips on the home hero: fill the composer and focus it. */
        var chip = e.target.closest('[data-topic]');
        if (chip) {
            var ta = document.getElementById('dth-prompt');
            if (ta) { ta.value = chip.getAttribute('data-topic'); ta.focus(); }
        }

        /* Password reveal toggle on auth forms. */
        var pwToggle = e.target.closest('[data-password-toggle]');
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
    });

    /* --- Depth: the rabbit-hole descent ------------------------------------
       Raise as the user goes deeper (0 = surface, 1 = the deep). The atmosphere
       in custom.css reacts via the --depth custom property. Call from chat code:
         DTH.setDepth(currentNodeDepth / maxDepth)  */
    window.DTH = {
        setDepth: function (v) {
            var d = Math.max(0, Math.min(1, Number(v) || 0));
            root.style.setProperty('--depth', String(d));
        }
    };

    syncSwitches();
})();
