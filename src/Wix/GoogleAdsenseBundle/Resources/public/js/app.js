(function(window) {
    'use strict';

    window.angular.module('AdsenseApp', ['AdsenseApp.filters', 'AdsenseApp.services', 'AdsenseApp.directives', 'jQueryUI'])
    /**
     * config
     */
    .config(['$routeProvider', function($routeProvider) {
        $routeProvider.when('/', {
            templateUrl: '/bundles/wixgoogleadsense/partials/settings.html',
            controller: window.SettingsCtrl,
            resolve: window.SettingsCtrl.resolve
        });

        $routeProvider.otherwise({
            redirectTo: '/'
        });
    }])
    .run(['$rootScope', '$http', '$q', function ($rootScope, $http, $q) {
        console.log("SETTINGS before load");
        var origCompId = Wix.Utils.getOrigCompId();
    }]);
}(window));