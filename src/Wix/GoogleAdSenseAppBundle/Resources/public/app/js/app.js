'use strict';

// Declare app level module which depends on filters, and services
angular.module('adSenseApp', ['adSenseApp.filters', 'adSenseApp.services', 'adSenseApp.directives', 'adSenseApp.resources', 'jQueryUI'])
    .config(['$routeProvider', function($routeProvider) {
        $routeProvider.when('/settings',{
            templateUrl: '/bundles/wixgoogleadsenseapp/app/partials/settings.html',
            controller: SettingsCtrl,
            resolve: SettingsCtrl.resolve
        });

        $routeProvider.otherwise({
            redirectTo: '/settings'
        });
  }]);
