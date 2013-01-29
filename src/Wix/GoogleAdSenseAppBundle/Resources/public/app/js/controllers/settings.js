'use strict';

/* Settings Controller */
function SettingsCtrl($scope, $q, $window, $http, Router, WixSDK, QueryParams, adUnit, user, uiDialog) {
    /**
     * represents the ad unit model
     */
    $scope.adUnit = adUnit;

    /**
     * represents the user model
     */
    $scope.user = user;

    /**
     * returns true if the user has connected his account
     */
    $scope.connected = function() {
        return $scope.user.accountId !== null;
    };

    /**
     * available fonts to choose from
     */
    $scope.fontFamily = ['Arial', 'Times', 'Verdana' ];

    /**
     * available font sizes to choose from
     */
    $scope.fontSize = ['Small', 'Medium', 'Large'];

    /**
     * listens to changes on the ad unit model and sends a request to save it on the server
     */
    $scope.$watch('adUnit', function(adUnit, oldAdUnit) {
        if (adUnit === oldAdUnit) {
            return;
        }
        $http.post(Router('saveAdUnit'), adUnit)
            .success(function() {
                WixSDK.refreshAppByCompIds(QueryParams.origCompId);
            });
    }, true);

    /**
     * opens an authentication window
     */
    $scope.authenticate = function() {
        WixSDK.getSiteInfo(function(info) {
            var websiteUrl = (info || {}).baseUrl;

            if (websiteUrl === null || websiteUrl === undefined) {
                uiDialog.alert('/bundles/wixgoogleadsenseapp/app/partials/publish.html');
                return;
            }

            $window.open(Router('authenticate', { websiteUrl: websiteUrl }), 'authenticate', 'height=615, width=1000');
        });
    };

    /**
     * disconnects the user's account
     */
    $scope.disconnect = function() {
        $http.post(Router('disconnect')).success(function() {
            reload();
        });
    };

    /**
     * submits an ad to be active for this account
     */
    $scope.submit = function() {
        uiDialog.confirm('/bundles/wixgoogleadsenseapp/app/partials/note.html', {
            submit: function() {
                $http.post(Router('submit')).success(function() {
                    reload();
                });
            }
        });
    };

    /**
     * returns true if this user has an active ad unit
     */
    $scope.hasAdUnit = function() {
        return $scope.user.adUnitId !== null;
    };

    /**
     * updates the models to the newest data from the backend and refreshes the app
     */
    function reload() {
        var adUnit = $http.get(Router('getAdUnit')).success(function(response) {
                $scope.adUnit = response;
            }),
            user = $http.get(Router('getUser')).success(function(response) {
                $scope.user = response;
            });

        $q.all([adUnit, user]).then(function() {
            WixSDK.refreshAppByCompIds(QueryParams.origCompId);
        });
    }

    /**
     * export the reload to the window to make it available from outside source
     */
    $window.reload = reload;
}

/**
 * specifying concrete injections
 */
SettingsCtrl.$inject = ['$scope', '$q', '$window', '$http', 'Router', 'WixSDK', 'QueryParams', 'adUnit', 'user', 'uiDialog'];

/**
 * resolving promises
 */
SettingsCtrl.resolve = {
    /**
     * resolves an ad unit
     */
    adUnit: ['$http', '$q', 'Router', function($http, $q, Router) {
        var dfd = $q.defer();

        $http.get(Router('getAdUnit')).success(function(response) {
            dfd.resolve(response);
        });

        return dfd.promise;
    }],
    /**
     * resolves a user object
     */
    user: ['$http', '$q', 'Router', function($http, $q, Router) {
        var dfd = $q.defer();

        $http.get(Router('getUser')).success(function(response) {
            dfd.resolve(response);
        });

        return dfd.promise;
    }]
};