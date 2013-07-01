/**
 * Ronen Amiel <ronena@codeoasis.com>
 * 1/31/13, 11:20 AM
 */
(function(window, Wix, jQuery) {
    'use strict';

    // report height change to wix whenever the document is refreshed
    jQuery(function() {
        Wix.reportHeightChange(
            jQuery('body').height() + 15
        );
    });
}(window, window.Wix, window.jQuery));