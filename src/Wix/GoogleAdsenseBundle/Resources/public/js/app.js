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


        //window.addEventListener('message', function(e) {
        //    console.log("message=>", JSON.parse(e.data));
        //    //if(e.data.origin == origCompId){
        //    //    $rootScope.widget_status = e.widget_status;
        //    //    console.log("SETTINGS: ",$rootScope.widget_status);
        //    //}
        //}, false);
        //Wix.Settings.triggerSettingsUpdatedEvent("my message", origCompId);
        //console.log('SETTINGS: publish to widget');
        //Wix.PubSub.publish("SETTINGS_LOAD", {value: "this is my message"}, true);


    }]);
}(window));