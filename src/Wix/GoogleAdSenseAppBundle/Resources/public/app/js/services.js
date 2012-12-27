'use strict';

/* Services */
angular.module('adSenseApp.services', [])
    /**
     * allows access to query string parameters.
     */
    .factory('QueryParams', ['$location', function($location) {
        var object = {};

        var params = $location.$$absUrl.slice($location.$$absUrl.indexOf('?') + 1).split('&');

        for(var param in params) {
            object[params[param].split('=')[0]] = params[param].split('=')[1];
        }

        return object;
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
