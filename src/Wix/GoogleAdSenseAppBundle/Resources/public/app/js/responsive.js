/**
 * Ronen Amiel <ronena@codeoasis.com>
 * 1/31/13, 11:20 AM
 */
(function(window, jQuery) {
    'use strict';

//    // list of sizes
//    var frame = jQuery('#preview'),
//        sizes = [
//            { width: 300, height: 250 },
//            { width: 336, height: 280 },
//            { width: 728, height: 90 },
//            { width: 160, height: 600 },
//            { width: 320, height: 50 },
//            { width: 468, height: 60 },
//            { width: 234, height: 60 },
//            { width: 120, height: 600 },
//            { width: 120, height: 240 },
//            { width: 300, height: 600 },
//            { width: 250, height: 250 },
//            { width: 200, height: 200 },
//            { width: 180, height: 150 },
//            { width: 125, height: 125 },
//            { width: 728, height: 15 },
//            { width: 468, height: 15 },
//            { width: 200, height: 90 },
//            { width: 180, height: 90 },
//            { width: 160, height: 90 },
//            { width: 120, height: 90 }
//        ],
//        size = { width: frame.width(), height: frame.height() },
//        updateSize;
//
//    // resize event to match to new sizes
//    jQuery(window).resize(function(event){
//        var width = jQuery(event.target).width(),
//            height = jQuery(event.target).height(),
//            matching = [],
//            largest,
//            url;
//
//        jQuery.each(sizes, function(key, size) {
//            if (size.width <= width && size.height <= height) {
//                matching.push(size);
//            }
//        });
//
//        jQuery.each(matching, function(key, size) {
//            if (largest === undefined || (size.width * size.height) > (largest.width * largest.height)) {
//                largest = size;
//            }
//        });
//
//        if (largest.width === size.width && largest.height === size.height) {
//            return;
//        }
//
//        if ((updateSize || {}).state === 'pending') {
//            return;
//        }
//
//        console.log(largest, size);
//
//        url = generateUrl(window.Routing.generate('saveAdUnitSize'), {
//            instance: getParam('instance'),
//            compId: getParam('compId')
//        });
//
//        updateSize = jQuery.ajax(url, {
//            type: 'POST',
//            data: largest
//        });
//
//        updateSize.then(function(adUnit) {
////            window.location.reload();
//            size = {
//                width: parseInt(adUnit.width),
//                height: parseInt(adUnit.height)
//            };
//            frame.attr('width', size.width);
//            frame.attr('height', size.height);
//        });
//    });

    // report height change to wix whenever the document is refreshed
    jQuery(function() {
        window.Wix.reportHeightChange(
            window.jQuery('body').height()
        );
    });

//    /**
//     * returns a query string param by it's name
//     * @param name
//     * @returns {string}
//     */
//    function getParam(name) {
//        name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");
//        var regexS = "[\\?&]" + name + "=([^&#]*)",
//            regex = new RegExp(regexS),
//            results = regex.exec(window.location.search);
//
//        if (results === null) {
//            return null;
//        }
//
//        return decodeURIComponent(results[1].replace(/\+/g, " "));
//    }
//
//    /**
//     * generates a url with params
//     * @param url
//     * @param params
//     * @returns {string}
//     */
//    function generateUrl(url, params) {
//        return url + getSeparator(url) + jQuery.param(params);
//    }
//
//    /**
//     * returns the right separator for a url
//     * @param url
//     * @returns {string}
//     */
//    function getSeparator(url) {
//        return url.indexOf('?') === -1 ? '?' : '&';
//    }
}(window, window.jQuery));