/**
 * Migration for users with client-id containing ca-mb-pub
 * We should replace the ca-mb-pub with ca-pub
 * Happens only for old users
 * Change adviced by noamm@wix.com after talking with Google
 */
db.users.find({"clientId": /^ca-mb-pub/})
	.forEach( function(u) {
		u.clientId = u.clientId.replace("mb-", "");
		u.isMbClient = true;
		db.users.save(u);
	});