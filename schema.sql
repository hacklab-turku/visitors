CREATE TABLE visit(
	mac TEXT NOT NULL,
	enter INTEGER NOT NULL,
	leave INTEGER NOT NULL,
	hostname TEXT
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
