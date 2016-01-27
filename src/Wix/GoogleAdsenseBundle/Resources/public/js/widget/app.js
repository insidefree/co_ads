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

        .factory("patchPageId", ['$http', 'Router', '$timeout', '$window',
            function($http, Router, $timeout, $window){

                function patch() {
                    Wix.getCurrentPageId(function(pageId) {
                        return $http({
                            method: 'PATCH',
                            url: Router.path('patchPageId'),
                            data: angular.toJson({page_id: pageId})
                        }).success(function(){

                            //$timeout(function(){
                            //    $window.location.reload();
                            //}, 4000);

                        }).error(function(){

                        });
                    });
                }

                return {
                    patch: patch
                }
            }])

        .factory("patchUpdatedDate", ['$http', 'Router',
            function($http, Router){

                function patch () {
                    return $http({
                        method: 'PATCH',
                        url: Router.path('patchUpdatedDate'),
                        data : angular.toJson({updated_date: new Date()})
                    })
                }

                return {
                    patch: patch
                }
        }])
        .run(['$rootScope', '$http', '$q', 'Router', 'patchUpdatedDate', 'patchPageId', function ($rootScope, $http, $q, Router, patchUpdatedDate, patchPageId) {

            $http.get(Router.path('getComponent')).then(function (response) {
                response = response.data || {};
                if ( !response.hasOwnProperty('page_id') ) {
                    patchPageId.patch();
                }

                if( !response.hasOwnProperty('updated_date') ){
                    patchUpdatedDate.patch();
                }
            });

            var myCompId = Wix.Utils.getCompId();
            console.log("WIDGET: now run publish");
            Wix.PubSub.publish("WIDGET_LOAD", {value: "this is my message"}, true);

            Wix.PubSub.subscribe("ALLOW_WIDGET", function(event){
                if(event.data.origin == myCompId){
                    console.log("WIDGET: ", myCompId, event);
                    $rootScope.messageWorker    = event.data.data;
                    $rootScope.countWorker      = event.data.count;
                    $rootScope.liveSiteEmpty    = false;
                    $rootScope.widget_status    = '';
                    var statusEnum      = {
                        'VISIBLE'  : 'visible',
                        'DELETED'  : 'deleted',
                        'BLOCKED'  : 'blocked'
                    };

                    if(!window.component_deleted){
                        if(Wix.Utils.getViewMode() !== 'editor' && Wix.Utils.getViewMode() !== 'preview'){
                            if(event.data.status == statusEnum.BLOCKED){
                                $rootScope.liveSiteEmpty = true;
                                $rootScope.widget_status = statusEnum.BLOCKED;
                                console.log("here: liveSiteEmpty");
                            }
                            else if(window.code){
                                console.log("here: liveSiteCode");
                                $rootScope.widget_status = statusEnum.VISIBLE;
                                $http.get(Router.url('ad')).success(function(data) {
                                    $('#liveSiteCode').append(data);
                                });
                            }
                            else{
                                console.log("here: liveSiteDemo");
                                $rootScope.widget_status = statusEnum.VISIBLE;
                                $http.get(Router.url('demo')).success(function(data) {
                                    // client configuration
                                    window.google_ad_client = 'ca-pub-8026931107919042';
                                    window.google_ad_slot = '';
                                    window.google_alternate_ad_url = 'http://www.wix.com/alternate/page/when/the/ad/cannot/be/displayed';
                                    window.google_page_url = data.domain ? data.domain : 'http://wix.com/';
                                    // width and height
                                    //window.google_ad_width = data.adUnit.width;
                                    //window.google_ad_height = data.adUnit.height;
                                    window.google_ad_format = data.adUnit.width+'x'+data.adUnit.height+'_as';
                                    window.google_ad_client = "pub-1786553880586297";
                                    window.google_ad_width  = data.adUnit.width ? data.adUnit.width : 300;
                                    window.google_ad_height = data.adUnit.height ? data.adUnit.height : 250;
                                    // type of ad
                                    if(data.adUnit.type != 'IMAGE'){
                                        window.google_ad_type = 'text';
                                    }
                                    else{
                                        window.google_ad_type = 'image';
                                    }
                                    // font style
                                    window.google_font_face = data.adUnit.fontFamily;
                                    window.google_font_size = 'medium';
                                    // colors
                                    window.google_color_border = data.adUnit.borderColor;
                                    window.google_color_bg = data.adUnit.backgroundColor;
                                    window.google_color_link = data.adUnit.titleColor;
                                    window.google_color_url = data.adUnit.urlColor;
                                    window.google_color_text = data.adUnit.textColor;
                                    // corners
                                    if(data.adUnit.cornerStyle == 'SQUARE'){
                                        window.google_ui_features = 'rc:4';
                                    }
                                    else if(data.adUnit.cornerStyle == 'SLIGHTLY_ROUNDED'){
                                        window.google_ui_features = 'rc:6';
                                    }
                                    else if(data.adUnit.cornerStyle == 'VERY_ROUNDED'){
                                        window.google_ui_features = 'rc:10';
                                    }


                                    var container = document.getElementById('container');
                                    var w = document.write;
                                    document.write = function (content) {
                                        container.innerHTML = content;
                                        document.write = w;
                                    };

                                    var script = document.createElement('script');
                                    script.type = 'text/javascript';
                                    script.src = 'http://pagead2.googlesyndication.com/pagead/show_ads.js';
                                    $('#container').append(script);
                                });
                            }
                        }
                        else{
                            if(event.data.status == statusEnum.BLOCKED){
                                console.log("here: editorBlocked");
                                var data = '<div class="comp_limit_container"><div class="comp_limit_text">Sorry, Google does not allow more than 3 ads per page, so we recommend that you delete it.<br><br><span>Note: This message will not be visible in your site</span></div></div>';
                                $('#editorBlocked').append(data);
                                $rootScope.widget_status = statusEnum.BLOCKED;
                            }
                            else{
                                console.log("here: editorDemo");
                                $rootScope.widget_status = statusEnum.VISIBLE;
                                $http.get(Router.url('placeholder')).success(function(data) {
                                    $( "#editorBlocked" ).remove( "div" );
                                    $('#editorDemo').append($(data));
                                });
                                console.log("after");
                            }
                        }
                    }
                }
            }, true);

            Wix.addEventListener(Wix.Events.COMPONENT_DELETED, function(){
                var deleteComponent =  $http({
                    method: 'DELETE',
                    url: Router.path('deleteComponent')
                });

                Wix.PubSub.publish("DELETED_WIDGET", {compId: Wix.Utils.getCompId()}, true);

                return deleteComponent;
            });

            Wix.addEventListener(Wix.Events.PAGE_NAVIGATION, function(data){
                Wix.PubSub.publish("PAGE_NAVIGATION", {compId: Wix.Utils.getCompId(), eventData: data}, true);
            });

            //Wix.addEventListener(Wix.Events.SETTINGS_UPDATED, function(data){
            //
            //window.postMessage("hello", "http://adsense.apps.wix.com");
            ////{widget_status: $rootScope.widget_status, origin: myCompId}
            //
            //
            //});

            //console.log("WIDGET: now subscribe SETTINGS_LOAD");
            //Wix.PubSub.subscribe("SETTINGS_LOAD", function(event){
            //    console.log(event);
            //});
        }])

}(window));