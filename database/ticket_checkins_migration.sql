-- Additive migration. Select event_ticketing_db before importing.
CREATE TABLE IF NOT EXISTS ticket_checkins (
  booking_id INT NOT NULL PRIMARY KEY,
  checked_in_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  checked_in_by INT NOT NULL,
  CONSTRAINT fk_checkin_booking FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
  CONSTRAINT fk_checkin_staff FOREIGN KEY (checked_in_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;