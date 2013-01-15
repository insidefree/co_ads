'use strict';

/* Settings Controller */
function SettingsCtrl($scope, $q, $window, $http, Router, WixSDK, QueryParams, adUnit, user) {
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
    $scope.fontFamily = ['ARIAL', 'TIMES', 'VERDANA' ];

    /**
     * available font sizes to choose from
     */
    $scope.fontSize = ['SMALL', 'MEDIUM', 'LARGE'];

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
        $window.open(Router('authenticate'), 'authenticate', 'height=600, width=1000');
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
        $http.post(Router('submit')).success(function() {
            reload();
        });
    };

    /**
     * returns true if this user has an active ad unit
     */
    $scope.hasAdUnit = function() {
        return $scope.user.adUnitId !== null;
    };

    /**
     * updates the models to the newest data from the backend.
     */
    function reload() {
        $http.get(Router('getAdUnit')).success(function(response) {
            $scope.adUnit = response;
        });

        $http.get(Router('getUser')).success(function(response) {
            $scope.user = response;
        });
    }

    $window.reload = reload;
}

/**
 * specifying concrete injections
 */
SettingsCtrl.$inject = ['$scope', '$q', '$window', '$http', 'Router', 'WixSDK', 'QueryParams', 'adUnit', 'user'];

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