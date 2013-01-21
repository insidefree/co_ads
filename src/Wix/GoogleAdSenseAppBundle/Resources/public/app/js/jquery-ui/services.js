'use strict';

/* Services */
angular.module('jQueryUI.services', [])
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
                                ((options || {}).submit || angular.noop)();
                                elm.dialog('close');
                            },
                            'Cancel': function() {
                                ((options || {}).cancel || angular.noop)();
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
