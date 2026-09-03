# Guest maintenance and QR access

- Admin: open a Unit, select **Guest App QR / Registration Link**, then print or save the poster as PDF.
- The QR is generated locally with `mpdf/qrcode`; no guest data is sent to an external QR provider. Set the production `APP_URL` correctly before printing.
- Guests scan the unit QR, sign in, or activate using an active booking reference and its email. Activation reuses the booking-to-tenant sync rules and sends Laravel's expiring, single-use password reset link. It never logs in the guest or displays booking details publicly. Working outbound email is required.
- A mismatched email/reference, conflicting identity, cancelled booking, or unrelated account does not grant access. The public response is deliberately generic. Requests are rate-limited.
- After password setup, guests sign in and complete missing profile details. Maintenance is in the app navigation. Requests require a linked confirmed or checked-in booking on a non-deleted unit.
- Requests create an open, unassigned Maintenance task in the existing Task Manager with guest creator, booking, unit, priority, description, up to five photos, and a creation audit activity. Management assigns and processes it using existing task workflows.
- Guests see only their own submitted requests still linked to their booking, with live task status. They cannot assign staff, set completion status, change units, or see internal remarks and costs. Submitting a request does not change the unit's availability status or post accounting entries.
- QR activation does not support unbooked visitors self-assigning a unit. Management must first create/correct their booking.
- Deployment: run `composer install --no-dev --optimize-autoloader` and refresh route/view caches. No new database migration is required.
