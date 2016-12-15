-- -*- mode: markdown; -*-

# Visitor logger

TODO documentation

## Database

Schema can be found at [schema.sql]. Create initial database with
`./init_db`. See some human readable data from SQLite3 console by
running:

```sql
SELECT mac, datetime(enter, 'unixepoch'), datetime(leave, 'unixepoch'), hostname FROM visit;
```

## Requirements

	sudo apt install php-cli php-sqlite3
