# Luma Events

A PHP/MySQL event-ticketing coursework project with a Liquid Glass frontend, role-based booking approval, signed QR tickets and a detachable ticket animation after check-in.

## Features
- Live event discovery, search, filters and sorting.
- Customer registration and JWT login.
- Customer booking history and cancellation.
- Admin booking management across events; organisers manage their own events.
- Confirmed tickets with locally generated signed QR codes.
- Staff check-in with duplicate-entry prevention.
- Purple ticket stub tear animation after verified entry.
- Responsive layout, keyboard-accessible dialogs and reduced-motion support.

## Stack
PHP, MySQL/MariaDB, HTML, CSS and JavaScript. QRCode.js 1.0.0 is vendored under its included MIT license. Camera QR detection is optional; copy/paste and USB-reader entry are supported alternatives.

## Run locally with XAMPP
1. Place the project folder under `C:\xampp\htdocs\luma-events`.
2. Start Apache and MySQL.
3. In phpMyAdmin, create/select `event_ticketing_db` and import `database/event_ticketing_db.sql` for a fresh database only.
4. Import `database/ticket_checkins_migration.sql` into the same database.
5. Set database connection details in `config/db.php`. The defaults are for local XAMPP development.
6. Configure strong unique `JWT_SECRET` and `API_INTERNAL_KEY` values in the Apache environment (at least 32 characters each), then restart Apache. Keep these values outside the repository.
7. Visit `http://localhost/luma-events/frontend/Nayli/`.
8. Create a customer account through the interface. The seeded admin/organiser accounts have placeholder password hashes: generate a password hash with `php tools/hash_password.php "YOUR_CHOSEN_PASSWORD"` and save the resulting hash in the selected user's `password_hash` column. Do not store plaintext passwords or use phpMyAdmin's MD5 function.

No working passwords, JWT signing secrets, live user data or Apache configuration are included. Do not import the original SQL dump over an existing database.

## Booking flow
Customer reservation → Pending → Staff approval → Confirmed QR ticket → Staff check-in → Used ticket.

Approval and check-in are separate actions. One QR admits the entire booking quantity once. Keep the customer ticket dialog open to see the stub detach after staff accepts entry.

For a one-computer demonstration, use two browser tabs. Customer opens a confirmed ticket and copies its check-in code; organiser/admin pastes it into Workspace → Check in QR, then selects Validate & check in.

## Contributions and project origin
This portfolio version builds on the SWC3633 Group 4 repository: https://github.com/azminaabd/event_ticketing_api . It includes the shared group backend and the original Hazique frontend with their attribution retained.

The individual Luma interface lives in `frontend/Nayli`. The associated security work covers JWT authentication, API-key checks, role-based permissions and booking/ticket ownership integration. Group backend work and individual frontend work are identified separately rather than presented as a single-author codebase.

Third-party attribution: QRCode.js by David Shim — https://github.com/davidshimjs/qrcodejs . Its license is included in `frontend/Nayli/vendor/QRCode-LICENSE.txt`.

## Verification and limits
Local checks covered login, approval/cancellation, ownership restrictions, pending/cancelled QR rejection, signed tickets, duplicate-entry rejection, concurrent check-in, and the customer tear animation after staff validation. Browser checks included the organiser controls and a 320-pixel ticket layout.

Physical camera/phone scanning, USB-reader hardware, every browser and production deployment were not tested. Booking changes retain the group backend's localhost restriction. Check-in is allowed for future active events for demonstration; strict event-time windows are not implemented. Payments are not included. This is a student demonstration, not a production deployment.

See [setup and test details](TICKET_SETUP_AND_TESTS.md) for the endpoint changes and manual checks. Publishing this repository shares the source code; it does not host a live PHP website.