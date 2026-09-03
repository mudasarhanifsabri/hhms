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

## VAT input and compact booking screens

Migration 038 stores each invoice's VAT input mode. The Edit Invoice popup accepts either rent including VAT or rent before VAT, previews the breakdown, and recalculates it again on the server. Reopening an inclusive invoice restores its gross rent input without adding VAT twice. Existing invoices default to the additive input view of their already-stored net rent; migration does not change their amounts.

Example at 5%: entering AED 10,500 with VAT Included produces net rent 10,000 and VAT 500; Add VAT produces net rent 10,500 and VAT 525. A separate AED 1,500 security deposit results in totals 12,000 and 12,525 respectively. Neither saving action records a payment or posts owner income.

Booking creation uses the same VAT choice. Overview and History both offer the invoice editor; all booking views share compact spacing and navigation. Bookings with invoices use a contact-only Edit Guest Details form, keeping charges, identity documents, unit and stay dates protected from accidental financial changes. Use invoice corrections and extension/renewal actions separately.
