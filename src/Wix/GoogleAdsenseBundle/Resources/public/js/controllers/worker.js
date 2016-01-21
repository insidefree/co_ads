/**
 * Created by hagit on 1/18/16.
 */
(function(window) {
    'use strict';

    /* Worker Controller */

    var arrCompId = [];

    Wix.Worker.PubSub.subscribe('WIDGET_LOAD', function(event)
    {
        console.log("WORKER: data event: ", event);
        arrCompId.push(event.origin);
        console.log("WORKER: arrCompId: ",arrCompId);

        var data    =  {};
        data.data   = "WORKER: there is " + arrCompId.length;
        data.origin = event.origin;
        data.count  = arrCompId.length;

        Wix.Worker.PubSub.publish('ALLOW_WIDGET', data, true);
    },true);

}(window));
