/* Services */
(function(window) {
    'use strict';

    window.angular.module('AdsenseApp.services', [])
        /**
         * allows access to query string parameters.
         */
        .factory('QueryParams', ['$location', function($location) {
            var object = {},
                params = $location.$$absUrl.slice($location.$$absUrl.indexOf('?') + 1).split('&'),
                param;

            for(param in params) {
                if (params.hasOwnProperty(param)) {
                    object[params[param].split('=')[0]] = params[param].split('=')[1];
                }
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
        .factory('Router', ['$window', 'QueryParams', 'WixSDK', function($window, QueryParams, WixSDK) {
            return {
                path: function(name, params, absolute) {
                    params = params || {};

                    params.instance    = QueryParams.instance || null;
                    params.compId      = QueryParams.compId || null;
                    params.origCompId  = QueryParams.origCompId || null;
                    params.userId      =  WixSDK.Utils.getUid();
                    params.siteOwnerId =  WixSDK.Utils.getSiteOwnerId();

                    return $window.Routing.generate(name, params, absolute);
                },
                url: function(name, params) {
                    return this.path(name, params, true);
                }
            };
        }]);
}(window));
