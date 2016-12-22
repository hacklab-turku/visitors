<!-- -*- mode: markdown; -*- -->

# API documentation

RESTful interface is served via HTTP for changing the nickname associated wtih the nick.P

method | endpoint | description
--- | --- | ---
GET | /v1/nicks | List all nicknames on the system
GET | /v1/nick | Get information about the device
PUT | /v1/nick | Start tracking the device. Parameters in: `nick`
DELETE | /v1/nick | Delete device, do not track anymore

All endpoints except `/api/v1/nicks` show only your data associated to
the MAC of your device.

## Examples

The following examples operate on API endpoints of Hacklab
Jyväskylä. Change the URL to your site before running in other
networks.

The examples below are piped through `jq` which pretty-prints the
output. You may leave it out if you can natively parse JSON :-)

Get list of nicknames:

	curl http://hacklab.ihme.org/api/v1/nicks | jq

Get info associated to your device:

	curl http://hacklab.ihme.org/api/v1/nick | jq

Set a nickname for your device (you may use the same nickname for all of your devices):

	curl -X PUT http://hacklab.ihme.org/api/v1/nick?nick=Hillosipuli

Stop tracking:

	curl -X DELETE http://hacklab.ihme.org/api/v1/nick
