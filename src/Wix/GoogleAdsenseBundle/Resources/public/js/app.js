(function(window) {
    'use strict';

    window.angular.module('adSenseApp', ['adSenseApp.filters', 'adSenseApp.services', 'adSenseApp.directives', 'jQueryUI'])
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
    }]);
}(window));
