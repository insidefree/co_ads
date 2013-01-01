'use strict';

/* Settings Controller */
function SettingsCtrl($scope, $window, $http, Router, adUnit) {
    $scope.adUnit = adUnit;

    $scope.fontTypes = ['ARIAL', 'TIMES', 'VERDANA' ];

    $scope.fontSizes = ['SMALL', 'MEDIUM', 'LARGE'];

    $scope.$watch('adUnit', function(adUnit, oldAdUnit) {
        if (adUnit === oldAdUnit) {
            return;
        }
        $http.post(Router('saveAdUnit'), adUnit);
    }, true);

    $scope.authenticate = function() {
        $window.open(Router('authenticate'), 'authenticate', 'height=600, width=1000');
    }
}

SettingsCtrl.$inject = ['$scope', '$window', '$http', 'Router', 'adUnit'];

SettingsCtrl.resolve = {
    /**
     * resolves an ad unit so it will be available to the settings controller.
     */
    adUnit: ['$http', '$q', 'Router', function($http, $q,Router) {
        var dfd = $q.defer();

        $http.get(Router('getAdUnit')).success(function(response) {
            dfd.resolve(response);
        });

        return dfd.promise;
    }]
};