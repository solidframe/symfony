CREATE TABLE IF NOT EXISTS event_store (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    aggregate_id VARCHAR(255) NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    version INTEGER UNSIGNED NOT NULL,
    event_type VARCHAR(255) NOT NULL,
    payload JSON NOT NULL,
    occurred_at DATETIME NOT NULL,
    UNIQUE (aggregate_id, version)
);

CREATE INDEX idx_event_store_aggregate_id ON event_store (aggregate_id);
