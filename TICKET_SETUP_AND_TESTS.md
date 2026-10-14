# Luma Events — booking approvals and QR entry

## Open the installed project
Start XAMPP Apache and MySQL, then open:
http://localhost/event_ticketing_api-main/frontend/Nayli/
Refresh the page after updating. The new ticket_checkins table has already been installed in your current local database. Your accounts and existing bookings were retained.

## Try the complete flow
1. CUSTOMER: sign in, book an active future event, and open My bookings. It starts as PENDING.
2. ORGANISER: open a separate browser tab, sign in, go to Workspace > Bookings. Only bookings for your events are listed. Select Approve and confirm. Admin can approve bookings across all events.
3. CUSTOMER: refresh My bookings, then select View ticket. A real signed QR appears on the white/purple ticket. Pending or cancelled bookings cannot retrieve a usable QR.
4. STAFF: select Check in QR. In a browser with camera QR support, select Start camera scan and grant camera access. Capture the QR from the customer's screen, then select Validate & check in.
5. For a one-computer demo, CUSTOMER selects Copy check-in code. STAFF pastes it in Check in QR and selects Validate & check in. A USB QR reader can also enter the code into the field.
6. Leave the customer's ticket dialog open: within about 2.5 seconds after accepted check-in, the QR disappears, the purple stub tears away, and CHECKED IN appears. Reopening an already-used ticket shows the used state without replaying entry.
7. Attempt the same check-in again: the server must reject it as already used.
8. STAFF can Cancel unused reservations; CUSTOMER can cancel their own unused bookings. Checked-in bookings cannot be changed.

## Ticket rules
Each booking has one QR and admits the full booked quantity once; individual group members cannot arrive separately with the same booking QR. Confirmation and entry are separate steps. Simply viewing or scanning outside the staff check-in workflow does not admit anyone.
The current demo allows staff to check in a future active event before its scheduled date. It rejects past or inactive events. Add event-time windows if the group requires strict day-of-entry enforcement.
QR generation happens locally in the browser using vendored QRCode.js 1.0.0 (MIT license; frontend/Nayli/vendor/QRCode-LICENSE.txt). Ticket tokens are never sent to the third-party QR service. JWTs remain in sessionStorage; ticket signing uses a separate derived key from the existing JWT_SECRET. Secret rotation invalidates old ticket codes; reopen tickets to obtain new ones.

## Fresh installation or another computer
1. Keep the whole event_ticketing_api-main folder together under XAMPP htdocs. Nested extraction folders work; include them in the browser URL.
2. Start Apache and MySQL.
3. Fresh database only: create/select event_ticketing_db in phpMyAdmin and import database/event_ticketing_db.sql. Do not import this original dump over existing tables.
4. In the same database import database/ticket_checkins_migration.sql. This is an additive, repeatable migration; it keeps existing tables and records.
5. Check config/db.php connection settings.
6. Configure a strong unique JWT_SECRET and separate API_INTERNAL_KEY (each at least 32 characters) in Apache's environment; restart Apache. Do not put secrets in frontend files or ZIPs.
7. Seed accounts in the original SQL have placeholder hashes. Set them using tools/hash_password.php and phpMyAdmin; your current local passwords are not exported in this package.
8. Open the frontend through localhost, using your Apache port if different. Camera access requires a supported browser, a secure context (localhost or HTTPS), and explicit device permission. When native BarcodeDetector is unavailable, paste a copied code or use a USB reader. Mobile LAN hosting and camera support need separate testing; existing booking writes retain their localhost restriction.

## Group integration changes
This feature includes frontend work plus necessary backend security integration. Modified existing backend files: middleware/security.php, bookings.php, events.php, qr_ticket.php, ticket_view.php. New backend files: ticket.php, checkin.php and database/ticket_checkins_migration.sql.
JWT verification, public customer registration, user-role checks, API-key protection and request logging remain. Booking creation binds customers to their verified identity. Booking listing is scoped to a customer's bookings or an organiser's events; admins see all. PUT approvals require ADMIN/ORGANISER. Organisers cannot transfer event ownership. Ticket endpoints require JWT and permission checks. Atomic booking locks and a unique check-in record prevent duplicate entry and cancellation races.
Legacy QR/ticket endpoints now require authentication and booking permission; they are not Luma's signed QR workflow. Older clients must send Authorization headers. Luma uses ticket.php and checkin.php instead. Hazique frontend files, users.php, venues.php, login, internal API-key endpoint, .htaccess and the original SQL dump are unchanged.
A recoverable copy of the project before these changes is saved in this chat's work folder. No changes were pushed to GitHub.

## Tests run
- PHP syntax checks on all 14 PHP files; JavaScript syntax check.
- Real local API requests: customer ownership binding, public registration/login, pending QR denial, missing JWT denial on current and legacy ticket endpoints, customer self-approval rejection, organiser scope and other-organiser denial, other-customer ticket denial, approval, signed QR retrieval, tampered QR rejection, customer check-in rejection, successful organiser check-in, duplicate check-in rejection, cancelled QR rejection, checked-in cancellation denial, admin approval/cancellation across events and organiser ownership-transfer rejection.
- Two simultaneous check-in requests produced one 200 response and one 409 response.
- Browser: customer booking history, real QR rendering, copy/paste entry, organiser approval and cancellation buttons, live customer tear animation after staff acceptance, duplicate-entry error, and used-ticket state. Ticket fits a 320-pixel viewport without horizontal dialog overflow.
- All temporary test check-ins, bookings, customers, events and the venue were removed afterward. Insert tests consumed auto-increment values, so numbering gaps are expected.

## Not tested
Physical camera/phone scanning, USB scanner hardware, every browser, production/remote deployment, real-device reduced-motion, large booking datasets, and a full security audit. The in-app browser lacks native camera QR detection, so the tested entry method was the supported copy/paste fallback. No payment integration is included. Existing organiser event creation remains admin-only under the group middleware.