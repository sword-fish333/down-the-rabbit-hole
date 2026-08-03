/* Down the Rabbit Hole — the library and the organiser.

   Two screens, one file, each block guarded by its own root element so nothing
   runs on a page that doesn't have it.

   The rule both blocks follow: the server already renders every row and every
   folder, and every mutation already has a real route. This file never builds
   markup the server can build, and never invents an endpoint — it appends HTML
   the server sent, and it fills in forms the server rendered. That is why the
   whole surface still works with JavaScript off. */

/* =============================================================================
   1. The library — search, infinite scroll, and bulk selection.
   ========================================================================== */
(function () {
    'use strict';

    var library = document.querySelector('[data-subject-library]');
    if (!library) return;

    var endpoint = library.getAttribute('data-endpoint');
    var rows = library.querySelector('[data-rows]');
    var moreBox = library.querySelector('[data-rows-more]');
    var searchForm = library.querySelector('[data-subject-search]');
    var searchField = searchForm && searchForm.querySelector('[name="q"]');
    var filterField = searchForm && searchForm.querySelector('[name="filter"]');

    var selectionBar = library.querySelector('[data-selection-bar]');
    var selectToggle = library.querySelector('[data-select-toggle]');
    var selectionCount = library.querySelector('[data-selection-count]');
    var selectionSubmit = library.querySelector('[data-selection-submit]');

    var cursor = readCursor();
    var loading = false;
    var debounce = null;

    function readCursor() {
        var meta = rows.querySelector('[data-rows-meta]');
        return meta ? (meta.getAttribute('data-next-cursor') || '') : '';
    }

    function url(withCursor) {
        var target = new URL(endpoint, window.location.origin);
        var term = searchField ? searchField.value.trim() : '';

        if (term) target.searchParams.set('q', term);
        if (filterField && filterField.value) target.searchParams.set('filter', filterField.value);
        if (withCursor && cursor) target.searchParams.set('cursor', cursor);
        target.searchParams.set('fragment', '1');

        return target;
    }

    /* Fetch one page of rows and either replace the list (a new search) or add
       to it (scrolling). The response is HTML the server rendered from the same
       Blade partial as the first paint — no second row renderer exists. */
    function load(append) {
        if (loading) return;
        loading = true;
        library.setAttribute('data-loading', '1');

        fetch(url(append), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin',
        })
            .then(function (response) { return response.text(); })
            .then(function (html) {
                var parsed = document.createElement('template');
                parsed.innerHTML = html;

                var meta = parsed.content.querySelector('[data-rows-meta]');
                cursor = meta ? (meta.getAttribute('data-next-cursor') || '') : '';

                if (!append) rows.replaceChildren();
                rows.append(parsed.content);

                if (moreBox) moreBox.hidden = !cursor;
            })
            .catch(function () {
                /* Leave what is on screen alone. The next scroll or keystroke
                   retries, and a half-cleared list would be worse than a stale one. */
            })
            .finally(function () {
                loading = false;
                library.removeAttribute('data-loading');
            });
    }

    /* --- Search: debounced, and the address bar keeps up so the result is
           linkable and the back button behaves. --- */
    if (searchField) {
        searchForm.addEventListener('submit', function (event) { event.preventDefault(); });

        searchField.addEventListener('input', function () {
            window.clearTimeout(debounce);
            debounce = window.setTimeout(function () {
                cursor = '';
                var shown = url(false);
                shown.searchParams.delete('fragment');
                window.history.replaceState({}, '', shown);
                load(false);
            }, 220);
        });
    }

    /* --- Infinite scroll. The "Load more" link stays in the markup as the
           no-JS path and as the fallback where IntersectionObserver isn't. --- */
    if (moreBox && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (entries) {
            if (entries[0].isIntersecting && cursor) load(true);
        }, { rootMargin: '600px 0px' }).observe(moreBox);
    }

    if (moreBox) {
        moreBox.addEventListener('click', function (event) {
            if (!event.target.closest('[data-rows-next]')) return;
            event.preventDefault();
            load(true);
        });
    }

    /* --- Bulk selection ---------------------------------------------------- */
    function boxes() {
        return rows.querySelectorAll('input[name="ids[]"]');
    }

    function syncSelection() {
        var checked = rows.querySelectorAll('input[name="ids[]"]:checked').length;
        if (selectionCount) selectionCount.textContent = String(checked);
        if (selectionSubmit) selectionSubmit.disabled = checked === 0;
    }

    function setSelecting(on) {
        library.classList.toggle('is-selecting', on);
        if (selectionBar) selectionBar.hidden = !on;
        if (selectToggle) selectToggle.setAttribute('aria-pressed', String(on));

        if (!on) {
            boxes().forEach(function (box) { box.checked = false; });
            syncSelection();
        }
    }

    if (selectToggle) {
        selectToggle.hidden = false;
        selectToggle.setAttribute('aria-pressed', 'false');
        selectToggle.addEventListener('click', function () {
            setSelecting(!library.classList.contains('is-selecting'));
        });
    }

    library.addEventListener('click', function (event) {
        if (event.target.closest('[data-select-cancel]')) {
            setSelecting(false);
            return;
        }

        if (event.target.closest('[data-select-all]')) {
            boxes().forEach(function (box) { box.checked = true; });
            syncSelection();
            return;
        }

        /* While selecting, the row is a checkbox rather than a link — following
           it would lose the selection the learner is halfway through building. */
        if (library.classList.contains('is-selecting')) {
            var link = event.target.closest('.dth-row-link');
            if (link && !event.target.closest('.dth-select')) {
                event.preventDefault();
                var box = link.closest('[data-subject-row]').querySelector('input[name="ids[]"]');
                if (box) box.checked = !box.checked;
                syncSelection();
            }
        }
    });

    library.addEventListener('change', function (event) {
        if (event.target.matches('input[name="ids[]"]')) syncSelection();
    });
})();

/* =============================================================================
   2. The organiser — a folder tree you can drag things into.

   Every drop submits the hidden form the page already rendered, so the move
   goes through the same validated route as the no-JS path: the server decides
   whether a move is legal (a folder can't land inside itself) and answers with
   the normal redirect and flash. No optimistic reordering, no second source of
   truth about where anything lives.
   ========================================================================== */
(function () {
    'use strict';

    var root = document.querySelector('[data-subject-tree]');
    if (!root) return;

    var moveForm = root.querySelector('#dth-move-form');
    var searchField = root.querySelector('[data-tree-search]');
    var noMatches = root.querySelector('[data-tree-no-matches]');
    var newFolderName = root.querySelector('[data-new-folder-name]');
    var newFolderParent = root.querySelector('[data-new-folder-parent]');
    var newFolderHint = root.querySelector('[data-new-folder-hint]');
    var newFolderHintText = root.querySelector('[data-new-folder-hint-text]');

    var OPEN_KEY = 'dth-tree-open';
    var dragging = null;

    /* --- Which folders are open survives the redirect after every move. ---- */
    function readOpen() {
        try { return JSON.parse(window.localStorage.getItem(OPEN_KEY) || '[]'); } catch (e) { return []; }
    }

    function storeOpen() {
        var open = [];
        root.querySelectorAll('[data-folder-details]').forEach(function (details) {
            if (details.open) open.push(details.getAttribute('data-folder-id'));
        });
        try { window.localStorage.setItem(OPEN_KEY, JSON.stringify(open)); } catch (e) {}
    }

    var remembered = readOpen();
    root.querySelectorAll('[data-folder-details]').forEach(function (details) {
        if (remembered.indexOf(details.getAttribute('data-folder-id')) !== -1) details.open = true;
        details.addEventListener('toggle', function () {
            if (!searchField || !searchField.value.trim()) storeOpen();
        });
    });

    /* --- Moving things ----------------------------------------------------- */
    function submitMove(action, field, value) {
        if (!moveForm) return;

        moveForm.setAttribute('action', action);
        moveForm.querySelectorAll('[data-move-field]').forEach(function (el) { el.remove(); });

        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = field;
        input.value = value === null ? '' : String(value);
        input.setAttribute('data-move-field', '1');
        moveForm.appendChild(input);

        root.setAttribute('data-saving', '1');
        moveForm.submit();
    }

    function moveTo(folderId) {
        if (!dragging) return;

        // Dropping a folder onto itself is a no-op, not an error worth a round trip.
        if (dragging.type === 'folder' && String(folderId) === dragging.id) return;

        submitMove(
            dragging.type === 'folder'
                ? root.getAttribute('data-folder-endpoint').replace('__ID__', dragging.id)
                : root.getAttribute('data-subject-endpoint').replace('__ID__', dragging.id),
            dragging.type === 'folder' ? 'parent_id' : 'folder_id',
            folderId,
        );
    }

    root.addEventListener('dragstart', function (event) {
        var handle = event.target.closest('[data-drag-type]');
        if (!handle) return;

        dragging = { type: handle.getAttribute('data-drag-type'), id: handle.getAttribute('data-drag-id') };
        handle.classList.add('is-dragging');

        // Some browsers refuse to start a drag without payload, even unused.
        if (event.dataTransfer) {
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragging.type + ':' + dragging.id);
        }
    });

    root.addEventListener('dragend', function () {
        dragging = null;
        root.querySelectorAll('.is-dragging, .is-drop-target').forEach(function (el) {
            el.classList.remove('is-dragging', 'is-drop-target');
        });
    });

    root.addEventListener('dragover', function (event) {
        if (!dragging) return;

        var folder = event.target.closest('[data-drop-folder]');
        var zone = folder || event.target.closest('[data-drop-root]');
        if (!zone) return;

        event.preventDefault();
        if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';

        root.querySelectorAll('.is-drop-target').forEach(function (el) { el.classList.remove('is-drop-target'); });
        if (folder) folder.classList.add('is-drop-target');
    });

    root.addEventListener('drop', function (event) {
        if (!dragging) return;

        var folder = event.target.closest('[data-drop-folder]');
        if (!folder && !event.target.closest('[data-drop-root]')) return;

        event.preventDefault();
        moveTo(folder ? folder.getAttribute('data-drop-folder') : null);
    });

    /* --- Rename, new subfolder, guarded delete ----------------------------- */
    root.addEventListener('click', function (event) {
        var rename = event.target.closest('[data-rename-folder]');
        if (rename) {
            event.preventDefault();
            var form = root.querySelector('[data-rename-form="' + rename.getAttribute('data-rename-folder') + '"]');
            if (form) {
                form.hidden = !form.hidden;
                if (!form.hidden) form.querySelector('input[name="name"]').focus();
            }
            return;
        }

        var subfolder = event.target.closest('[data-new-subfolder]');
        if (subfolder) {
            event.preventDefault();
            if (newFolderParent) newFolderParent.value = subfolder.getAttribute('data-new-subfolder');
            if (newFolderHint && newFolderHintText) {
                newFolderHintText.textContent = subfolder.getAttribute('data-folder-name');
                newFolderHint.hidden = false;
            }
            if (newFolderName) newFolderName.focus();
            return;
        }

        if (event.target.closest('[data-new-folder-reset]')) {
            event.preventDefault();
            if (newFolderParent) newFolderParent.value = '';
            if (newFolderHint) newFolderHint.hidden = true;
            return;
        }

        /* Deleting a folder takes its subfolders with it, so it asks once. Without
           JavaScript the form simply posts — the copy under the tree says what
           will happen either way. */
        var remove = event.target.closest('[data-confirm-delete]');
        if (remove && !remove.hasAttribute('data-armed')) {
            event.preventDefault();
            remove.setAttribute('data-armed', '1');
            remove.classList.add('text-danger');
            remove.setAttribute('title', root.getAttribute('data-confirm-label') || '');
            window.setTimeout(function () {
                remove.removeAttribute('data-armed');
                remove.classList.remove('text-danger');
            }, 3000);
        }
    });

    /* --- Filtering the tree ------------------------------------------------ */
    /* Client-side: a learner's own tree is tens of rows, and a round trip per
       keystroke would be slower than the eye. Matching a nested subject reveals
       the folders above it, because a match you can't see hasn't been found. */
    function filter(term) {
        var nodes = root.querySelectorAll('[data-tree-node]');

        if (!term) {
            nodes.forEach(function (node) { node.hidden = false; });
            if (noMatches) noMatches.hidden = true;
            return;
        }

        var found = false;
        nodes.forEach(function (node) { node.hidden = true; });

        nodes.forEach(function (node) {
            if ((node.getAttribute('data-name') || '').indexOf(term) === -1) return;

            found = true;
            node.hidden = false;

            var parent = node.parentElement ? node.parentElement.closest('[data-tree-node]') : null;
            while (parent) {
                parent.hidden = false;
                var details = parent.querySelector(':scope > details');
                if (details) details.open = true;
                parent = parent.parentElement ? parent.parentElement.closest('[data-tree-node]') : null;
            }
        });

        if (noMatches) noMatches.hidden = found;
    }

    if (searchField) {
        searchField.addEventListener('input', function () {
            filter(searchField.value.trim().toLowerCase());
        });
    }
})();
