
(function (window) {
    angular.module('adsenseWidget')
        .controller('layoutCtrl', '$scope', 'wixService', '$q', '$http', 'Router', function($scope, wixService, $q, $http, Router){

            var myCompId = wixService.getCompId();

            wixService.getComponentInfo()
                .then(function(componentInfo){
                    if (!componentInfo) {
                        return $q.reject();
                    }
                    return $q.all([wixService.getCurrentPageId(), componentInfo]);
                })
                .then(function(values){
                    var pageId              = values[0];
                    var componentInfo       = values[1];
                    componentInfo.appPageId = pageId;
                    console.log('getComponentInfo=>',componentInfo);
                    /**
                     * when widget load trigger the worker to register comp
                     */
                    Wix.PubSub.publish("WIDGET_LOAD", {componentInfo: componentInfo}, true);
                });


            /**
             * after register to worker, listen to answer from worker which status this comp
             */
            Wix.PubSub.subscribe("ALLOW_WIDGET", function(event){


                var is_mobile = false;
                // handle only my responses
                if(event.data.origin == myCompId){
                    // set if mobile
                    if(wixService.getDeviceType() == 'mobile' ||
                        screen.width < 500 ||
                        navigator.userAgent.match(/Android/i) ||
                        navigator.userAgent.match(/webOS/i) ||
                        navigator.userAgent.match(/iPhone/i) ||
                        navigator.userAgent.match(/iPod/i))  {
                        is_mobile = true;
                    }
                    console.log("WIDGET: ", myCompId, event);
                    // status options
                    var statusEnum      = {
                        'VISIBLE'  : 'visible',
                        'DELETED'  : 'deleted',
                        'BLOCKED'  : 'blocked'
                    };
                    if(wixService.getViewMode() === 'editor' ) {
                        Wix.Data.Public.set("statusComp" + myCompId,
                            event.data.status,
                            {scope: 'COMPONENT'},
                            function (d) {
                                console.log('widget', d, event.data.status);
                            },
                            function (f) {
                                console.log(f);
                            });
                    }
                    // case live site
                    if(wixService.getViewMode() !== 'editor' && wixService.getViewMode() !== 'preview'){
                        // status blocked - when there are more than 3 comp
                        if(event.data.status == statusEnum.BLOCKED){
                            console.log("here: liveSiteEmpty");
                            $('body').removeClass('live_site_demo');
                            $('body').addClass('live_site_empty');
                        }
                        // status visible and user connected adsense account
                        else if(window.code){
                            console.log("here: liveSiteCode");
                            $http.get(Router.url('ad')).success(function(data) {
                                $('body').removeClass('live_site_empty');
                                $('#liveSiteCode').append(data);
                            });
                        }
                        // status visible and account of google demo
                        else{
                            console.log("here: liveSiteDemo");
                            $('body').removeClass('live_site_empty');
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
                            jQuery(function() {
                                wixService.setHeight( jQuery('body').height() + 15 );
                            });
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
            }, true);

            /**
             * handle components deleted
             */
            wixService.addEventListener(Wix.Events.COMPONENT_DELETED, function(){
                console.log("=============================================WIDGET: component deleted ");
                var deleteComponent =  $http({
                    method: 'DELETE',
                    url: Router.path('deleteComponent')
                });

                wixService.getComponentInfo()
                    .then(function(componentInfo){
                        if (!componentInfo) {
                            return $q.reject();
                        }
                        return $q.all([wixService.getCurrentPageId(), componentInfo]);
                    })
                    .then(function(values){
                        var pageId = values[0];
                        var componentInfo = values[1];
                        componentInfo.appPageId = pageId;
                        console.log('getComponentInfo=>',componentInfo);
                        // call worker to update that this component deleted
                        console.log("****************************************WIDGET: component deleted ");
                        Wix.PubSub.publish("DELETED_WIDGET", {componentInfo: componentInfo}, true);
                    });

                return deleteComponent;
            });

            /**
             * handle user navigate pages
             */
            wixService.addEventListener(Wix.Events.PAGE_NAVIGATION, function(data){
                console.log("WIDGET: PAGE_NAVIGATION  ");
                // call worker to release comps, prefer all pages
                Wix.PubSub.publish("PAGE_NAVIGATION", {compId: wixService.getCompId(), eventData: data}, true);
            });

        });
})();
