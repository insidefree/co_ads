'use strict';

angular.module('ajaxEvents', [])
    /**
     * fires ajaxStart, ajaxSuccess and ajaxFailure events on the root scope
     */
    .config(['$httpProvider', function ($httpProvider) {
        $httpProvider.responseInterceptors.push(function($q, $rootScope) {
            return function(promise) {
                $rootScope.$emit('ajaxStart');

                return promise.then(
                    function(response) {
                        $rootScope.$emit('ajaxSuccess');
                        return response;
                    },
                    function(response) {
                        $rootScope.$emit('ajaxFailure');
                        $q.reject(response);
                    }
                );
            };
        });
    }])