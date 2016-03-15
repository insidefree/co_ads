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
            var origComp        = Wix.Utils.getOrigCompId();
            var $blockSettings  = $('#block_settings');

            Wix.Data.Public.get('statusComp'+origComp,
                { scope:  'COMPONENT'},
                function(d) {
                    var key = 'statusComp' + origComp;
                    if( d[key] == 'blocked' ){
                        $blockSettings.addClass('blocked');
                    }
                    else if ( d[key] == 'visible' ){
                        $blockSettings.removeClass('blocked');
                    }
                },
                function(f) {
                });
    }]);
}(window));