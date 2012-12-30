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
    }])
    /**
     * colorpicker directive
     */
    .directive('uiColorpicker', function() {
        return {
            require: 'ngModel',
            link: function(scope, elm, attr, ctrl) {
                elm.colorPicker({
                    onColorChange: function(id, value) {
                        scope.$apply(function() {
                            ctrl.$setViewValue(value);
                        });
                    },
                    pickerDefault: scope.$eval(attr.ngModel)
                });
            }
        };
    });