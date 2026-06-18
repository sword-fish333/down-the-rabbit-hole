/* global $ */
$(function () {
    // CSRF for all jQuery AJAX requests.
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    });

    const $html = $('html');

    /* ----------------------------------------------------------------------
     * Dark mode (persisted in localStorage; applied pre-paint by the layout).
     * Any [data-theme-switch] control mirrors state via aria-checked so the
     * pill toggle (public/css/custom.css) animates to the right position.
     * -------------------------------------------------------------------- */
    function syncThemeControls(isDark) {
        $('[data-theme-switch]').attr('aria-checked', isDark ? 'true' : 'false');
    }
    syncThemeControls($html.hasClass('dark'));

    $('[data-theme-toggle]').on('click', function () {
        const isDark = $html.toggleClass('dark').hasClass('dark');
        localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
        syncThemeControls(isDark);
    });

    /* ----------------------------------------------------------------------
     * Sidebar — off-canvas drawer on mobile, collapsible icon-rail on desktop.
     * -------------------------------------------------------------------- */
    const $sidebar = $('[data-sidebar]');
    const $overlay = $('[data-sidebar-overlay]');

    function openSidebar() {
        $sidebar.removeClass('-translate-x-full');
        $overlay.removeClass('hidden');
    }
    function closeSidebar() {
        $sidebar.addClass('-translate-x-full');
        $overlay.addClass('hidden');
    }

    $('[data-sidebar-open]').on('click', openSidebar);
    $('[data-sidebar-close], [data-sidebar-overlay]').on('click', closeSidebar);

    // Desktop collapse to icon-rail (persisted, applied pre-paint via <html>).
    $('[data-sidebar-collapse]').on('click', function () {
        const collapsed = $html.toggleClass('nav-collapsed').hasClass('nav-collapsed');
        localStorage.setItem('admin-sidebar', collapsed ? 'collapsed' : 'expanded');
    });

    /* ----------------------------------------------------------------------
     * Generic toggle dropdowns (user menu)
     * -------------------------------------------------------------------- */
    $('[data-dropdown-toggle]').on('click', function (e) {
        e.stopPropagation();
        const open = $($(this).data('dropdown-toggle')).toggleClass('hidden').is(':visible');
        $(this).attr('aria-expanded', open ? 'true' : 'false');
    });
    $(document).on('click', function () {
        $('[data-dropdown]').addClass('hidden');
        $('[data-dropdown-toggle]').attr('aria-expanded', 'false');
    });
    $('[data-dropdown]').on('click', (e) => e.stopPropagation());

    /* ----------------------------------------------------------------------
     * Profile tabs
     * -------------------------------------------------------------------- */
    const TAB_ACTIVE = 'bg-surface text-primary shadow-sm';
    const TAB_INACTIVE = 'text-muted-foreground hover:text-foreground';
    $('[data-tab]').on('click', function () {
        const tab = $(this).data('tab');
        $('[data-tab]').removeClass(TAB_ACTIVE).addClass(TAB_INACTIVE).attr('aria-selected', 'false');
        $(this).addClass(TAB_ACTIVE).removeClass(TAB_INACTIVE).attr('aria-selected', 'true');
        $('[data-tab-panel]').addClass('hidden');
        $('[data-tab-panel="' + tab + '"]').removeClass('hidden');
    });

    /* ----------------------------------------------------------------------
     * Avatar: live preview + auto-submit on selection
     * -------------------------------------------------------------------- */
    $('[data-avatar-input]').on('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (e) => $('[data-avatar-preview]').attr('src', e.target.result).removeClass('hidden').next().addClass('hidden');
        reader.readAsDataURL(file);
        $(this).closest('form').trigger('submit');
    });

    /* ----------------------------------------------------------------------
     * Password show / hide (swaps the Material Symbols glyph)
     * -------------------------------------------------------------------- */
    $('[data-password-toggle]').on('click', function () {
        const $input = $($(this).data('password-toggle'));
        const toText = $input.attr('type') === 'password';
        $input.attr('type', toText ? 'text' : 'password');
        $(this).find('.material-symbols-outlined').text(toText ? 'visibility_off' : 'visibility');
    });

    /* ----------------------------------------------------------------------
     * Flash toast: auto-dismiss + manual close
     * -------------------------------------------------------------------- */
    const $toast = $('[data-toast]');
    if ($toast.length) {
        const dismiss = () => $toast.fadeOut(250);
        $('[data-toast-close]').on('click', dismiss);
        setTimeout(dismiss, 4500);
    }
});
