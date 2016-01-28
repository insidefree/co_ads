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
        getSiteInfo(event.origin)
            .then(function(page){
                // get page details failed
                if(!page){
                    return;
                }
                // if not exist appPageId in comps array
                if(page.appPageId && !comps[page.appPageId]){
                    comps[page.appPageId] = [];
                }
                var compId          = event.origin;
                var statusExists    = isExists(compId, page.appPageId, page.showOnAllPages);
                var statusComp      = checkCompStatus(page.appPageId);
                // comp exists in comps array
                if(statusExists){
                    // if visible status load widget else update the new status
                    if(statusExists == statusEnum.VISIBLE){
                        console.log("WORKER: exists comp visible");
                        return sendAllowWidget(compId, statusEnum.VISIBLE, page.appPageId, page.showOnAllPages);
                    }
                    console.log("WORKER: exists with "+ statusExists+"update to "+ statusComp);
                    updateCompId(compId, statusComp, page.appPageId);
                }
                else{
                    // if not exists insert comp
                    if(page.showOnAllPages){
                        comps["allPages"].push({
                            'pageId' : page.appPageId,
                            'compId' : compId,
                            'status' : statusComp
                        });
                    }
                    else{
                        comps[page.appPageId].push({
                            'pageId' : page.appPageId,
                            'compId' : compId,
                            'status' : statusComp
                        });
                    }
                }
                sendAllowWidget(compId, statusComp, page.appPageId, page.showOnAllPages);
                console.log("WORKER: comps: ",comps);
            });
    }, true);

    /**
     * when comp delete update status in comps array and try to release blocked component
     */
    Wix.Worker.PubSub.subscribe('DELETED_WIDGET', function(event) {
        getSiteInfo(event.data.compId)
            .then(function(page){
                updateCompId(event.data.compId, statusEnum.DELETED, page.appPageId);
                console.log('WORKER: deleted widget',event);
                var dataRelease = releaseBlockedComp(page.appPageId);
                if(dataRelease.length > 0 ){
                    for(var i = 0; i < dataRelease.length; i++){
                        sendAllowWidget(dataRelease[i].compId, dataRelease[i].status, page.appPageId, dataRelease[i].allPages);
                    }
                }
            });

    }, true);

    var oldToPage   = "";
    var oldFromPage = "";

    Wix.Worker.PubSub.subscribe('PAGE_NAVIGATION', function(event) {
        if(oldToPage !== event.data.toPage && oldFromPage !== event.data.fromPage){
            oldToPage   = event.data.toPage;
            oldFromPage = event.data.fromPage;
            console.log('WORKER: page navigation',event);
            var dataRelease = releaseBlockedComp(event.data.toPage);
            if(dataRelease.length > 0 ) {
                for (var i = 0; i < dataRelease.length; i++) {
                    sendAllowWidget(dataRelease[i].compId, dataRelease[i].status, event.data.toPage, dataRelease[i].allPages);
                }
            }
        }
    }, true);

    function getSiteInfo(compId){
        //getComponentInfo
        return new Promise(function(resolve, reject) {
            //Wix.Worker.getSiteInfo(function(data){
            setTimeout(function () {
                Wix.getComponentInfo(
                    function (data) {
                        if (!data) {
                            reject(data);
                        }
                        resolve(data);
                    }, compId)
            }, 1000);
        })
        .then(function (data) {
            return new Promise(function (resolve, reject) {
                Wix.Worker.getSiteInfo(function (page) {
                    if (!page) {
                        reject(page);
                    }
                    console.log('WORKER: getSiteInfo=>', page);
                    data.appPageId = page.pageTitle;
                    resolve(data);
                });
            });
        });

    }

    function checkCompStatus(page){
        var dataRelease = releaseBlockedComp(page);
        if(dataRelease.length > 0 ){
            for(var i = 0; i < dataRelease.length; i++){
                sendAllowWidget(dataRelease[i].compId, dataRelease[i].status, page.appPageId, dataRelease[i].allPages);
            }
        }
        var countComp = getCountVisible(page);
        // Because we now add another one
        if( countComp > 2){
            return statusEnum.BLOCKED;
        }
        return statusEnum.VISIBLE;

    }

    // check if comp exists and return false or status
    function isExists(compId, page, showOnAllPages){
        var allPagesLen   = comps["allPages"].length;

        if(showOnAllPages){
            // check if exists in all pages
            for(var j = 0; j < allPagesLen; j++){
                if(comps["allPages"][j].compId == compId){
                    return comps["allPages"][j].status;
                }
            }
        }
        var pageLen       = comps[page].length;
        // check if exists in current page
        for(var i = 0; i < pageLen; i++){
            if(comps[page][i].compId == compId){
                return comps[page][i].status;
            }
        }
        return false;
    }

    function sendAllowWidget(compId, status, page, showOnAllPages){
        var data    =  {
            'data' : "WORKER: there is " + comps[page].length,
            'origin' : compId,
            'status' : status,
            'allPages' : showOnAllPages
        };
        console.log(data);
        Wix.Worker.PubSub.publish('ALLOW_WIDGET', data, true);
    }

    function updateCompId(compId, status, page){

        // check if exists in all pages
        var allPagesLen   = comps["allPages"].length;
        for(var j = 0; j < allPagesLen; j++){
            if(comps["allPages"][j].compId == compId){
                comps["allPages"][j].status = status;
                return;
            }
        }

        // check if exists in current page
        var pageLen       = comps[page].length;
        for(var i = 0; i < pageLen; i++){
            if(comps[page][i].compId == compId){
                comps[page][i].status = status;
                return;
            }
        }

    }

    function getCountVisible(page){
        var pageLen       = comps[page].length;
        var allPagesLen   = comps["allPages"].length;
        var pageCount     = 0;
        var allPagesCount = 0;

        // check count of current page
        for(var i = 0; i < pageLen; i++){
            if(comps[page][i].status == statusEnum.VISIBLE){
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
        var allPagesLen   = comps["allPages"].length;
        for(var j = 0; countComp < 3 && j < allPagesLen; j++){
            if(comps["allPages"][j].status == statusEnum.BLOCKED){
                comps["allPages"][j].status = statusEnum.VISIBLE;
                dataRelease.push(comps["allPages"][j]);
                countComp++;
            }
        }
        if(comps[page].length > 0){
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
