<!-- -*- mode: markdown; -*- -->

# Visitor logger

Logs visitors in a given network. Can be used to log visits of both
wired and wireless ethernet devices. Useful for person tracking. We
use it for collecting visitor statistics in
[our hackerspace](http://jkl.hacklab.fi) in Jyväskylä, Finland.

Intended to be run on a router. Listens to the journal of `dnsmasq`
for DHCPACKs and stores the visitor database to an SQLite database.

To make this work, you need to reduce DHCP server lease time. We use 5
minutes which is currently hard-coded to variable `$dhcp_lease_secs`
in `follow_dhcp`.

The instructions are made for Debian based distributions but should
work with any system running *systemd*.

## Installing

Install requirements (as root):

```sh
apt install php-cli php-sqlite3
```

Initialize database:

```sh
./init_db
```

Make systemd journal persistent by editing
`/etc/systemd/journald.conf` and making sure that you have the
following option:

	[Journal]
	Storage=persistent

Shorten lease time in `/etc/dnsmasq.conf`. In *dhcp-range* put `5m`
after the range definition. For example:

	dhcp-range=10.0.0.100,10.0.0.254,5m

In the same file, enable logging (a must for this to work):

	log-dhcp

Reload services (as root):

	service systemd-journald restart
	service dnsmasq reload

## Running

First I recommend running this in one-shot mode which processes the
log and exits immediately after processing the last item:

```sh
./follow_dhcp --oneshot
```

Then look in to the database and check if you get senseful data. If
everything is fine, then do a systemd service which runs this
continuously. To run continously, just run it without arguments:

```sh
./follow_dhcp
```

TODO systemd service file

## Database

Schema can be found at [schema.sql](schema.sql). Create initial database with
`./init_db`. See some human readable data from SQLite3 console by
running:

```sql
SELECT mac, datetime(enter, 'unixepoch', 'localtime'), datetime(leave, 'unixepoch', 'localtime'), hostname FROM visit;
```

