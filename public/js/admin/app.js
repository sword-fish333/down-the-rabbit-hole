/* global $ */
$(function () {
    // CSRF for all jQuery AJAX requests.
    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    });

    /* ----------------------------------------------------------------------
     * Dark mode (persisted in localStorage; applied pre-paint by the layout)
     * -------------------------------------------------------------------- */
    $('[data-theme-toggle]').on('click', function () {
        const isDark = $('html').toggleClass('dark').hasClass('dark');
        localStorage.setItem('admin-theme', isDark ? 'dark' : 'light');
    });

    /* ----------------------------------------------------------------------
     * Mobile sidebar
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

    /* ----------------------------------------------------------------------
     * Generic toggle dropdowns (user menu)
     * -------------------------------------------------------------------- */
    $('[data-dropdown-toggle]').on('click', function (e) {
        e.stopPropagation();
        $($(this).data('dropdown-toggle')).toggleClass('hidden');
    });
    $(document).on('click', function () {
        $('[data-dropdown]').addClass('hidden');
    });
    $('[data-dropdown]').on('click', (e) => e.stopPropagation());

    /* ----------------------------------------------------------------------
     * Profile tabs
     * -------------------------------------------------------------------- */
    $('[data-tab]').on('click', function () {
        const tab = $(this).data('tab');
        $('[data-tab]')
            .removeClass('bg-white text-brand-700 shadow-sm dark:bg-zinc-700 dark:text-white')
            .addClass('text-zinc-500 dark:text-zinc-400');
        $(this)
            .addClass('bg-white text-brand-700 shadow-sm dark:bg-zinc-700 dark:text-white')
            .removeClass('text-zinc-500 dark:text-zinc-400');
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
     * Password show / hide
     * -------------------------------------------------------------------- */
    $('[data-password-toggle]').on('click', function () {
        const $input = $($(this).data('password-toggle'));
        const toText = $input.attr('type') === 'password';
        $input.attr('type', toText ? 'text' : 'password');
        $(this).find('i').toggleClass('fa-eye fa-eye-slash');
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
