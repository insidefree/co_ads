/* Services */
(function(window) {
    'use strict';

    window.angular.module('adSenseApp.services', [])
    /**
     * allows access to query string parameters.
     */
    .factory('QueryParams', ['$location', function($location) {
        var object = {},
            params = $location.$$absUrl.slice($location.$$absUrl.indexOf('?') + 1).split('&'),
            param;

        for(param in params) {
            object[params[param].split('=')[0]] = params[param].split('=')[1];
        }

        return object;
    }])
    /**
     * wix sdk servers as an api to communicate with wix's editor.
     */
    .factory('WixSDK', ['$window', function($window) {
        return $window.Wix;
    }])
    /**
     * serves as a router to generate routes to a symfony2 backend. appends the instance for every request if it's available.
     */
    .factory('Router', ['$window', 'QueryParams', function($window, QueryParams) {
        return function(name, opt_params, absolute) {
            var params = opt_params || {};

            params.instance = QueryParams.instance || null;
            params.compId = QueryParams.compId || null;
            params.origCompId = QueryParams.origCompId || null;

            return $window.Routing.generate(name, params, absolute);
        };
    }]);
}(window));
