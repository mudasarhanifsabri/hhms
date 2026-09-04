<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\AdminController;
use App\Http\Controllers\admin\landlords\LandlordController;
use App\Http\Controllers\admin\tenants\TenantController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\admin\agents\AgentController;
use App\Http\Controllers\admin\maintainers\MaintainerController;
use App\Http\Controllers\admin\properties\PropertyController;
use App\Http\Controllers\admin\properties\PropertyOwnerDocumentController;
use App\Http\Controllers\admin\properties\UnitDocumentController;
use App\Http\Controllers\admin\properties\BuildingController;
use App\Http\Controllers\admin\bookings\BookingController;
use App\Http\Controllers\admin\bookings\DepositController;
use App\Http\Controllers\admin\tasks\TaskController;
use App\Http\Controllers\admin\inspections\InspectionController;
use App\Http\Controllers\admin\accounting\AccountingController;
use App\Http\Controllers\admin\SettingsController;
use App\Http\Controllers\admin\SoftwareUpdateController;
use App\Http\Controllers\admin\DocumentOcrController;



// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
Route::get('/properties/{property}/guest-qr', [\App\Http\Controllers\Tenants\GuestAccessController::class, 'poster'])->name('property.guest-qr');
Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
Route::get('/software-update', [SoftwareUpdateController::class, 'index'])->name('software-update.index');
Route::post('/software-update', [SoftwareUpdateController::class, 'update'])->name('software-update.run');
Route::post('/document-ocr', [DocumentOcrController::class, 'scan'])->name('document-ocr.scan');

// AdminAccounting Routes
Route::get('/accounting', [AccountingController::class, 'dashboard'])->name('accounting.dashboard');
Route::get('/accounting/chart-of-accounts', [AccountingController::class, 'chartOfAccounts'])->name('accounting.chart-of-accounts');
Route::post('/accounting/chart-of-accounts', [AccountingController::class, 'storeAccount'])->name('accounting.chart-of-accounts.store');
Route::get('/accounting/chart-of-accounts/{account}/statement', [AccountingController::class, 'chartAccountStatement'])->name('accounting.chart-of-accounts.statement');
Route::get('/accounting/bank-accounts', [AccountingController::class, 'bankAccounts'])->name('accounting.bank-accounts');
Route::post('/accounting/bank-accounts', [AccountingController::class, 'storeBankAccount'])->name('accounting.bank-accounts.store');
Route::get('/accounting/bank-accounts/statements', [AccountingController::class, 'bankStatements'])->name('accounting.bank-statements');
Route::post('/accounting/bank-accounts/transfers', [AccountingController::class, 'transferBetweenAccounts'])->name('accounting.bank-accounts.transfer');
Route::put('/accounting/bank-accounts/{bankAccount}', [AccountingController::class, 'updateBankAccount'])->name('accounting.bank-accounts.update');
Route::get('/accounting/bank-accounts/{bankAccount}/statement', [AccountingController::class, 'bankAccountStatement'])->name('accounting.bank-account.statement');
Route::get('/accounting/vendors', [AccountingController::class, 'vendors'])->name('accounting.vendors');
Route::post('/accounting/vendors', [AccountingController::class, 'storeVendor'])->name('accounting.vendors.store');
Route::get('/accounting/ledger', [AccountingController::class, 'ledger'])->name('accounting.ledger');
Route::post('/accounting/ledger', [AccountingController::class, 'storeEntry'])->name('accounting.ledger.store');
Route::get('/accounting/expenses', [AccountingController::class, 'expenses'])->name('accounting.expenses');
Route::get('/accounting/expenses/report/pdf', [AccountingController::class, 'expenseReportPdf'])->name('accounting.expenses.report.pdf');
Route::get('/accounting/expenses/report/csv', [AccountingController::class, 'expenseReportCsv'])->name('accounting.expenses.report.csv');
Route::get('/e/{expense}', [AccountingController::class, 'expenseDocument'])->name('accounting.expenses.document');
Route::post('/accounting/expenses', [AccountingController::class, 'storeExpense'])->name('accounting.expenses.store');
Route::put('/accounting/expenses/{expense}', [AccountingController::class, 'updateExpense'])->name('accounting.expenses.update');
Route::delete('/accounting/expenses/{expense}', [AccountingController::class, 'destroyExpense'])->name('accounting.expenses.destroy');
Route::post('/accounting/expenses/{expense}/approve', [AccountingController::class, 'approveExpense'])->name('accounting.expenses.approve');
Route::get('/accounting/expenses/import', [AccountingController::class, 'importExpenses'])->name('accounting.expenses.import');
Route::post('/accounting/expenses/import/preview', [AccountingController::class, 'previewExpenseImport'])->name('accounting.expenses.import.preview');
Route::post('/accounting/expenses/import/confirm', [AccountingController::class, 'confirmExpenseImport'])->name('accounting.expenses.import.confirm');
Route::get('/accounting/utilities', [AccountingController::class, 'utilities'])->name('accounting.utilities');
Route::post('/accounting/utilities/accounts', [AccountingController::class, 'storeUtilityAccount'])->name('accounting.utilities.accounts.store');
Route::post('/accounting/utilities/bills', [AccountingController::class, 'storeUtilityBill'])->name('accounting.utilities.bills.store');
Route::post('/accounting/utilities/bills/{bill}/pay', [AccountingController::class, 'payUtilityBill'])->name('accounting.utilities.bills.pay');
Route::get('/accounting/reports', [AccountingController::class, 'reports'])->name('accounting.reports');
Route::get('/accounting/vat', [AccountingController::class, 'vatReport'])->name('accounting.vat');
Route::get('/accounting/owner-statements', [AccountingController::class, 'ownerStatements'])->name('accounting.owner-statements');
Route::get('/accounting/owner-statements/pdf', [AccountingController::class, 'ownerStatementPdf'])->name('accounting.owner-statements.pdf');
Route::delete('/accounting/owner-statements/entries/{entry}', [AccountingController::class, 'destroyOwnerStatementEntry'])->name('accounting.owner-statements.entries.destroy');
Route::get('/accounting/booking-invoices', [AccountingController::class, 'bookingInvoices'])->name('accounting.booking-invoices');
Route::get('/accounting/booking-invoices/{invoice}/pdf', [AccountingController::class, 'bookingInvoicePdf'])->name('accounting.booking-invoices.pdf');

// AdminLandlord Routes

Route::get('/landlords', [LandlordController::class, 'index'])->name('landlord.index');
Route::get('/landlords/grid', [LandlordController::class, 'showGrid'])->name('landlord.grid');
Route::get('/landlords/create', [LandlordController::class, 'create'])->name('landlord.create');
Route::post('/landlords/store', [LandlordController::class, 'store'])->name('landlord.store');
Route::post('/landlords/{id}/account-entries', [LandlordController::class, 'storeAccountEntry'])->name('landlord.account-entry.store');
Route::get('/landlords/{id}/account-statement', [LandlordController::class, 'accountStatement'])->name('landlord.account-statement');
Route::get('/landlords/{id}/account-statement/pdf', [LandlordController::class, 'accountStatementPdf'])->name('landlord.account-statement.pdf');
Route::get('/landlords/{id}/owned-properties', [LandlordController::class, 'ownedProperties'])->name('landlord.owned-properties');
Route::get('/landlords/{id}/security', [LandlordController::class, 'security'])->name('landlord.security');
Route::post('/landlords/{id}/security/reset-password', [LandlordController::class, 'resetTemporaryPassword'])->name('landlord.security.reset-password');
Route::post('/landlords/{id}/send-welcome-email', [LandlordController::class, 'sendWelcomeEmail'])->name('landlord.sendWelcomeEmail');
Route::get('/landlords/{id}', [LandlordController::class, 'show'])->name('landlord.show');
Route::delete('/landlords/{id}', [LandlordController::class, 'destroy'])->name('landlord.destroy');
Route::put('/landlords/{id}', [LandlordController::class, 'update'])->name('landlord.update');
Route::get('/landlords/{id}/edit', [LandlordController::class, 'edit'])->name('landlord.edit');
Route::put('/landlords/{id}/update-bank', [LandlordController::class, 'updateBankDetails'])->name('landlord.updateBank');

// AdminTenant Routes

Route::get('/tenants', [TenantController::class, 'index'])->name('tenant.index');
Route::get('/tenants/grid', [TenantController::class, 'showGrid'])->name('tenant.grid');
Route::get('/tenants/create', [TenantController::class, 'create'])->name('tenant.create');
Route::post('/tenants/store', [TenantController::class, 'store'])->name('tenant.store');
Route::get('/tenants/{id}', [TenantController::class, 'show'])->name('tenant.show');
Route::delete('/tenants/{id}', [TenantController::class, 'destroy'])->name('tenant.destroy');
Route::put('/tenants/{id}', [TenantController::class, 'update'])->name('tenant.update');
Route::get('/tenants/{id}/edit', [TenantController::class, 'edit'])->name('tenant.edit');
Route::put('/tenants/{id}/update-bank', [TenantController::class, 'updateBankDetails'])->name('tenant.updateBank');

// AdminAgent Routes

Route::get('/agents', [AgentController::class, 'index'])->name('agent.index');
Route::get('/agents/grid', [AgentController::class, 'showGrid'])->name('agent.grid');
Route::get('/agents/create', [AgentController::class, 'create'])->name('agent.create');
Route::post('/agents/store', [AgentController::class, 'store'])->name('agent.store');
Route::get('/agents/{id}', [AgentController::class, 'show'])->name('agent.show');
Route::delete('/agents/{id}', [AgentController::class, 'destroy'])->name('agent.destroy');
Route::put('/agents/{id}', [AgentController::class, 'update'])->name('agent.update');
Route::get('/agents/{id}/edit', [AgentController::class, 'edit'])->name('agent.edit');
Route::put('/agents/{id}/update-bank', [AgentController::class, 'updateBankDetails'])->name('agent.updateBank');

// AdminMaintainer Routes

Route::get('/maintainers', [MaintainerController::class, 'index'])->name('maintainer.index');
Route::get('/maintainers/grid', [MaintainerController::class, 'showGrid'])->name('maintainer.grid');
Route::get('/maintainers/create', [MaintainerController::class, 'create'])->name('maintainer.create');
Route::post('/maintainers/store', [MaintainerController::class, 'store'])->name('maintainer.store');
Route::get('/maintainers/{id}', [MaintainerController::class, 'show'])->name('maintainer.show');
Route::delete('/maintainers/{id}', [MaintainerController::class, 'destroy'])->name('maintainer.destroy');
Route::put('/maintainers/{id}', [MaintainerController::class, 'update'])->name('maintainer.update');
Route::get('/maintainers/{id}/edit', [MaintainerController::class, 'edit'])->name('maintainer.edit');
Route::put('/maintainers/{id}/update-bank', [MaintainerController::class, 'updateBankDetails'])->name('maintainer.updateBank');



//AdminProperty Routes


Route::get('/properties', [PropertyController::class, 'index'])->name('property.index');
Route::get('/properties/grid', [PropertyController::class, 'showGrid'])->name('property.grid');
Route::get('/properties/create', [PropertyController::class, 'create'])->name('property.create');
Route::post('/properties/draft', [PropertyController::class, 'saveDraft'])->name('property.draft');
Route::post('/properties/store', [PropertyController::class, 'store'])->name('property.store');
Route::get('/properties/dtcm-permits', [PropertyController::class, 'dtcmPermits'])->name('property.dtcm-permits');
Route::get('/properties/dtcm-permits/report/excel', [PropertyController::class, 'dtcmPermitsExcel'])->name('property.dtcm-permits.excel');
Route::get('/properties/dtcm-permits/report/pdf', [PropertyController::class, 'dtcmPermitsPdf'])->name('property.dtcm-permits.pdf');
Route::get('/properties/{property}/owner-documents', [PropertyOwnerDocumentController::class, 'index'])->name('property.owner-documents.index');
Route::post('/properties/{property}/owner-documents', [PropertyOwnerDocumentController::class, 'store'])->name('property.owner-documents.store');
Route::get('/properties/{property}/document-wallet', [UnitDocumentController::class, 'index'])->name('property.document-wallet.index');
Route::post('/properties/{property}/document-wallet', [UnitDocumentController::class, 'store'])->name('property.document-wallet.store');
Route::put('/properties/{property}/document-wallet/{document}', [UnitDocumentController::class, 'update'])->name('property.document-wallet.update');
Route::delete('/properties/{property}/document-wallet/{document}', [UnitDocumentController::class, 'destroy'])->name('property.document-wallet.destroy');
Route::get('/properties/{property}', [PropertyController::class, 'show'])->name('property.show');
Route::get('/properties/{property}/edit', [PropertyController::class, 'edit'])->name('property.edit');
Route::put('/properties/{property}', [PropertyController::class, 'update'])->name('property.update');
Route::delete('/properties/{property}', [PropertyController::class, 'destroy'])->name('property.destroy');

// AdminBooking Routes
Route::get('/bookings', [BookingController::class, 'index'])->name('booking.index');
Route::get('/bookings/grid', [BookingController::class, 'grid'])->name('booking.grid');
Route::get('/bookings/create', [BookingController::class, 'create'])->name('booking.create');
Route::post('/bookings', [BookingController::class, 'store'])->name('booking.store');
Route::get('/bookings/{booking}/edit', [BookingController::class, 'edit'])->name('booking.edit');
Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('booking.update');
  Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('booking.destroy');
  Route::post('/bookings/{booking}/extend', [BookingController::class, 'extend'])->name('booking.extend');
  Route::post('/bookings/{booking}/renew', [BookingController::class, 'renew'])->name('booking.renew');
  Route::post('/bookings/{booking}/check-in', [BookingController::class, 'checkIn'])->name('booking.check-in');
  Route::post('/bookings/{booking}/check-out', [BookingController::class, 'checkOut'])->name('booking.check-out');
  Route::get('/bookings/{booking}', [BookingController::class, 'show'])->name('booking.show');
Route::get('/bookings/{booking}/history', [BookingController::class, 'history'])->name('booking.history');
Route::post('/bookings/{booking}/payment-proof', [BookingController::class, 'attachPaymentProof'])->name('booking.payment-proof');
Route::post('/booking-invoices/{invoice}/payments', [BookingController::class, 'recordInvoicePayment'])->name('booking-invoice.payment');
Route::get('/booking-invoices/{invoice}/receipt', [BookingController::class, 'paymentReceipt'])->name('booking-invoice.receipt');
Route::get('/booking-invoices/{invoice}/confirmation', [BookingController::class, 'invoiceConfirmation'])->name('booking-invoice.confirmation');
Route::put('/bookings/{booking}/agent-commission', [BookingController::class, 'commission'])->name('booking.agent-commission');
Route::put('/agents/{agent}/commission-rate', [AgentController::class, 'commission'])->name('agent.commission');
Route::put('/booking-invoices/{invoice}/correct', [\App\Http\Controllers\admin\bookings\BookingCorrectionController::class, 'invoice'])->name('booking-invoice.correct');
Route::put('/booking-payments/{payment}/details', [\App\Http\Controllers\admin\bookings\BookingCorrectionController::class, 'paymentDetails'])->name('booking-payment.details');
Route::post('/booking-payments/{payment}/reverse', [\App\Http\Controllers\admin\bookings\BookingCorrectionController::class, 'reversePayment'])->name('booking-payment.reverse');
Route::post('/bookings/{booking}/prepare-checkout', [BookingController::class, 'prepareCheckout'])->name('booking.prepare-checkout');
Route::post('/bookings/{booking}/reverse-checkout', [BookingController::class, 'reverseCheckout'])->name('booking.reverse-checkout');
Route::get('/bookings/{booking}/deposit-wallet', [DepositController::class, 'index'])->name('booking.deposit-wallet');
Route::post('/bookings/{booking}/deposit-wallet/collect', [DepositController::class, 'collect'])->name('booking.deposit.collect');
Route::post('/bookings/{booking}/deposit-wallet/allocate', [DepositController::class, 'allocate'])->name('booking.deposit.allocate');
Route::post('/bookings/{booking}/deposit-wallet/refunds', [DepositController::class, 'requestRefund'])->name('booking.deposit.request');
Route::post('/bookings/{booking}/deposit-wallet/refunds/{refund}/review', [DepositController::class, 'review'])->name('booking.deposit.review');
Route::post('/bookings/{booking}/deposit-wallet/refunds/{refund}/pay', [DepositController::class, 'pay'])->name('booking.deposit.pay');
Route::post('/bookings/{booking}/deposit-wallet/carry', [DepositController::class, 'carry'])->name('booking.deposit.carry');
Route::get('/bookings/{booking}/deposit-wallet/receipt/{entry}', [DepositController::class, 'receipt'])->name('booking.deposit.receipt');
Route::get('/bookings/{booking}/invoice', [BookingController::class, 'invoice'])->name('booking.invoice');
Route::get('/bookings/{booking}/confirmation', [BookingController::class, 'confirmation'])->name('booking.confirmation');

// AdminTaskManager Routes
Route::get('/task-manager', [TaskController::class, 'index'])->name('task.index');
Route::get('/task-manager/grid', [TaskController::class, 'grid'])->name('task.grid');
Route::post('/task-manager', [TaskController::class, 'store'])->name('task.store');
Route::put('/task-manager/{task}', [TaskController::class, 'update'])->name('task.update');
Route::get('/task-manager/{task}', [TaskController::class, 'show'])->name('task.show');

// AdminInspection Routes
Route::get('/inspections', [InspectionController::class, 'index'])->name('inspection.index');
Route::get('/inspections/{inspection}', [InspectionController::class, 'show'])->name('inspection.show');
Route::get('/inspections/{inspection}/pdf', [InspectionController::class, 'pdf'])->name('inspection.pdf');
Route::get('/unit-inventory', [\App\Http\Controllers\admin\inspections\InventoryController::class, 'index'])->name('inventory.index');
Route::post('/unit-inventory', [\App\Http\Controllers\admin\inspections\InventoryController::class, 'store'])->name('inventory.store');
Route::put('/unit-inventory/{item}', [\App\Http\Controllers\admin\inspections\InventoryController::class, 'update'])->name('inventory.update');
Route::post('/unit-inventory/{item}/movement', [\App\Http\Controllers\admin\inspections\InventoryController::class, 'move'])->name('inventory.move');
Route::post('/inspection-requests', [\App\Http\Controllers\admin\inspections\InventoryController::class, 'requestInspection'])->name('inspection.request');
Route::post('/inventory-templates', [\App\Http\Controllers\admin\inspections\InventoryController::class, 'template'])->name('inventory.template');
Route::post('/inspections/{inspection}/inventory-approval', [\App\Http\Controllers\admin\inspections\InventoryController::class, 'approve'])->name('inventory.approve');



// AdminBuilding Routes
Route::get('/buildings', [BuildingController::class, 'index'])->name('building.index');
Route::post('/buildings', [BuildingController::class, 'store'])->name('building.store');
Route::get('/buildings/by-landlord/{landlord}', [BuildingController::class, 'byLandlord']);


// To download the PDF list of all landlords

Route::get('/landlords/pdf/list', [LandlordController::class, 'genratePdf'])->name('landlord.pdf.list');
Route::get('/landlords/excel/list', [LandlordController::class, 'exportExcel'])->name('landlord.excel.list');
Route::get('/tenants/pdf/list', [TenantController::class, 'genratePdf'])->name('tenant.pdf.list');
Route::get('/agents/pdf/list', [AgentController::class, 'genratePdf'])->name('agent.pdf.list');
Route::get('/maintainers/pdf/list', [MaintainerController::class, 'genratePdf'])->name('maintainer.pdf.list');



});
