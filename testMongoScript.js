//remove all components without pageId and adCode
db.components.remove({ "pageId": { $exists: false}, "adCode": { $exists: false}});

//remove duplicates and save only one
var result = db.components.aggregate([{ $group: { _id :{ compId : "$componentId", instanceId: "$instanceId", adCode: "$adCode", deleted: "$deletedAt"}, count: {$sum: 1}}  },{ $match: { count: { $gt: 1}}}]);

for each(var row in result){
	for(var i = 0; i < row.count-1; i ++){
		db.components.remove({"instanceId": row._id.instanceId, "componentId": row._id.compId, "adCode": row._id.adCode, "deletedAt": row._id.deletedAt}, true);
	}
}

//remove all components that not deleted but without pageId
db.components.remove({"deletedAt": {$exists: false}, "pageId": { $exists: false}})


//remove components that not relevant - not updatedDate
var result = db.components.find({"updatedDate": { $exists: true}, "deletedAt": {$exists: false}}, { "instanceId": 1, "pageId": 1});

for each(var row in result){
    db.components.remove({"instanceId": row.instanceId, "pageId": row.pageId, "adCode": { $exists: false},
                          "updatedDate": { $exists: false}, "deletedAt": { $exists: false}});
}

var o0 = db.components.aggregate([{ $match: {"updatedDate": { $exists: true }, "deletedAt": {$exists: false} } }, { $group: { _id: { instanceId: '$instanceId', pageId: '$pageId' } } }, { $sort: { '_id.pageId': -1 } } ]);
var o1 = o0.result.map(function(i){ return { instanceId: i._id.instanceId, pageId: i._id.pageId }; });
db.components.remove({ "adCode": { $exists: false}, "updatedDate": { $exists: false}, "deletedAt": { $exists: false}, $or: o1 });

