'use strict';

(function (angular) {
    angular.module('adsenseWidget')
        .controller('layoutCtrl', ['$scope', 'wixService', '$q', '$http', 'Router',
            function($scope, wixService, $q, $http, Router){

            var myCompId            = wixService.getCompId();
            var is_mobile           = false;
            var $body               = $('body');
            var $editorDemo         = $('#editorDemo');
            var $editorBlocked      = $('#editorBlocked');
            var $liveSiteCode       = $('#liveSiteCode');
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

            wixService.addEventListener('SETTINGS_UPDATED', _settingUpdateHandler);

            function _settingUpdateHandler (oData) {
                $scope.$apply(function () {
                    switch (oData.type) {
                        // Layout widget updates
                        case 1:
                            var dim = oData.size == 'SIZE_120_600' ? {width: 150} : (oData.size == 'SIZE_300_250' ? {width: 360} : {width: 500});

                            wixService.resizeComponent(dim);
                            break;

                    }
                });
            }

            /**
             * When widget load trigger the Worker to register comp
             */
            wixService.getComponentInfoWithAppPageId()
                .then(function(componentInfo){
                    if(!componentInfo){
                        return;
                    }
                    // trigger worker
                    Wix.PubSub.publish('WIDGET_LOAD', {componentInfo: componentInfo}, true);
                });

            /**
             * After register comp, listen to answer from Worker with status of comp
             */
            Wix.PubSub.subscribe('ALLOW_WIDGET', function(event){
                // handle only my responses
                if(event.data.origin !== myCompId) {
                    return;
                }
                is_mobile = isMobile();
                // In editor view mode, set status of comp in wix data for using the settings
                if(wixService.getViewMode() === viewModeEnum.EDITOR ) {
                    wixService.setPublicData('statusComp' + myCompId,
                        event.data.status, {scope: 'COMPONENT'}, function (d) {}, function (f) {});
                }
                // case live site
                if(wixService.getViewMode() !== viewModeEnum.EDITOR && wixService.getViewMode() !== viewModeEnum.PREVIEW){
                    // status blocked - when there are more than 3 comp on page
                    if(event.data.status == statusEnum.BLOCKED){
                        $body.removeClass('live_site_demo')
                             .addClass('live_site_empty');
                    }
                    // status visible and user connected adsense account
                    else if(window.code){
                        $http.get(Router.url('ad')).success(function(data) {
                            $body.removeClass('live_site_empty');
                            var containerId = 'liveSiteCode';
                            loadLiveSiteCode(data, containerId, is_mobile);
                        });
                    }
                    // status visible and user is not connected, connected to Wix account
                    else{
                        $body.removeClass('live_site_empty')
                             .addClass('live_site_demo');
                        $http.get(Router.url('demo')).success(function(data) {
                            loadLiveSiteDemo(data, adsenseContainerId, is_mobile);
                        });
                    }
                }
                // case editor/preview mode
                else{
                    if(is_mobile){
                        $editorDemo.addClass('mobile');
                        $editorBlocked.addClass('mobile');
                        // jQuery(function() {
                        //     wixService.setHeight( $body.height() + 15 );
                        // });
                        $http.get(Router.url('demo')).success(function(data) {
                            var dim = data.adUnit.size == 'SIZE_120_600' ? {width: 60, height: 300} : 
                                (data.adUnit.size == 'SIZE_300_250' ? {width: 280, height: 236} : {width: 280, height: 36});
                            wixService.setHeight(dim.height);
                        });
                    }
                    else{
                        $editorDemo.removeClass('mobile');
                        $editorBlocked.removeClass('mobile');
                    }
                    $editorDemo.addClass('showDemo');
                    if(event.data.status == statusEnum.BLOCKED){
                        $body.addClass('blocked');
                    }
                    else if(event.data.status == statusEnum.VISIBLE){
                        $body.removeClass('blocked');
                    }
                }

            }, true);

            /**
             * Delete a component data.
             * @return {{method}}
             * @private
             */
            function _deleteComponentData() {
                return $http.delete(Router.url('deleteComponent'));
            }

            /**
             * Handle component deleted
             */
            wixService.addEventListener(Wix.Events.COMPONENT_DELETED, function(){
                _deleteComponentData();
                // When component deleted, trigger the Worker to update status of comp.
                wixService.getComponentInfoWithAppPageId()
                    .then(function(componentInfo){
                        if(!componentInfo){
                            return;
                        }
                        Wix.PubSub.publish('DELETED_WIDGET', {componentInfo: componentInfo}, true);
                    });
            });

            /**
             * Handle user navigate pages
             */
            wixService.addEventListener(Wix.Events.PAGE_NAVIGATION, function(data){
                // When user navigate pages, trigger the Worker to release comps, prefer comps all pages.
                Wix.PubSub.publish('PAGE_NAVIGATION', {compId: wixService.getCompId(), eventData: data}, true);
            });

            /**
             * Load google ads connected to wix account demo
             * @param data
             * @param containerId
             * @param is_mobile
             */
            function loadLiveSiteDemo(data, containerId, is_mobile){
                var width;
                var height;
                if (is_mobile) {
                    var dim = data.adUnit.size == 'SIZE_120_600' ? {width: 60, height: 300} : 
                        (data.adUnit.size == 'SIZE_300_250' ? {width: 280, height: 236} : {width: 280, height: 36});
                    wixService.setHeight(dim.height);
                    if (data.adUnit.size == 'SIZE_120_600') {
                        width = 60;
                        height = 300;
                    }
                    else if (data.adUnit.size == 'SIZE_300_250') {
                        width = 280; 
                        height = 236;
                    } 
                    else {
                        width = 280;
                        height = 36;
                    };
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
                window.google_ad_client = 'pub-1786553880586297';
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
                script.src = 'https://pagead2.googlesyndication.com/pagead/show_ads.js';
                $(container).append(script);
            }

            /**
             * Load google ads connected to user account
             * @param data
             * @param containerId
             * @param is_mobile
             */
            function loadLiveSiteCode(data, containerId, is_mobile){
                if(is_mobile) {
                    var dim = data.adUnit.size == 'SIZE_120_600' ? {width: 60, height: 300} : 
                        (data.adUnit.size == 'SIZE_300_250' ? {width: 280, height: 236} : {width: 280, height: 36});
                    wixService.setHeight(dim.height);
                    $body.addClass('mobile');
                }
                window.google_page_url = data.domain;
                var w = document.write;
                var container = document.getElementById(containerId);
                document.write = function (content) {
                    container.innerHTML = content;
                    document.write = w;
                };
                $(container).append(data.code);
                wixService.applicationLoadingStep(1, '');
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
