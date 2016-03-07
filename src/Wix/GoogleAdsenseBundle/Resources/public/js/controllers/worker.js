/**
 * Created by hagit on 1/18/16.
 */
(function(window) {
    'use strict';

    /* Worker Controller */

    var comps           = {};
    comps["allPages"]   = [];
    var statusEnum      = {
        'VISIBLE'  : 'visible',
        'DELETED'  : 'deleted',
        'BLOCKED'  : 'blocked'
    };

    /**
     * when widget loaded check the status of the comp and load the view respectively
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

        var pageExists      = isExists(compId);
        var statusComp      = checkCompStatus(componentInfo);
        // comp exists in comps array
        if(pageExists){
            // if visible status load widget else update the new status
            if(pageExists.page.status === statusEnum.VISIBLE && pageExists.showOnAllPages === componentInfo.showOnAllPages){
                console.log("WORKER: comps: ",comps);
                console.log("WORKER: exists comp visible");
                return sendAllowWidget(compId, statusEnum.VISIBLE, pageId, componentInfo.showOnAllPages);
            }
            else if(pageExists.showOnAllPages !== componentInfo.showOnAllPages){
                updatePage(compId, pageExists.page, componentInfo);
                // keep same status
                statusComp = pageExists.page.status;
            }
            else{
                console.log("WORKER: exists with "+ pageExists.page.status+"update to "+ statusComp);
                updateCompId(compId, statusComp, pageId);
            }
        }
        else{
            // if not exists insert comp
            if(componentInfo.showOnAllPages){
                comps["allPages"].push({
                    'pageId' : pageId,
                    'compId' : compId,
                    'status' : statusComp
                });
            }
            else{
                comps[pageId].push({
                    'pageId' : pageId,
                    'compId' : compId,
                    'status' : statusComp
                });
            }
        }
        sendAllowWidget(compId, statusComp, pageId, componentInfo.showOnAllPages);
        console.log("WORKER: comps: ",comps);
    }, true);

    /**
     * when comp delete update status in comps array and try to release blocked component
     */
    Wix.Worker.PubSub.subscribe('DELETED_WIDGET', function(event) {
        var componentInfo = event.data.componentInfo;
        var compId = componentInfo.compId;

        updateCompId(compId, statusEnum.DELETED, componentInfo.pageId);
        console.log('WORKER: deleted widget',event);
        var currentPage = componentInfo.pageId ? componentInfo.pageId : componentInfo.appPageId;
        var dataRelease = releaseBlockedComp(currentPage);
        if(dataRelease && dataRelease.length > 0 ){
            for(var i = 0; i < dataRelease.length; i++){
                console.log("WORKER: sendAllowWidget from delete widget");
                sendAllowWidget(dataRelease[i].compId, dataRelease[i].status, componentInfo.pageId, dataRelease[i].allPages);
            }
        }


    }, true);

    var oldToPage   = "";
    var oldFromPage = "";

    Wix.Worker.PubSub.subscribe('PAGE_NAVIGATION', function(event) {
        if(oldToPage !== event.data.toPage && oldFromPage !== event.data.fromPage){
            oldToPage   = event.data.toPage;
            oldFromPage = event.data.fromPage;
            console.log('WORKER: page navigation',event);
            var dataRelease = releaseBlockedComp(event.data.toPage);
            if(dataRelease && dataRelease.length) {
                for (var i = 0; i < dataRelease.length; i++) {
                    console.log("WORKER: sendAllowWidget from page navigation");
                    sendAllowWidget(dataRelease[i].compId, dataRelease[i].status, event.data.toPage, dataRelease[i].allPages);
                }
            }
        }
    }, true);

    function checkCompStatus(componentInfo){
        var currentPage = componentInfo.pageId ? componentInfo.pageId : componentInfo.appPageId;
        var dataRelease = releaseBlockedComp(currentPage);
        if(dataRelease && dataRelease.length){
            for(var i = 0; i < dataRelease.length; i++){
                console.log("WORKER: sendAllowWidget from releaseBlockedComp");
                sendAllowWidget(dataRelease[i].compId, dataRelease[i].status, currentPage, dataRelease[i].allPages);
            }
        }

        var countComp = getCountVisible(currentPage);
        // Because we now add another one
        if( countComp > 2){
            return statusEnum.BLOCKED;
        }
        return statusEnum.VISIBLE;

    }

    /**
     check if comp exists and return false or page details
     runs on all array comps for component that copy/paste/cut/changed to all pages
      */
    function isExists(compId){
        var showOnAllPages;
        for(var page in comps){
            var pageLen = comps[page] ? comps[page].length : 0;
            for(var j = 0; j < pageLen; j++) {
                if (comps[page][j].compId == compId) {
                    console.log("===========================isExists: found ", comps[page][j]);
                    showOnAllPages = !comps[page][j].pageId;
                    return {page: comps[page][j], showOnAllPages: showOnAllPages};
                }
            }
        }
        return false;
    }

    function sendAllowWidget(compId, status, page, showOnAllPages){
        var data    =  {
            'data' : "WORKER: there is " ,
            'origin' : compId,
            'status' : status,
            'allPages' : showOnAllPages
        };
        Wix.Worker.PubSub.publish('ALLOW_WIDGET', data, true);
    }

    function updateCompId(compId, status, page){

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

    // update widget allPages <==> pageId
    function updatePage(compId, pageExists, componentInfo){
        pageExists.pageId    = !pageExists.pageId ? "allPages" : pageExists.pageId;
        componentInfo.pageId = !componentInfo.pageId ? "allPages" : componentInfo.pageId;
        var pageToMove;

        for(var i = 0; i <  comps[pageExists.pageId].length; i++){
            if(comps[pageExists.pageId][i].compId == compId){
                pageToMove = comps[pageExists.pageId][i];
                comps[pageExists.pageId].splice(i, 1);
                comps[componentInfo.pageId].push({
                    'pageId' : (componentInfo.pageId == "allPages" ? "" : componentInfo.pageId),
                    'compId' : compId,
                    'status' : pageToMove.status
                });
                return;
            }
        }
    }

    function getCountVisible(currentPage){
        var pageLen       = comps[currentPage] ? comps[currentPage].length : 0;
        var allPagesLen   = comps["allPages"] ? comps["allPages"].length : 0;
        var pageCount     = 0;
        var allPagesCount = 0;

        // check count of current page
        for(var i = 0; i < pageLen; i++){
            if(comps[currentPage][i].status == statusEnum.VISIBLE){
                pageCount++;
            }
        }

        // check count of array all pages
        for(var j = 0; j < allPagesLen; j++){
            if(comps["allPages"][j].status == statusEnum.VISIBLE){
                allPagesCount++;
            }
        }
        return allPagesCount+pageCount;

    }

    function releaseBlockedComp(page){
        var countComp = getCountVisible(page);
        var dataRelease = [];
        // check if exists blocked in all pages
        var allPagesLen   = comps["allPages"] ? comps["allPages"].length : 0;
        for(var j = 0; countComp < 3 && j < allPagesLen; j++){
            if(comps["allPages"][j].status == statusEnum.BLOCKED){
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
        return dataRelease;
    }
}(window));
