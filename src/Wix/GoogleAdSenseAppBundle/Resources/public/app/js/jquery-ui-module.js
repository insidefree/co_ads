'use strict';

/* Directives */


angular.module('jQueryUI.directives', [])
    /**
     * transforms an element into a jquery-ui accordion
     */
    .directive('uiAccordion', function() {
        return function(scope, elm, attr) {
            var heightStyle = attr.uiHeightStyle,
                header = attr.uiHeader,
                options = {heightStyle: heightStyle, header: header};
            elm.accordion(options);
        };
    });
