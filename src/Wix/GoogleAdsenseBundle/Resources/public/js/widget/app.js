(function (window, undefined) {

    angular.module('adsenseWidget', [])
        .factory('QueryParams', function () {
            // the return value is assigned to QueryString!
            var query_string = {};
            var query = window.location.search.substring(1);
            var vars = query.split("&");
            for (var i=0;i<vars.length;i++) {
                var pair = vars[i].split("=");
                // If first entry with this name
                if (typeof query_string[pair[0]] === "undefined") {
                    query_string[pair[0]] = pair[1];
                    // If second entry with this name
                } else if (typeof query_string[pair[0]] === "string") {
                    query_string[pair[0]] = [ query_string[pair[0]], pair[1] ];
                    // If third or later entry with this name
                } else {
                    query_string[pair[0]].push(pair[1]);
                }
            }
            return query_string;
        })
    /**
     * serves as a router to generate routes to a symfony2 backend. appends the instance for every request if it's available.
     */
        .factory('Router', ['$window', 'QueryParams', function ($window, QueryParams) {
            return {
                path: function (name, params, absolute) {
                    params = params || {};

                    params.instance = QueryParams.instance || null;
                    params.compId = QueryParams.compId || null;
                    params.origCompId = QueryParams.origCompId || null;

                    return $window.Routing.generate(name, params, absolute);
                },
                url: function (name, params) {
                    return this.path(name, params, true);
                }
            };
        }])
        .run(['$http', '$q', 'Router', '$timeout', '$window', function ($http, $q, Router, $timeout, $window) {

            $http.get(Router.path('getComponent')).then(function (response) {
                response = response.data || {};
                if ( !response.hasOwnProperty('page_id') ) {
                    Wix.getCurrentPageId(function(pageId) {
                        $http({
                            method: 'PATCH',
                            url: Router.path('patchPageId'),
                            data: angular.toJson({page_id: pageId})
                        }).success(function(){

                            $timeout(function(){
                                $window.location.reload();
                            }, 4000);

                        }).error(function(){

                        });
                    });

                }
            });

            Wix.addEventListener(Wix.Events.COMPONENT_DELETED, function(){
                return $http({
                    method: 'DELETE',
                    url: Router.path('deleteComponent')
                });
            });

        }])


}(window));