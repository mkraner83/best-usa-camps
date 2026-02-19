/**
 * Parent Dashboard JS  (assets/parent-dashboard.js)
 * - Remove favourite via AJAX (from favourites tab)
 */
(function ($) {
    'use strict';

    $(document).ready(function () {
        // Remove favourite button inside the dashboard
        $(document).on('click', '.pd-remove-fav-btn', function () {
            var btn    = $(this);
            var card   = btn.closest('.pd-fav-card');
            var campId = btn.data('camp-id');

            if (!campId) return;

            btn.prop('disabled', true).text('Removing…');

            $.ajax({
                url: parentDashboardData.ajaxUrl,
                type: 'POST',
                data: {
                    action : 'parent_toggle_favourite',
                    nonce  : parentDashboardData.nonce,
                    camp_id: campId,
                },
                success: function (res) {
                    if (res.success && res.data.action === 'removed') {
                        card.fadeOut(300, function () { card.remove(); });
                    } else {
                        btn.prop('disabled', false).text('♡ Remove');
                    }
                },
                error: function () {
                    btn.prop('disabled', false).text('♡ Remove');
                },
            });
        });
    });
})(jQuery);
