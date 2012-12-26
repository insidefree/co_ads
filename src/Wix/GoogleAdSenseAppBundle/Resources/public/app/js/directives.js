'use strict';

/* Directives */
angular.module('adSenseApp.directives', [])
    /**
     * loader directive.
     */
    .directive('loader', [function () {
        return {
            restrict: 'C',
            link: function(scope, elm) {
                elm.hide();

                scope.$on('$routeChangeStart', function() {
                    elm.fadeIn('fast');
                });

                scope.$on('$routeChangeSuccess', function() {
                    elm.fadeOut('fast');
                });
            }
        };
    }]);