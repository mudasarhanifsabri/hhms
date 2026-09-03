# Security deposit wallet

Open **Bookings > booking details > Security Deposit Wallet** after applying migration 036.

## Receiving deposits

- If payment already exists, select **Allocate Existing Payment** and identify its deposit portion. This reclassifies that portion into Guest Security Deposits (2030); it does not collect money again or change the invoice total.
- For a new deposit-only payment, choose its unpaid invoice, date, account, reference and receipt. This also reduces that invoice's outstanding balance.
- A mixed invoice payment can specify its deposit portion in the usual payment form.
- An invoice marked Paid without an itemised ledger payment is not automatically treated as a received deposit. Reconcile its receipt first; do not create a second collection.

## Refunding

1. Request a refund against held funds. Optional deductions require descriptions and evidence. An inspection may be linked.
2. An admin approves or rejects with review notes. The current application has one admin role; requester and reviewer are recorded independently, but a separate manager role is not assumed.
3. Approval applies deductions but does not move cash. Deductions move from 2030 to **2095 - Guest Deposit Deductions Pending Allocation**. Their final allocation is not automatically treated as revenue or an owner expense.
4. Record actual refund payments with recipient, active bank/cash account, reference and proof. This records an already-made payment; it does not initiate a bank transfer. Partial payments are supported up to the approved remainder and held balance.
5. Download a receipt for each actual payment from Deposit History. Rejected requests cannot be paid. Duplicate submission identifiers do not create duplicate movements.

Deposit collections and refunds are excluded from dashboard income/expenses. They remain visible in bank statements and the liability ledger. Original invoice amounts, invoice payment records and owner rent statements are not reduced when a deposit is refunded.

## Renewal carry-forward

Create the linked renewal for the same guest and unit with **Security Deposit = 0**, then carry the existing held amount forward. Both booking wallets record the transfer, with no bank movement or second collection. Carry-forward is blocked while either booking has an open refund request, or if the renewal already charges a new deposit.

## Pending accounting work

- Full booking-charge account mapping and full tax-invoice changes remain pending. Deposit liability mapping is implemented separately by this change.
- Confirmed owner calculation: base rent minus management-fee percentage equals owner rent income; management fee is company income.
- User configuration confirmed on 3 September 2026: **no VAT on the management fee**. This is the requested software treatment, not a legal tax determination.
- Broader mapping for rent, management fees, cleaning, agency fees and DTCM fees has not been activated by the deposit-wallet update.

No live booking reversal or live refund is performed by deployment.
