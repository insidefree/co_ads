(function(window) {
    'use strict';

    /* Settings Controller */
    window.SettingsCtrl = function($scope, $q, $window, $http, Router, WixSDK, QueryParams, adUnit, user, component, uiDialog, $timeout) {
        /**
         * represents the ad unit model
         */
        $scope.adUnit = adUnit;

        /**
         * represents the user model
         */
        $scope.user = user;

        /**
         * represents the component model
         */
        $scope.component = component;

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
         * set wix site owner id
         */
        $scope.siteOwnerId = WixSDK.Utils.getSiteOwnerId();

        /**
         * set wix user id
         */
        $scope.userId = WixSDK.Utils.getUid();

        $scope.diffUserIDOwnerId = ($scope.siteOwnerId !== $scope.userId);
        /**
         * show contributor section only on contributor site and when user click on btn
         */
        $scope.contributorSection = false;

        /**
         * close contributor section
         */
        $scope.closeContributor = function() {
            $scope.contributorSection = false;
        };

        /**
         * listens to contributor section, show only 8 sec.
         */
        $scope.$watch(function(){ return $scope.contributorSection}, function(newVal, oldVal) {

            if (newVal === oldVal) {
                return;
            }

            if(newVal) {
                $timeout(function () {
                    $scope.contributorSection = false;
                }, 8000);
            }

        }, true);


        /**
         * listens to changes on the ad unit model and sends a request to save it on the server
         */
        $scope.$watch('adUnit', function(adUnit, oldAdUnit) {
            if (adUnit === oldAdUnit) {
                return;
            }
            $http.post(Router.path('updateAdUnit'), adUnit)
                .success(function() {
                    WixSDK.Settings.refreshAppByCompIds([QueryParams.origCompId]);
                });
        }, true);

        $scope.$watch(function() {
            return $scope.adUnit.size;
        }, function(newValue, oldValue){
                if(newValue !== oldValue){
                    WixSDK.Settings.triggerSettingsUpdatedEvent({type: 1, size: newValue}, WixSDK.Utils.getOrigCompId());
                }
            }, true);

        /**
         * opens an authentication window
         */
        $scope.authenticate = function() {

            // check if contributor not allow to connect account
            if( $scope.userId !== $scope.siteOwnerId ) {
                return;
            }

            if (!$scope.websiteUrl) {
                uiDialog.alert('/bundles/wixgoogleadsense/partials/publish.html');
                return;
            }

            $window.open(Router.path('authenticate',
                { websiteUrl: $scope.websiteUrl }),
                'authenticate', 'height=615, width=1000');
        };

        /**
         * disconnects the user's account
         */
        $scope.disconnect = function() {

            // check if contributor not allow to disconnect account
            if( $scope.userId !== $scope.siteOwnerId ) {
                return;
            }

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
                        reload().then(function() {
                            uiDialog.alert('/bundles/wixgoogleadsense/partials/congratulation.html');
                        });
                    });
                }
            });
        };

        /**
         * returns true if this user has an active ad unit
         */
        $scope.hasAdUnit = function() {
            return !!$scope.component.ad_unit_id;
        };

        /**
         * updates the models to the newest data from the backend and refreshes the app
         */
        function reload() {
            var defer = $q.defer(),
                promise = defer.promise,
                adUnit = $http.get(Router.path('getAdUnit')).success(function(response) {
                    $scope.adUnit = response;
                }),
                user = $http.get(Router.path('getUser')).success(function(response) {
                    $scope.user = response;
                });

            $q.all([adUnit, user]).then(function() {
                WixSDK.Settings.refreshAppByCompIds([QueryParams.origCompId]);
                defer.resolve();
            });

            return promise;
        }

        /**
         * export the reload to the window to make it available from outside source
         */
        $window.reload = reload;
    };

    /**
     * specifying concrete injections
     */
    window.SettingsCtrl.$inject = ['$scope', '$q', '$window', '$http', 'Router', 'WixSDK', 'QueryParams', 'adUnit', 'user', 'component', 'uiDialog', '$timeout'];

    /**
     * resolving promises
     */
    window.SettingsCtrl.resolve = {
        /**
         * resolves an ad unit
         */
        adUnit: ['$http', '$q', 'Router', function($http, $q, Router) {
            return $http.get(Router.path('getAdUnit')).then(function(response) {
                return response.data;
            });
        }],
        /**
         * resolves a user object
         */
        user: ['$http', '$q', 'Router', function($http, $q, Router) {
            return $http.get(Router.path('getUser')).then(function(response) {
                return response.data;
            });
        }],
        /**
         * resolves a component object
         */
        component: ['$http', '$q', 'Router', function($http, $q, Router) {
            return $http.get(Router.path('getComponent')).then(function(response) {
                return response.data;
            });
        }]

    };
}(window));
