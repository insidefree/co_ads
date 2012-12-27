'use strict';

/* Controllers */
function SettingsCtrl($scope, $window, Router, user) {
    $scope.user = user;

    $scope.authenticate = function() {
        $window.open(Router('authenticate'), 'authenticate', 'height=600, width=600');
    }
}

SettingsCtrl.$inject = ['$scope', '$window', 'Router'];

SettingsCtrl.resolve = {
    user: ['Users', function(Users) {
        Users.get();
    }]
};