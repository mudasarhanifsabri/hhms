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
use App\Http\Controllers\admin\properties\BuildingController;
use App\Http\Controllers\admin\bookings\BookingController;
use App\Http\Controllers\admin\tasks\TaskController;
use App\Http\Controllers\admin\inspections\InspectionController;
use App\Http\Controllers\admin\accounting\AccountingController;



// Admin Routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

// AdminAccounting Routes
Route::get('/accounting', [AccountingController::class, 'dashboard'])->name('accounting.dashboard');
Route::get('/accounting/ledger', [AccountingController::class, 'ledger'])->name('accounting.ledger');
Route::post('/accounting/ledger', [AccountingController::class, 'storeEntry'])->name('accounting.ledger.store');
Route::get('/accounting/expenses', [AccountingController::class, 'expenses'])->name('accounting.expenses');
Route::post('/accounting/expenses', [AccountingController::class, 'storeExpense'])->name('accounting.expenses.store');
Route::get('/accounting/utilities', [AccountingController::class, 'utilities'])->name('accounting.utilities');
Route::post('/accounting/utilities/accounts', [AccountingController::class, 'storeUtilityAccount'])->name('accounting.utilities.accounts.store');
Route::post('/accounting/utilities/bills', [AccountingController::class, 'storeUtilityBill'])->name('accounting.utilities.bills.store');
Route::post('/accounting/utilities/bills/{bill}/pay', [AccountingController::class, 'payUtilityBill'])->name('accounting.utilities.bills.pay');
Route::get('/accounting/reports', [AccountingController::class, 'reports'])->name('accounting.reports');
Route::get('/accounting/vat', [AccountingController::class, 'vatReport'])->name('accounting.vat');
Route::get('/accounting/owner-statements', [AccountingController::class, 'ownerStatements'])->name('accounting.owner-statements');
Route::get('/accounting/owner-statements/pdf', [AccountingController::class, 'ownerStatementPdf'])->name('accounting.owner-statements.pdf');
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
Route::get('/properties/{property}/owner-documents', [PropertyOwnerDocumentController::class, 'index'])->name('property.owner-documents.index');
Route::post('/properties/{property}/owner-documents', [PropertyOwnerDocumentController::class, 'store'])->name('property.owner-documents.store');
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



// AdminBuilding Routes
Route::get('/buildings', [BuildingController::class, 'index'])->name('building.index');
Route::post('/buildings', [BuildingController::class, 'store'])->name('building.store');
Route::get('/buildings/by-landlord/{landlord}', [BuildingController::class, 'byLandlord']);


// To download the PDF list of all landlords

Route::get('/landlords/pdf/list', [LandlordController::class, 'genratePdf'])->name('landlord.pdf.list');
Route::get('/tenants/pdf/list', [TenantController::class, 'genratePdf'])->name('tenant.pdf.list');
Route::get('/agents/pdf/list', [AgentController::class, 'genratePdf'])->name('agent.pdf.list');
Route::get('/maintainers/pdf/list', [MaintainerController::class, 'genratePdf'])->name('maintainer.pdf.list');



});
