# Booking guests and tenant profiles

Migration 040 links existing bookings and creates tenant accounts where safe. New bookings and renewals run the same sync. Guest name, personal email, phone, passport/ID and an existing booking ID attachment seed the profile. Existing tenant details are filled only when blank, never overwritten. New accounts receive a random hashed secret, not a shared temporary password. No invitation emails are automatically sent by deployment; the guest uses the standard Forgot Password flow to establish a password, requiring working application email delivery.

An email already belonging to another role, a deleted user, or a conflicting passport/ID is not linked. The booking receives a Tenant Link Needs Review history entry. Use a real guest email, not a shared agent/channel mailbox. Invalid email or an oversized ID is also flagged.

Linked profiles missing name, phone, passport/ID, nationality, date of birth or address are marked pending. Tenant-app middleware redirects them to Complete your guest profile before accessing bookings/inspections. Emergency contact details are optional. Profile changes are restricted to the authenticated tenant; email, role and permissions cannot be changed through this form. Booking snapshots and financials remain unchanged.

Tenant booking access uses the explicit tenant ID, not an email-only match. The admin tenant list shows pending completion and actual linked booking counts. Tenant detail pages show only that tenant's booked units. The Profile navigation item opens the form after completion as well.

Accounts are preserved on rollback. Deploy with migrations 039 and 040; 039 belongs to the separate existing-owner reconciliation change. Live data and emails have not been changed during local development.
