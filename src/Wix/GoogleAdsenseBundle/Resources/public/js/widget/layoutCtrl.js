'use strict';

(function (angular) {
    angular.module('adsenseWidget')
        .controller('layoutCtrl', ['$scope', 'wixService', '$q', '$http', 'Router', function($scope, wixService, $q, $http, Router){

            var myCompId            = wixService.getCompId();
            var is_mobile           = false;
            var $body               = $('body');
            var $editorDemo         = $('#editorDemo');
            var $editorBlocked      = $('#editorBlocked');
            var adsenseContainerId  = 'adsense_container';
            // view mode options
            var viewModeEnum = {
                EDITOR   : 'editor',
                PREVIEW  : 'preview',
                MOBILE   : 'mobile'
            };
            // status options
            var statusEnum = {
                VISIBLE  : 'visible',
                DELETED  : 'deleted',
                BLOCKED  : 'blocked'
            };

            /**
             * When widget load trigger the Worker to register comp
             */
            wixService.getComponentInfoWithAppPageId()
                .then(function(componentInfo){
                    console.log('getComponentInfo=>',componentInfo);
                    if(!componentInfo){
                        return;
                    }
                    // trigger worker
                    Wix.PubSub.publish("WIDGET_LOAD", {componentInfo: componentInfo}, true);
                });

            /**
             * After register comp, listen to answer from Worker with status of comp
             */
            Wix.PubSub.subscribe("ALLOW_WIDGET", function(event){
                // handle only my responses
                if(event.data.origin !== myCompId) {
                    return;
                }
                is_mobile = isMobile();
                console.log("WIDGET: ", myCompId, event);
                // In editor view mode, set status of comp in wix data for using the settings
                if(wixService.getViewMode() === viewModeEnum.EDITOR ) {
                    wixService.setPublicData("statusComp" + myCompId,
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
                if(wixService.getViewMode() !== viewModeEnum.EDITOR && wixService.getViewMode() !== viewModeEnum.PREVIEW){
                    // status blocked - when there are more than 3 comp on page
                    if(event.data.status == statusEnum.BLOCKED){
                        console.log("here: liveSiteEmpty");
                        $body.removeClass('live_site_demo')
                             .addClass('live_site_empty');
                    }
                    // status visible and user connected adsense account
                    else if(window.code){
                        console.log("here: liveSiteCode");
                        $http.get(Router.url('ad')).success(function(data) {
                            $body.removeClass('live_site_empty');
                            $('#liveSiteCode').append(data);
                        });
                    }
                    // status visible and user is not connected, connected to Wix account
                    else{
                        console.log("here: liveSiteDemo");
                        $body.removeClass('live_site_empty')
                             .addClass('live_site_demo');
                        $http.get(Router.url('demo')).success(function(data) {
                            loadLiveSiteDemo(data, adsenseContainerId);
                        });
                    }
                }
                // case editor/preview mode
                else{
                    if(is_mobile){
                        $editorDemo.addClass('mobile');
                        $editorBlocked.addClass('mobile');
                        jQuery(function() {
                            wixService.setHeight( jQuery('body').height() + 15 );
                        });
                    }
                    else{
                        $editorDemo.removeClass('mobile');
                        $editorBlocked.removeClass('mobile');
                    }
                    $editorDemo.addClass('showDemo');
                    if(event.data.status == statusEnum.BLOCKED){
                        console.log("here: editorBlocked");
                        $body.addClass('blocked');
                    }
                    else if(event.data.status == statusEnum.VISIBLE){
                        console.log("here: editorDemo");
                        $body.removeClass('blocked');
                    }
                }

            }, true);

            /**
             * Handle component deleted
             */
            wixService.addEventListener(Wix.Events.COMPONENT_DELETED, function(){
                console.log("=============================================WIDGET: component deleted ");
                var deleteComponent =  $http({
                    method: 'DELETE',
                    url: Router.path('deleteComponent')
                });

                // When component deleted, trigger the Worker to update status of comp.
                wixService.getComponentInfoWithAppPageId()
                    .then(function(componentInfo){
                        console.log('getComponentInfo=>',componentInfo);
                        if(!componentInfo){
                            return;
                        }
                        Wix.PubSub.publish("DELETED_WIDGET", {componentInfo: componentInfo}, true);
                    });
                return deleteComponent;
            });

            /**
             * Handle user navigate pages
             */
            wixService.addEventListener(Wix.Events.PAGE_NAVIGATION, function(data){
                console.log("WIDGET: PAGE_NAVIGATION  ");
                // When user navigate pages, trigger the Worker to release comps, prefer comps all pages.
                Wix.PubSub.publish("PAGE_NAVIGATION", {compId: wixService.getCompId(), eventData: data}, true);
            });

            /**
             * Load google ads connected to wix account demo
             * @param data
             * @param containerId
             */
            function loadLiveSiteDemo(data, containerId){
                var width;
                var height;
                if (is_mobile) {
                    width  = data.mobile.regular.width;
                    height = data.mobile.regular.height;
                    $body.addClass('mobile');
                }
                else{
                    height  = data.height ? data.height : 250;
                    width = data.width ? data.width : 300;
                }
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

                var w = document.write;
                var container = document.getElementById(containerId);
                document.write = function (content) {
                    container.innerHTML = content;
                    document.write = w;
                };
                var script = document.createElement('script');
                script.type = 'text/javascript';
                script.src = 'http://pagead2.googlesyndication.com/pagead/show_ads.js';
                container.append(script);
            }

            /**
             * Check is mobile
             * @returns {boolean}
             */
            function isMobile(){
                return wixService.getDeviceType() == viewModeEnum.MOBILE ||
                        screen.width < 500 ||
                        navigator.userAgent.match(/Android/i) ||
                        navigator.userAgent.match(/webOS/i) ||
                        navigator.userAgent.match(/iPhone/i) ||
                        navigator.userAgent.match(/iPod/i)
            }
        }]);
})(angular);
