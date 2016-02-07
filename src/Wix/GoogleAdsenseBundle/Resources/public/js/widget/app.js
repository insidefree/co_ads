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

            console.log('WIDGET:getComponentInfo');
            Wix.getComponentInfo(function(page){
                console.log('WIDGET: before publish worker=>',page);
                /**
                 * when widget load trigger the worker to register comp
                 */
                Wix.PubSub.publish("WIDGET_LOAD", {page: page}, true);
            });


            /**
             * after register to worker, listen to answer from worker which status this comp
             */
            Wix.PubSub.subscribe("ALLOW_WIDGET", function(event){


                var is_mobile = false;
                // handle only my responses
                if(event.data.origin == myCompId){
                    // set if mobile
                    if(Wix.Utils.getDeviceType() == 'mobile' ||
                        screen.width < 500 ||
                        navigator.userAgent.match(/Android/i) ||
                        navigator.userAgent.match(/webOS/i) ||
                        navigator.userAgent.match(/iPhone/i) ||
                        navigator.userAgent.match(/iPod/i))  {
                        is_mobile = true;
                    }
                    console.log("WIDGET: ", myCompId, event);
                    $rootScope.liveSiteEmpty    = false;
                    // status options
                    var statusEnum      = {
                        'VISIBLE'  : 'visible',
                        'DELETED'  : 'deleted',
                        'BLOCKED'  : 'blocked'
                    };
                    Wix.Data.Public.set("statusComp"+myCompId,
                        event.data.status,
                        { scope: 'COMPONENT' },
                        function(d) {
                            console.log(d);
                        },
                        function(f) {
                            console.log(f);
                        });
                    // handle only components that not deleted
                    if(!window.component_deleted){
                        // case live site
                        if(Wix.Utils.getViewMode() !== 'editor' && Wix.Utils.getViewMode() !== 'preview'){
                            // status blocked - when there are more than 3 comp
                            if(event.data.status == statusEnum.BLOCKED){
                                $rootScope.liveSiteEmpty = true;
                                console.log("here: liveSiteEmpty");
                            }
                            // status visible and user connected adsense account
                            else if(window.code){
                                console.log("here: liveSiteCode");
                                $http.get(Router.url('ad')).success(function(data) {
                                    $('#liveSiteCode').append(data);
                                });
                            }
                            // status visible and account of google demo
                            else{
                                console.log("here: liveSiteDemo");
                                $('body').addClass('live_site_demo');
                                $http.get(Router.url('demo')).success(function(data) {
                                    var width;
                                    var height;
                                    if (is_mobile) {
                                        width  = data.mobile.regular.width;
                                        height = data.mobile.regular.height;
                                        $('body').addClass('mobile');
                                    }
                                    else{
                                        height  = data.height ? data.height : 250;
                                        width = data.width ? data.width : 300;
                                    }
                                    console.log('live site size=>',width,height);
                                    // client configuration
                                    //window.google_ad_client = 'ca-pub-8026931107919042';
                                    window.google_ad_slot = '';
                                    window.google_alternate_ad_url = 'http://www.wix.com/alternate/page/when/the/ad/cannot/be/displayed';
                                    window.google_page_url = data.domain ? data.domain : 'http://wix.com/';
                                    // width and height
                                    window.google_ad_height = height;
                                    window.google_ad_width  = width;
                                    window.google_ad_client = "pub-1786553880586297";
                                    window.google_ad_format = width+'x'+height+'_as';
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
                                    window.google_color_border  = data.adUnit.borderColor;
                                    window.google_color_bg      = data.adUnit.backgroundColor;
                                    window.google_color_link    = data.adUnit.titleColor;
                                    window.google_color_url     = data.adUnit.urlColor;
                                    window.google_color_text    = data.adUnit.textColor;
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

                                    var container = document.getElementById('adsense_container');
                                    var w = document.write;
                                    document.write = function (content) {
                                        container.innerHTML = content;
                                        document.write = w;
                                    };
                                    var script = document.createElement('script');
                                    script.type = 'text/javascript';
                                    script.src = 'http://pagead2.googlesyndication.com/pagead/show_ads.js';
                                    $('#adsense_container').append(script);
                                });
                            }
                        }
                        // case editor / preview
                        else{
                            if(is_mobile){
                                $('#editorDemo').addClass('mobile');
                                $('#editorBlocked').addClass('mobile');
                            }
                            else{
                                $('#editorDemo').removeClass('mobile');
                                $('#editorBlocked').removeClass('mobile');
                            }
                            $('#editorDemo').addClass('showDemo');
                            if(event.data.status == statusEnum.BLOCKED){
                                console.log("here: editorBlocked");
                                var data = '<div class="comp_limit_container"><div class="comp_limit_text">Sorry, Google does not allow more than 3 ads per page, so we recommend that you delete it.<br><br><span>Note: This message will not be visible in your site</span></div></div>';
                                $("#editorBlocked:not(:has(>div))").append(data);
                                $('body').addClass('blocked');
                            }
                            else if(event.data.status == statusEnum.VISIBLE){
                                console.log("here: editorDemo");
                                $( "#editorBlocked div" ).remove();
                                $('body').removeClass('blocked');
                            }
                        }
                    }
                }
            }, true);

            /**
             * handle components deleted
             */
            Wix.addEventListener(Wix.Events.COMPONENT_DELETED, function(){
                console.log("WIDGET: component deleted ");
                var deleteComponent =  $http({
                    method: 'DELETE',
                    url: Router.path('deleteComponent')
                });

                Wix.getComponentInfo(function(page){
                    // call worker to update that this component deleted
                    Wix.PubSub.publish("DELETED_WIDGET", {compId: Wix.Utils.getCompId(), page: page}, true);
                });

                return deleteComponent;
            });

            /**
             * handle user pagination
             */
            Wix.addEventListener(Wix.Events.PAGE_NAVIGATION, function(data){
                console.log("WIDGET: PAGE_NAVIGATION  ");
                // call worker to release comps, prefer all pages
                Wix.PubSub.publish("PAGE_NAVIGATION", {compId: Wix.Utils.getCompId(), eventData: data}, true);
            });

        }])

}(window));