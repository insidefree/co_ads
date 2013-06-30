(function(window) {
    'use strict';

    /* Settings Controller */
    window.SettingsCtrl = function($scope, $q, $window, $http, Router, WixSDK, QueryParams, adUnit, user, uiDialog) {
        /**
         * represents the ad unit model
         */
        $scope.adUnit = adUnit;

        /**
         * represents the user model
         */
        $scope.user = user;

        /**
         * available fonts to choose from
         */
        $scope.fontFamily = ['Arial', 'Times', 'Verdana'];

        /**
         * available font sizes to choose from
         */
        $scope.fontSize = ['Small', 'Medium', 'Large'];

        /**
         * set the website url when it's available on the scope
         */
        WixSDK.getSiteInfo(function(info) {
            $scope.websiteUrl = (info || {}).baseUrl;
        });

        /**
         * returns true if the user has connected his account
         */
        $scope.connected = function() {
            return !!$scope.user.account_id;
        };

        /**
         * listens to changes on the ad unit model and sends a request to save it on the server
         */
        $scope.$watch('adUnit', function(adUnit, oldAdUnit) {
            if (adUnit === oldAdUnit) {
                return;
            }
            $http.post(Router.path('saveAdUnit'), adUnit)
                .success(function() {
                    WixSDK.refreshAppByCompIds(QueryParams.origCompId);
                });
        }, true);

        /**
         * opens an authentication window
         */
        $scope.authenticate = function() {
            if (!$scope.websiteUrl) {
                uiDialog.alert('/bundles/wixgoogleadsense/partials/publish.html');
                return;
            }

            $window.open(Router.path('authenticate', { websiteUrl: $scope.websiteUrl }), 'authenticate', 'height=615, width=1000');
        };

        /**
         * disconnects the user's account
         */
        $scope.disconnect = function() {
            $http.post(Router.path('disconnect')).success(function() {
                reload();
            });
        };

        /**
         * submits an ad to be active for this account
         */
        $scope.submit = function() {
            uiDialog.confirm('/bundles/wixgoogleadsense/partials/note.html', {
                submit: function() {
                    $http.post(Router.path('submit')).success(function() {
                        reload();
                    });
                }
            });
        };

        /**
         * returns true if this user has an active ad unit
         */
        $scope.hasAdUnit = function() {
            return !!$scope.user.ad_unit_id;
        };

        /**
         * updates the models to the newest data from the backend and refreshes the app
         */
        function reload() {
            var adUnit = $http.get(Router.path('getAdUnit')).success(function(response) {
                    $scope.adUnit = response;
                }),
                user = $http.get(Router.path('getUser')).success(function(response) {
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
    };

    /**
     * specifying concrete injections
     */
    window.SettingsCtrl.$inject = ['$scope', '$q', '$window', '$http', 'Router', 'WixSDK', 'QueryParams', 'adUnit', 'user', 'uiDialog'];

    /**
     * resolving promises
     */
    window.SettingsCtrl.resolve = {
        /**
         * resolves an ad unit
         */
        adUnit: ['$http', '$q', 'Router', function($http, $q, Router) {
            return $http.get(Router.path('getAdUnit')).success(function(response) {
                return response.data;
            });
        }],
        /**
         * resolves a user object
         */
        user: ['$http', '$q', 'Router', function($http, $q, Router) {
            return $http.get(Router.path('getUser')).success(function(response) {
                return response.data;
            });
        }]
    };
}(window));
