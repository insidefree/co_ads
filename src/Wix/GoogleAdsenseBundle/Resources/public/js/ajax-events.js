(function(window) {
    'use strict';

    window.angular.module('ajaxEvents', [])
        /**
         * Emits events when ajax operations starts and when it ends (successfully or not). It's used by the ajax
         * loader directive.
         */
        .config(['$httpProvider', function ($httpProvider) {
            $httpProvider.responseInterceptors.push(['$q', '$rootScope', function($q, $rootScope) {
                return function(promise) {
                    $rootScope.$emit('ajaxStart');

                    promise.then(
                        function(response) {
                            $rootScope.$emit('ajaxFinish');
                        },
                        function(response) {
                            $rootScope.$emit('ajaxFinish');
                        }
                    );

                    return promise;
                };
            }]);
        }]);
}(window));