-- -*- mode: sql; sql-product: sqlite; -*-
BEGIN;

CREATE TABLE state (
	key TEXT PRIMARY KEY,
	value
);
INSERT INTO state (key) VALUES ('cursor'),('last_leave_rowid');
INSERT INTO state (key,value) VALUES ('is_empty', 1);

CREATE TABLE visit(
	mac TEXT NOT NULL,
	enter INTEGER NOT NULL,
	leave INTEGER NOT NULL,
	ip TEXT NOT NULL,
	hostname TEXT NOT NULL
);

CREATE INDEX ix_visit_mac ON visit (mac,enter);
CREATE INDEX ix_visit_enter ON visit (enter, mac);
CREATE INDEX ix_visit_leave ON visit (leave, mac);

CREATE TABLE user(
	id INTEGER PRIMARY KEY,
	nick TEXT NOT NULL
);

CREATE INDEX ix_user_nick ON user (nick);

CREATE TABLE user_mac(
	mac TEXT NOT NULL,
	id INTEGER, -- NULL if mac is removed
	changed INTEGER NOT NULL
);

CREATE INDEX ix_user_mac_mac on user_mac (mac,changed DESC);
CREATE INDEX ix_user_mac_id on user_mac (id);

END;
