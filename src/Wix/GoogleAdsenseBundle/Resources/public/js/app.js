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
            var origComp = Wix.Utils.getOrigCompId();
            Wix.Data.Public.get("statusComp"+origComp,
                { scope:  'COMPONENT'},
                function(d) {
                    var key = ('statusComp' + origComp);
                    if( d[key] == 'blocked' ){
                        $('#block_settings').addClass('blocked');
                    }
                    else if ( d[key] == 'visible' ){
                        $('#block_settings').removeClass('blocked');
                    }
                },
                function(f) {
                    console.log(f);
                });
    }]);
}(window));