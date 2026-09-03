# Booking payments and corrections

## Owner posting

After migration 037, newly created bookings and renewals use receipt-based owner posting. Existing bookings retain `legacy` posting until separately reconciled; this migration does not rewrite past owner statements. Extensions follow their booking's basis.

For receipt-based bookings, creating an invoice does not create owner ledger entries. The booking page shows expected uncollected rent. Each payment requires its rent-only allocation; rent excludes VAT, other fees, and the company-held security deposit. The deposit allocation is entered separately, within the same payment amount. The remainder covers VAT and other invoice charges. Server validation prevents allocations exceeding those charge balances.

The rent allocation credits the owner on the receipt date. The booking's saved management percentage produces a separate debit, with no additional management-fee VAT. Deposit collections and refunds never generate owner rent credits.

General company income-account mapping is not changed by this feature; allocation across rent, VAT and other company accounts still needs its separate accounting implementation.

## History & Corrections

- Edit Invoice: unpaid invoices without active payments or deposit activity only. Charges and linked original/renewal booking totals are synchronized; invoice number and audit history are retained. Existing legacy projected rent/management rows are synchronized, not converted to collection-based posting.
- Edit Payment Details: reference and notes only; the correction reason and before/after values are recorded. Amount/date/account/method remain protected.
- Reverse & Re-enter: eligible receipt-basis payments without deposit-wallet links. An offsetting ledger entry is created, related owner receipt postings are reversed, invoice balance is reopened and the bank balance recalculated. The original payment remains visible as reversed. Record the replacement separately. This is not a real bank refund.
- Legacy payments and deposit-linked payment amounts require reconciliation; automatic reversal is refused.
- Proof-only uploads cannot mark invoices paid. Use Record Payment.
- Bookings with payment or deposit history cannot be deleted.

Run migration 037 as part of deployment before using these screens. Regression coverage: BookingCorrectionsTest, DepositWalletTest, BookingExtensionRenewalTest, AccountingReportsTest and OwnerStatementOrderTest.
