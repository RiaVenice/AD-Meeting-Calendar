CREATE TABLE IF NOT EXISTS meeting_users (
    assignment_id SERIAL PRIMARY KEY,
    meeting_id INTEGER NOT NULL REFERENCES meetings(meeting_id),
    user_id INTEGER NOT NULL REFERENCES users(user_id),
    role VARCHAR(30) DEFAULT 'attendee',
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(meeting_id, user_id)
);
