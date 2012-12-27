'use strict';

/* Resources */
angular.module('adSenseApp.resources', ['ngResource'])
    .factory('Users', ['$resource', 'Router', function($resource, Router) {
        return $resource(
            Router('user')
        );
    }]);
