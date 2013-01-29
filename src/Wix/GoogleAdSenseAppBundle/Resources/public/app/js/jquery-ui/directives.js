/* Directives */
(function(window) {
    'use strict';

    window.angular.module('jQueryUI.directives', [])
    /**
     * transforms an element into a jquery-ui accordion
     */
    .directive('uiAccordion', function() {
        return function(scope, elm, attr) {
            elm.accordion({
                header: attr.uiHeader,
                heightStyle: attr.uiHeightStyle
            });
        };
    });
}(window));