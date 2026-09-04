# Unit inventory and office inspection requests

Deploy the code and run `php artisan migrate --force`, then clear/rebuild route and view caches through the normal updater. No existing inventory quantities are inferred from expenses or old inspection text.

## Office workflow

Open **Inspections > Unit Inventory**, or **Unit Details > Unit Inventory & Inspections**. Choose the apartment. Add item requirements by room, with an estimated per-item replacement cost. New actual quantities start at zero.

Save requirements as a named template and apply to other units. Applying adds missing item definitions only: it never overwrites an existing requirement or stock count. To revise a template, edit unit requirements and save a new named version.

Record good-stock receipts, good-stock transfers, repairs, or disposal of damaged stock. Movements have a mandatory reason/reference and retained history. These movements do not create purchase expenses or bank transactions. Central-store purchasing is not included in this release.

Use **Request inspection** to select an active maintainer, due date, instructions and inspection type. Routine, maintenance and cleaning inspections can be requested at any time without a booking. Check-in/check-out requests require a booking belonging to the unit. The existing Task Manager creates and assigns the inspection task and checklist; there is no separate inspection system.

## Staff application

Open **My Inspections** from the menu or **Inspect** in the bottom navigation. Only assigned tasks are visible. Open a request and choose **Start Inspection**. Complete the existing condition/photo checklist and the inventory Found/Damaged quantities. Damaged is a subset of Found. Enter evidence details in inventory notes and attach pictures in the room checklist.

Submitting closes the staff task and sends counts for office review. Staff cannot change actual balances or approve counts. Submitted inspections cannot be silently resubmitted.

## Review and damage estimates

The existing inspection detail page shows inventory review. Approving updates quantities with a movement log; optionally create an open Task Manager repair/replacement task for shortages/damage. Office assigns that follow-up task through the existing task workflow.

Stock changed after an inspection was started cannot be overwritten by its approval. Request a fresh inspection in that case. Missing quantities are `max(required - found, 0)`.

Checkout estimates compare against an approved inventory check-in for the same booking. Only increases in missing/damaged quantities are estimated using the saved per-item cost. Without a baseline, the screen says **No baseline**, not zero liability. Older generic checklists are not automatically converted into quantity baselines. Costs are estimates that require evidence/responsibility review, especially when stock moved during the stay. No guest deductions, deposit refunds, owner charges or financial entries are posted by inventory approval; use the existing approved deposit workflow separately.
