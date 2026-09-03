# Automatic invoice settlement

Deploy with `php artisan migrate --force` before serving the updated payment pages, and refresh route/view caches using the normal deployment process.

New invoice receipts must equal the full outstanding balance. Staff select the receiving bank/cash account, date, payment method and reference; the invoice supplies rent, VAT, cleaning, agency, tourism, other fees and refundable deposit. Manual rent/deposit allocation inputs are no longer used.

Agency commission is calculated on the agency fee, not rent. The booking snapshots the agent profile percentage. An administrator can override the booking percentage with a reason before any payment history exists. The remaining agency fee is company agency income; commission is recorded as payable, not as an actual payout. Cleaning and management fees are separate income accounts. No additional VAT is calculated on management fees.

The bank receives the payment once. Non-bank allocation entries clear the receipt into owner payable, company income, VAT, tourism and agent liabilities. Security deposits remain in the company deposit wallet. Owner receipt posting continues through its existing receipt-based workflow.

Invoice-specific confirmations require that invoice to be fully paid through active payment records. Overall booking confirmations require all its invoices to be fully paid. A legacy paid flag alone does not unlock confirmations.

Historical payments are not rewritten. An older partial payment without stored allocation requires reconciliation before the remaining balance can be automatically settled. Existing deposit-linked payment reversals continue through the deposit/refund safeguards; commission payouts remain a separate accounting action.

New chart codes: 2096 receipt clearing, 2097 agent commission payable, 2098 tourism fees payable, 4100 management income, 4110 agency income. Existing account codes are preserved, not overwritten.
