/* jQuery UI module */
(function(window) {
    'use strict';

    window.angular.module('jQueryUI', [])
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
    })
    /**
     * dialog service
     * @todo add docs
     */
    .factory('uiDialog', ['$http', '$compile', '$rootScope', function($http, $compile, $rootScope) {
        return {
            alert: function(templateUrl, options) {
                $http.get(templateUrl).success(function(response) {
                    var scope = $rootScope.$new();

                    $compile(response)(scope, function(elm) {
                        elm.dialog({
                            modal: true
                        });

                        elm.on('dialogclose', function() {
                            elm.remove();
                        });
                    });
                });
            },
            confirm: function(templateUrl, options) {
                $http.get(templateUrl).success(function(response) {
                    var scope = $rootScope.$new();

                    $compile(response)(scope, function(elm) {
                        elm.dialog({
                            modal: true,
                            buttons: {
                                'Submit': function() {
                                    ((options || {}).submit || window.angular.noop)();
                                    elm.dialog('close');
                                },
                                'Cancel': function() {
                                    ((options || {}).cancel || window.angular.noop)();
                                    elm.dialog('close');
                                }
                            }
                        });

                        elm.on('dialogclose', function() {
                            elm.remove();
                        });
                    });
                });
            }
        };
    }]);
}(window));