/**
 * Parent Camp Shortcodes JS  (assets/parent-camp-shortcodes.js)
 * - Favourite button toggle via AJAX
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        $(document).on('click', '.pcs-fav-btn[data-camp-id]', function () {
            var btn    = $(this);
            var campId = btn.data('camp-id');

            if (!parentCampData.isLoggedIn) {
                window.location.href = parentCampData.loginUrl;
                return;
            }

            btn.prop('disabled', true);

            $.ajax({
                url: parentCampData.ajaxUrl,
                type: 'POST',
                data: {
                    action : 'parent_toggle_fav',
                    nonce  : parentCampData.nonce,
                    camp_id: campId,
                },
                success: function (res) {
                    if (res.success) {
                        var isFav = res.data.action === 'added';
                        btn.html(res.data.label);
                        btn.toggleClass('pcs-fav-active', isFav);
                        btn.data('is-fav', isFav ? '1' : '0');
                    }
                },
                complete: function () {
                    btn.prop('disabled', false);
                },
            });
        });
    });
})(jQuery);
