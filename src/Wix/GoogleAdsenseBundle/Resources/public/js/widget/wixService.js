'use strict';

(function () {

    angular.module('adsenseWidget')
        .service('wixService', ['$q', '$rootScope', function ($q, $rootScope) {

        //==============================
        //   FUNCTION DECLARATIONS     =
        //==============================

        /**
         * Return obj with all component info attributes
         * @returns Promise
         */
        function getComponentInfo(){
            var deferred = $q.defer();
            Wix.getComponentInfo(
                function (data) {
                    $rootScope.$apply(function() {
                        if (!data) {
                            deferred.reject(data);
                        }
                        deferred.resolve(data);
                    });
                });
            return deferred.promise;
        }

        /**
         * Return obj with current pageId
         * @returns Promise
         */
        function getCurrentPageId(){
            var deferred = $q.defer();
            Wix.getCurrentPageId(function(pageId) {
                $rootScope.$apply(function() {
                    if (!pageId) {
                        deferred.reject(pageId);
                    }
                    deferred.resolve(pageId);
                });
            });
            return deferred.promise;
        }

        /**
         * Return obj with all component info attributes + appPageId
         * @returns Promise
         */
        function getComponentInfoWithAppPageId() {
            var deferred = $q.defer();
            getComponentInfo()
                .then(function (componentInfo) {
                    if (!componentInfo) {
                        return $q.reject();
                    }
                    return $q.all([getCurrentPageId(), componentInfo]);
                })
                .then(function (values) {
                    var pageId = values[0];
                    var componentInfo = values[1];
                    componentInfo.appPageId = pageId;
                    if (!componentInfo) {
                        deferred.reject(componentInfo);
                    }
                    deferred.resolve(componentInfo);
                });
            return deferred.promise;
        }

        /**
         *
         * @returns String
         */
        function getDeviceType() {
            return Wix.Utils.getDeviceType();
        }

        /**
         * @returns String
         * NOTICE: the settings app and the widget do not have the same compId
         * settings.origCompId === widget.compId
         */
        function getCompId() {
            return Wix.Utils.getCompId();
        }

        /**
         * @returns String
         * @description
         * return origin compId. first try directly from SDK, if not exist parse from url
         */
        function getOrigCompId() {

            function _parseCompIdFromIFrame() {
                var params = {};
                window.location.search.substr(1).split('&').forEach(function(val){
                    val = val.split('=');
                    params[val[0]] = val[1];
                });

                return params.compId;
            }

            return Wix.Utils.getOrigCompId() || _parseCompIdFromIFrame();
        }

        function getInstanceId() {
            return Wix.Utils.getInstanceId();
        }

        /**
         * @param h {Integer}
         * @description
         * Request Wix to change iframe's height
         */
        function setHeight (h) {
            Wix.setHeight(h);
        }

        /**
         * @param evt
         * @param listenerFn
         * @description
         * Wrapper on wix events
         */
        function addEventListener (evt, listenerFn) {
            Wix.addEventListener(evt, listenerFn);
        }

        /**
         *
         * @param key
         * @param value
         * @param options
         * @param onSuccess
         * @param onFailure
         * @description
         * Set public data
         */
        function setPublicData(key, value, options, onSuccess, onFailure){
            Wix.Data.Public.set(key, value, options, onSuccess, onFailure);
        }

        /**
         * @description
         * Get view mode
         * @returns {*}
         */
        function getViewMode() {
            return Wix.Utils.getViewMode();
        }

        //==============================
        //        PUBLIC API           =
        //==============================

        return {
            getComponentInfo                : getComponentInfo,
            getCurrentPageId                : getCurrentPageId,
            getComponentInfoWithAppPageId   : getComponentInfoWithAppPageId,
            getDeviceType                   : getDeviceType,
            getCompId                       : getCompId,
            getOrigCompId                   : getOrigCompId,
            getInstanceId                   : getInstanceId,
            setHeight                       : setHeight,
            getViewMode                     : getViewMode,
            setPublicData                   : setPublicData,
            addEventListener                : addEventListener
        };
    }
  ]);
}());