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
apt install php-cli php-sqlite3 php-zmq php-fpm nginx
```

Initialize database:

```sh
./init_db
```

Remember to adjust database privileges to allow writes by user of
*php-fpm* which is `www-data` by default.

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

If you want to serve public parts of the data, let's configure
nginx. Add to nginx server block the following (adapt php-fpm socket
path to your system):

```
location /visitors {
	root /PATH/TO/visitors/dist;
	try_files $uri $uri/ @extensionless-php;

	location ~ \.php$ {
		include snippets/fastcgi-php.conf;
		fastcgi_pass unix:/run/php/php7.0-fpm.sock;
		fastcgi_param SCRIPT_FILENAME $request_filename;
	}
}

location @extensionless-php {
	rewrite (.*) $1.php last;
}
```

Reload services (as root):

	service systemd-journald restart
	service dnsmasq reload
	service nginx reload

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

Schema can be found at [schema.sql](schema.sql). Create initial
database with `./init_db`. See some human readable data from SQLite3
console (start with `sqlite3 db/db.sqlite`) by running:

```sql
SELECT mac, datetime(enter, 'unixepoch', 'localtime'),
       datetime(leave, 'unixepoch', 'localtime'), ip, hostname
FROM visit
ORDER BY enter;
```

## Other stuff

### Useful queries

Get visitors with name:

```sql
SELECT nick, datetime(enter, 'unixepoch', 'localtime'),
       datetime(leave, 'unixepoch', 'localtime')
FROM visit v
JOIN user_mac USING (mac)
JOIN user u ON (SELECT id
                FROM user_mac m
                WHERE m.mac=v.mac
                ORDER BY changed DESC LIMIT 1
               )=u.id;
```

### Generating oident map

If you want to generate map for oidentd so that connections coming
from local network are identifiable from outside. This allows to get
nicer IRC identities, for example.

	./lab_visitors/ident_map | sudo tee /etc/oidentd_masq.conf
	sudo service oidentd reload

However, we never managed to get *oident* to detect masqueraded
connections.

### Cleaning the data

If you have way too many short visits due to misconfigured
`$dhcp_lease_secs` or want to clean up old data by merging short-term
visits (within <10 minutes), there is tool `tools/cleanup_old_data`.

It reads current *visit* table and imports the data into *visit\_new*
while re-evaluating the `$dhcp_lease_secs` for all data on that table.
You should stop `follow_dhcp` while running the tool and rename
*visit\_new* to *visit*. Don't forget to recreate the indices!
