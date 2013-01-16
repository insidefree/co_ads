'use strict';

/* Services */
angular.module('jQueryUI.services', [])
    .factory('uiDialog', ['$http', '$compile', '$rootScope', function($http, $compile, $rootScope) {
        return {
            open: function(templateUrl) {
                $http.get(templateUrl).success(function(response) {
                    var scope = $rootScope.$new();

                    $compile(response)(scope, function(elm) {
                        elm.dialog();

                        elm.on('dialogclose', function() {
                            elm.remove();
                        });
                    });
                });
            }
        };
    }]);
