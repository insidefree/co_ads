'use strict';

/* Settings Controller */
function SettingsCtrl($scope, $window, Router, adUnit) {
    $scope.adUnit = adUnit;

    $scope.authenticate = function() {
        $window.open(Router('authenticate'), 'authenticate', 'height=600, width=1000');
    }
}

SettingsCtrl.$inject = ['$scope', '$window', 'Router', 'adUnit'];

SettingsCtrl.resolve = {
    /**
     * resolves an ad unit so it will be available to the settings controller.
     */
    adUnit: ['$http', '$q', 'Router', function($http, $q,Router) {
        var dfd = $q.defer();

        $http.get(Router('adunit')).success(function(response) {
            dfd.resolve(response);
        });

        return dfd.promise;
    }]
};