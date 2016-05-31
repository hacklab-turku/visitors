CREATE TABLE visitor(mac TEXT NOT NULL, ts TEXT NOT NULL DEFAULT current_timestamp, ip TEXT NOT NULL);
CREATE INDEX mac_ix on visitor (mac,ts);
CREATE INDEX ts_ix on visitor (ts,mac);
CREATE INDEX ip_ix on visitor (ip);
