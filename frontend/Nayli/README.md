# Nayli individual frontend — Luma Events
See ../../TICKET_SETUP_AND_TESTS.md for installation, approval/check-in steps and actual test results.
This frontend has a light Liquid Glass design, live event search, customer registration/login/booking/history, an admin/organiser workspace, booking approval/cancellation, a protected signed QR ticket and a purple stub that detaches after staff accepts check-in.
The updated frontend requires the accompanying backend changes and ticket_checkins migration. Installing only this folder over the old backend is insufficient.
Use http://localhost/event_ticketing_api-main/frontend/Nayli/ with Apache and MySQL running. API paths follow app.js, including nested folders and alternate ports.
Local QR generation uses vendored QRCode.js 1.0.0 under the included MIT license. Camera detection is optional and browser-dependent; copy/paste or USB QR readers are supported alternatives.