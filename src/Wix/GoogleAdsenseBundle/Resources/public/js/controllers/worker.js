
(function(window) {
    'use strict';

    /* Worker Controller */

    var comps           = {
        allPages: []
    };
    var statusEnum      = {
        VISIBLE  : 'visible',
        DELETED  : 'deleted',
        BLOCKED  : 'blocked'
    };

    /**
     * When widget loaded check status of the comp and send to widget
     */
    Wix.Worker.PubSub.subscribe('WIDGET_LOAD', function(event)
    {
        // get comp details : pageId, allPages indication and ...
        var componentInfo = event.data.componentInfo;
        // get page details failed
        if(!componentInfo){
            return;
        }
        var compId = event.origin;
        var pageId = componentInfo.pageId;
        // if not exist pageId in comps array
        if(pageId && !comps[pageId]){
            comps[pageId] = [];
        }

        var compExists      = isExists(compId);
        var statusComp      = getDesiredStatus(componentInfo);
        // when comp exists in comps array
        if(compExists){
            // if visible status load widget
            if(compExists.comp.status === statusEnum.VISIBLE && compExists.showOnAllPages === componentInfo.showOnAllPages){
                return sendAllowWidget(compId, statusEnum.VISIBLE, componentInfo.showOnAllPages);
            }
            else if(compExists.showOnAllPages !== componentInfo.showOnAllPages){
                // when user change from all pages to specific page and from specific page to all pages, update the comp page
                return updateCompPage(compId, compExists.comp, componentInfo);
            }
            else{
                // update status of comp
                updateCompStatus(compId, statusComp, pageId);
            }
        }
        else{
            // if not exists insert comp
            if(componentInfo.showOnAllPages){
                comps["allPages"].push({
                    pageId : pageId,
                    compId : compId,
                    status : statusComp
                });
            }
            else{
                comps[pageId].push({
                    pageId : pageId,
                    compId : compId,
                    status : statusComp
                });
            }
        }
        // trigger widget with status of comp
        return sendAllowWidget(compId, statusComp, componentInfo.showOnAllPages);
    }, true);

    /**
     * When comp deleted update status in comps array and try to release blocked component
     */
    Wix.Worker.PubSub.subscribe('DELETED_WIDGET', function(event) {
        var componentInfo   = event.data.componentInfo;
        var compId          = componentInfo.compId;
        var currentPage     = componentInfo.pageId ? componentInfo.pageId : componentInfo.appPageId;

        updateCompStatus(compId, statusEnum.DELETED, componentInfo.pageId);
        releaseBlockedComp(currentPage);
    }, true);

    var oldToPage   = "";
    var oldFromPage = "";
    /**
     * When user navigate between pages, try to release blocked all pages
     */
    Wix.Worker.PubSub.subscribe('PAGE_NAVIGATION', function(event) {
        var eventData = event.data.eventData;
        if(oldToPage !== eventData.toPage && oldFromPage !== eventData.fromPage){
            oldToPage   = eventData.toPage;
            oldFromPage = eventData.fromPage;
            // release blocked comps if there aren't 3 visible ads on current page
            releaseBlockedComp(eventData.toPage);
            // give priority to all pages to be visible and blocked current page visible comps
            blockedVisibleComp(eventData.toPage);
        }
    }, true);

    /**
     * Check if comp exists (when user cut/copy/paste/changed to all_pages)
     * and return page details or false when not exists
     * @param compId
     * @returns {*}
     */
    function isExists(compId){
        var showOnAllPages;
        for(var page in comps){
            if ( !comps.hasOwnProperty(page) ) {
                continue;
            }
            var pageLen = comps[page] ? comps[page].length : 0;
            for(var j = 0; j < pageLen; j++) {
                if (comps[page][j].compId == compId) {
                    showOnAllPages = !comps[page][j].pageId;
                    return {comp: comps[page][j], showOnAllPages: showOnAllPages};
                }
            }
        }
        return false;
    }

    /**
     * Trigger widget with comp details (compId, status, allPages)
     * @param compId
     * @param status
     * @param showOnAllPages
     */
    function sendAllowWidget(compId, status, showOnAllPages){
        var data    =  {
            origin   : compId,
            status   : status,
            allPages : showOnAllPages
        };
        Wix.Worker.PubSub.publish('ALLOW_WIDGET', data, true);
    }

    /**
     * When status of comp changed, update the new status
     * @param compId
     * @param status
     * @param page
     */
    function updateCompStatus(compId, status, page){
        // check if exists in all pages
        var allPagesLen   = comps["allPages"] ? comps["allPages"].length : 0;
        for(var j = 0; j < allPagesLen; j++){
            if(comps["allPages"][j].compId == compId){
                comps["allPages"][j].status = status;
                return;
            }
        }
        // check if exists in current page
        var pageLen       = comps[page] ? comps[page].length : 0;
        for(var i = 0; i < pageLen; i++){
            if(comps[page][i].compId == compId){
                comps[page][i].status = status;
                return;
            }
        }
    }

    /**
     * When user change from all pages to specific page and from specific page to all pages, update the comp page
     * @param compId
     * @param pageExists
     * @param componentInfo
     */
    function updateCompPage(compId, pageExists, componentInfo){
        pageExists.pageId    = !pageExists.pageId ? "allPages" : pageExists.pageId;
        componentInfo.pageId = !componentInfo.pageId ? "allPages" : componentInfo.pageId;
        var pageToMove;

        for(var i = 0; i <  comps[pageExists.pageId].length; i++){
            if(comps[pageExists.pageId][i].compId == compId){
                pageToMove = comps[pageExists.pageId][i];
                comps[pageExists.pageId].splice(i, 1);
                comps[componentInfo.pageId].push({
                    pageId : (componentInfo.pageId == "allPages" ? "" : componentInfo.pageId),
                    compId : compId,
                    status : pageToMove.status
                });
            }
        }
        // only when user change from specific page to all page, give priority to all pages and blocked specific page comp
        if(componentInfo.showOnAllPages){
            blockedVisibleComp(componentInfo.appPageId);
        }
    }

    /**
     * Get count of visible comps on current page
     * @param currentPage
     * @returns {number}
     */
    function getCountVisible(currentPage){
        return getCountCurrentPageVisible(currentPage) + getCountAllPagesVisible();
    }

    /**
     * Get count of visible comps, only comp all pages
     * @returns {number}
     */
    function getCountAllPagesVisible(){
        var allPagesLen   = comps["allPages"] ? comps["allPages"].length : 0;
        var allPagesCount = 0;
        // check count of all pages
        for(var j = 0; j < allPagesLen; j++){
            if(comps["allPages"][j].status == statusEnum.VISIBLE){
                allPagesCount++;
            }
        }
        return allPagesCount;
    }

    /**
     * Get count of visible comps, only comp of current page
     * @param currentPage
     */
    function getCountCurrentPageVisible(currentPage){
        var pageLen       = comps[currentPage] ? comps[currentPage].length : 0;
        var pageCount     = 0;
        // check count of current page
        for(var i = 0; i < pageLen; i++){
            if(comps[currentPage][i].status == statusEnum.VISIBLE){
                pageCount++;
            }
        }
        return pageCount;
    }

    /**
     * Get desired comp status, blocked comp when there are more than 3 ads on page
     * @param componentInfo
     * @returns {string}
     */
    function getDesiredStatus(componentInfo){
        var currentPage = componentInfo.pageId ? componentInfo.pageId : componentInfo.appPageId;
        releaseBlockedComp(currentPage);

        var countComp = getCountVisible(currentPage);
        // Because now add another one
        if( countComp > 2){
            return statusEnum.BLOCKED;
        }
        return statusEnum.VISIBLE;
    }

    /**
     * Release blocked components to be visible give priority to all pages
     * @param page
     */
    function releaseBlockedComp(page) {
        var countComp   = getCountVisible(page);
        var dataRelease = [];
        // check if exists blocked in all pages
        var allPagesLen = comps["allPages"] ? comps["allPages"].length : 0;
        for (var j = 0; countComp < 3 && j < allPagesLen; j++) {
            if (comps["allPages"][j].status === statusEnum.BLOCKED) {
                comps["allPages"][j].status = statusEnum.VISIBLE;
                dataRelease.push(comps["allPages"][j]);
                countComp++;
            }
        }
        if(comps[page] && comps[page].length){
            // check if exists blocked in current page
            var pageIdLen   = comps[page].length;
            for(var i = 0; countComp < 3 && i < pageIdLen; i++){
                if(comps[page][i].status == statusEnum.BLOCKED){
                    comps[page][i].status = statusEnum.VISIBLE;
                    dataRelease.push(comps[page][i]);
                    countComp++;
                }
            }
        }
        // trigger widget with the new status of comps
        if(dataRelease && dataRelease.length){
            for(var z = 0; z < dataRelease.length; z++){
                sendAllowWidget(dataRelease[z].compId, dataRelease[z].status, dataRelease[z].allPages);
            }
        }
    }

    /**
     * Release all pages comp, when all pages visible < 3, and blocked visible comps of current page
     * @param currentPage
     */
    function blockedVisibleComp(currentPage){
        var countAllPages    = getCountAllPagesVisible();
        var countCurrentPage = getCountCurrentPageVisible(currentPage);
        var dataRelease      = [];

        // check if exists blocked in all pages
        var allPagesLen = comps["allPages"] ? comps["allPages"].length : 0;
        for (var j = 0; countAllPages < 3 && j < allPagesLen; j++) {
            if (comps["allPages"][j].status === statusEnum.BLOCKED) {
                comps["allPages"][j].status = statusEnum.VISIBLE;
                dataRelease.push(comps["allPages"][j]);
                countAllPages++;
            }
        }

        // blocked visible comp - current page
        if(comps[currentPage] && comps[currentPage].length){
            // check if exists blocked in current page
            var pageIdLen   = comps[currentPage].length;
            for(var i = 0; (countAllPages+countCurrentPage) > 3 && i < pageIdLen; i++){
                if(comps[currentPage][i].status == statusEnum.VISIBLE){
                    comps[currentPage][i].status = statusEnum.BLOCKED;
                    dataRelease.push(comps[currentPage][i]);
                    countCurrentPage--;
                }
            }
        }

        // trigger widget with the new status of comps
        if(dataRelease && dataRelease.length){
            for(var z = 0; z < dataRelease.length; z++){
                sendAllowWidget(dataRelease[z].compId, dataRelease[z].status, dataRelease[z].allPages);
            }
        }
    }

}(window));
