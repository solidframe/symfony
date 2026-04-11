CREATE TABLE IF NOT EXISTS snapshots (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    aggregate_id VARCHAR(255) NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    version INTEGER UNSIGNED NOT NULL,
    state JSON NOT NULL,
    created_at DATETIME NOT NULL,
    UNIQUE (aggregate_id)
);
