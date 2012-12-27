'use strict';

/* Settings Controller */
function SettingsCtrl($scope, $window, Router, user, $http) {
    $scope.user = user;

    $scope.$watch('user', function(newValue, oldValue) {
        if (newValue === oldValue) {
            return;
        }
        $scope.user.$save();
    });

    $scope.authenticate = function() {
        $window.open(Router('authenticate'), 'authenticate', 'height=600, width=600');
    }
}

SettingsCtrl.$inject = ['$scope', '$window', 'Router', 'user', '$http'];

SettingsCtrl.resolve = {
    user: ['$q', 'Users', function($q, Users) {
        var defer = $q.defer();

        Users.get({}, function(user) {
            defer.resolve(user);
        });

        return defer.promise;
    }]
};