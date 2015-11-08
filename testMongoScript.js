db.components.remove({ "pageId": { $exists: false}, "adCode": { $exists: false}});


var result = db.components.aggregate([{ $group: { _id :{ compId : "$componentId", instanceId: "$instanceId", adCode: "$adCode", deleted: "$deletedAt"}, count: {$sum: 1}}  },{ $match: { count: { $gt: 1}}}]);

for each(var row in result){
	for(var i = 0; i < row.count-1; i ++){
		db.components.remove({"instanceId": row._id.instanceId, "componentId": row._id.compId, "adCode": row._id.adCode, "deletedAt": row._id.deletedAt}, true);
	}
}

