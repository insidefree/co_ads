'use strict';

/* Directives */
angular.module('adSenseApp.directives', ['ajaxEvents'])
    /**
     * ajax loader directive
     */
    .directive('ajaxLoader', ['$rootScope', function ($rootScope) {
        return {
            restrict: 'C',
            link: function(scope, elm) {
                elm.hide();

                $rootScope.$on('ajaxStart', function() {
                    elm.fadeIn('fast');
                });

                $rootScope.$on('ajaxSuccess', function() {
                    elm.fadeOut('fast');
                });

                $rootScope.$on('ajaxFailure', function() {
                    elm.fadeOut('fast');
                });

                $rootScope.$on();
            }
        };
    }])
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