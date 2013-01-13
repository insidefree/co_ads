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
            }
        };
    }])
    /**
     * loader directive.
     */
    .directive('loader', [function () {
        return {
            restrict: 'C',
            link: function(scope, elm, attr) {
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
     * directive for the chosen plugin.
     */
    .directive('chosen', [function() {
        return {
            require: '?ngModel',
            link: function(scope, elm, attr) {
                setTimeout(function() {
                    elm.chosen({
                        disable_search: attr.disableSearch || true
                    });
                }, 1);

                if (attr.ngModel) {
                    scope.$watch(attr.ngModel, function() {
                        elm.trigger('liszt:updated');
                    });
                }
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