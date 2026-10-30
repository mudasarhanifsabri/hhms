<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends BaseModel
{
    public const CATEGORIES = [
        // Utilities
        'electricity_water' => 'Electricity & Water',
        'cleaning' => 'Cleaning',
        'maintenance' => 'Maintenance',
        'dewa' => 'DEWA',
        'gas' => 'Gas',
        'internet' => 'Internet',
        'chiller' => 'Chiller',
        'television_streaming' => 'Television / Streaming',
        'telephone' => 'Telephone',
        'sewerage' => 'Sewerage',
        'utility_connection_deposit' => 'Utility Connection / Deposit',
        // Cleaning & housekeeping
        'checkout_cleaning' => 'Checkout Cleaning',
        'deep_cleaning' => 'Deep Cleaning',
        'housekeeping' => 'Housekeeping Service',
        'laundry_linen' => 'Laundry & Linen Cleaning',
        'carpet_cleaning' => 'Carpet Cleaning',
        'upholstery_cleaning' => 'Sofa / Curtain Cleaning',
        'pest_control' => 'Pest Control',
        'cleaning_materials' => 'Cleaning Materials',
        'waste_removal' => 'Waste Removal',
        // Maintenance & repairs
        'plumbing' => 'Plumbing',
        'electrical' => 'Electrical',
        'hvac' => 'Air Conditioning / HVAC',
        'appliance_repair' => 'Appliance Repair',
        'furniture_repair' => 'Furniture Repair',
        'carpentry' => 'Carpentry',
        'painting' => 'Painting',
        'locksmith_smartlock' => 'Locksmith / Smart Lock',
        'glass_aluminium' => 'Glass / Aluminium',
        'waterproofing' => 'Waterproofing',
        'civil_work' => 'Civil Work',
        'emergency_repair' => 'Emergency Repair',
        'maintenance_contract' => 'Annual Maintenance Contract',
        // Guest supplies & operations
        'supplies' => 'Guest Supplies',
        'guest_amenities' => 'Guest Amenities',
        'toiletries' => 'Toiletries',
        'linen_towels' => 'Linen & Towels',
        'kitchen_supplies' => 'Kitchen Supplies',
        'welcome_pack' => 'Welcome Pack',
        'drinking_water' => 'Drinking Water',
        'access_cards_keys' => 'Access Cards / Keys',
        'replacement_items' => 'Replacement Items',
        'guest_compensation' => 'Guest Compensation',
        'lost_damaged_items' => 'Lost / Damaged Items',
        'guest_support' => 'Luggage / Guest Support',
        'guest_transportation' => 'Guest Transportation',
        // Furniture & equipment
        'furniture_purchase' => 'Furniture Purchase',
        'appliances' => 'Appliances',
        'electronics' => 'Electronics',
        'kitchen_equipment' => 'Kitchen Equipment',
        'bedding_mattress' => 'Bedding / Mattress',
        'curtains_decoration' => 'Curtains / Decoration',
        'safety_equipment' => 'Safety Equipment',
        'smart_home_equipment' => 'Smart Home Equipment',
        'inventory_replenishment' => 'Inventory Replenishment',
        'furnishing_setup' => 'Furnishing / Setup Cost',
        // Building & community
        'service_charges' => 'Service Charges',
        'building_access' => 'Building Access Charges',
        'parking_charges' => 'Parking Charges',
        'community_charges' => 'Community Charges',
        'move_in_out' => 'Move-in / Move-out Charges',
        'building_noc' => 'Building Permit / NOC',
        'security_charges' => 'Security Charges',
        'facility_charges' => 'Facility Charges',
        // Government & compliance
        'dtcm_permit' => 'DTCM Permit',
        'tourism_dirham' => 'DTCM Tourism Dirham',
        'holiday_home_license' => 'Holiday Home License',
        'trade_license' => 'Trade License',
        'municipality_fee' => 'Municipality Fee',
        'government_fine' => 'Government Fine',
        'inspection_fee' => 'Inspection Fee',
        'ejari_registration' => 'Ejari / Registration',
        'civil_defence' => 'Civil Defence',
        'insurance' => 'Insurance',
        'visa_immigration' => 'Visa / Immigration Expense',
        // Booking, sales & distribution
        'commission' => 'Commission',
        'ota_commission' => 'OTA Commission',
        'booking_com_commission' => 'Booking.com Commission',
        'airbnb_commission' => 'Airbnb Commission',
        'travel_agency_commission' => 'Travel Agency Commission',
        'agent_commission' => 'Agent Commission',
        'payment_gateway_fee' => 'Payment Gateway Fee',
        'card_processing_fee' => 'Credit Card Processing Fee',
        'refund_cancellation' => 'Refund / Cancellation Cost',
        'promotion_discount' => 'Promotion / Discount',
        'photography_videography' => 'Photography / Videography',
        'listing_marketing' => 'Listing / Marketing',
        'channel_manager_software' => 'Channel Manager / Software',
        // Staff & contractors
        'maintainer_payment' => 'Maintainer Payment',
        'cleaner_payment' => 'Cleaner Payment',
        'driver_payment' => 'Driver Payment',
        'contractor_payment' => 'Contractor Payment',
        'staff_accommodation' => 'Staff Accommodation',
        'staff_transportation' => 'Staff Transportation',
        'staff_supplies' => 'Staff Supplies',
        'overtime_callout' => 'Overtime / Emergency Callout',
        // Administration & professional
        'accounting_fee' => 'Accounting Fee',
        'legal_fee' => 'Legal Fee',
        'bank_charges' => 'Bank Charges',
        'office_supplies' => 'Office Supplies',
        'printing_stationery' => 'Printing / Stationery',
        'software_subscription' => 'Software Subscription',
        'communication' => 'Communication',
        'courier' => 'Courier',
        'consultancy' => 'Professional Consultancy',
        // Owner & property charges
        'owner_approved' => 'Owner-Approved Expense',
        'management_fee' => 'Management Fee',
        'property_improvement' => 'Property Improvement',
        'renovation' => 'Renovation',
        'owner_advance_loan' => 'Owner Advance / Loan',
        'deposit_deduction' => 'Security Deposit Deduction',
        'utility_recovery' => 'Utility Recovery',
        'damage_recovery' => 'Damage Recovery',
        'owner_reimbursable' => 'Reimbursable Owner Expense',
        // General company
        'office_rent' => 'Office Rent',
        'company_utility' => 'Company Utility',
        'salaries' => 'Salaries',
        'vehicle_expense' => 'Vehicle Expense',
        'fuel' => 'Fuel',
        'company_insurance' => 'Company Insurance',
        'marketing' => 'Marketing',
        'training' => 'Training',
        'meals_entertainment' => 'Meals / Entertainment',
        'general_company' => 'General Company Expense',
        'license' => 'License / Permit',
        'other' => 'Other',
    ];

    public const CATEGORY_GROUPS = [
        'Utilities' => ['electricity_water', 'dewa', 'gas', 'internet', 'chiller', 'television_streaming', 'telephone', 'sewerage', 'utility_connection_deposit'],
        'Cleaning & Housekeeping' => ['cleaning', 'checkout_cleaning', 'deep_cleaning', 'housekeeping', 'laundry_linen', 'carpet_cleaning', 'upholstery_cleaning', 'pest_control', 'cleaning_materials', 'waste_removal'],
        'Maintenance & Repairs' => ['maintenance', 'plumbing', 'electrical', 'hvac', 'appliance_repair', 'furniture_repair', 'carpentry', 'painting', 'locksmith_smartlock', 'glass_aluminium', 'waterproofing', 'civil_work', 'emergency_repair', 'maintenance_contract'],
        'Guest Supplies & Operations' => ['supplies', 'guest_amenities', 'toiletries', 'linen_towels', 'kitchen_supplies', 'welcome_pack', 'drinking_water', 'access_cards_keys', 'replacement_items', 'guest_compensation', 'lost_damaged_items', 'guest_support', 'guest_transportation'],
        'Furniture & Equipment' => ['furniture_purchase', 'appliances', 'electronics', 'kitchen_equipment', 'bedding_mattress', 'curtains_decoration', 'safety_equipment', 'smart_home_equipment', 'inventory_replenishment', 'furnishing_setup'],
        'Building & Community' => ['service_charges', 'building_access', 'parking_charges', 'community_charges', 'move_in_out', 'building_noc', 'security_charges', 'facility_charges'],
        'Government & Compliance' => ['dtcm_permit', 'tourism_dirham', 'holiday_home_license', 'trade_license', 'municipality_fee', 'government_fine', 'inspection_fee', 'ejari_registration', 'civil_defence', 'insurance', 'visa_immigration', 'license'],
        'Booking, Sales & Distribution' => ['commission', 'ota_commission', 'booking_com_commission', 'airbnb_commission', 'travel_agency_commission', 'agent_commission', 'payment_gateway_fee', 'card_processing_fee', 'refund_cancellation', 'promotion_discount', 'photography_videography', 'listing_marketing', 'channel_manager_software'],
        'Staff & Contractors' => ['maintainer_payment', 'cleaner_payment', 'driver_payment', 'contractor_payment', 'staff_accommodation', 'staff_transportation', 'staff_supplies', 'overtime_callout'],
        'Administration & Professional' => ['accounting_fee', 'legal_fee', 'bank_charges', 'office_supplies', 'printing_stationery', 'software_subscription', 'communication', 'courier', 'consultancy'],
        'Owner & Property Charges' => ['owner_approved', 'management_fee', 'property_improvement', 'renovation', 'owner_advance_loan', 'deposit_deduction', 'utility_recovery', 'damage_recovery', 'owner_reimbursable'],
        'General Company Expenses' => ['office_rent', 'company_utility', 'salaries', 'vehicle_expense', 'fuel', 'company_insurance', 'marketing', 'training', 'meals_entertainment', 'general_company', 'other'],
    ];

    public static function categoryDefaults(string $category): array
    {
        $utility = ['electricity_water', 'dewa', 'gas', 'internet', 'chiller', 'television_streaming', 'telephone', 'sewerage', 'service_charges', 'community_charges', 'maintenance_contract', 'software_subscription', 'channel_manager_software'];
        $ownerBillable = ['electricity_water', 'dewa', 'gas', 'internet', 'chiller', 'service_charges', 'community_charges', 'building_access', 'parking_charges', 'move_in_out', 'building_noc', 'dtcm_permit', 'holiday_home_license', 'property_improvement', 'renovation', 'furnishing_setup', 'owner_approved', 'owner_reimbursable'];
        $company = ['office_rent', 'company_utility', 'salaries', 'vehicle_expense', 'fuel', 'company_insurance', 'marketing', 'training', 'meals_entertainment', 'general_company', 'accounting_fee', 'legal_fee', 'bank_charges', 'office_supplies', 'printing_stationery', 'communication', 'courier', 'consultancy'];

        return [
            'responsibility' => in_array($category, $company, true) ? 'company' : (in_array($category, $ownerBillable, true) ? 'owner' : 'company'),
            'owner_billable' => in_array($category, $ownerBillable, true),
            'recurring' => in_array($category, $utility, true),
            'vat_mode' => in_array($category, ['government_fine', 'tourism_dirham', 'deposit_deduction', 'owner_advance_loan'], true) ? 'none' : 'excluded',
        ];
    }

    protected $fillable = [
        'booking_task_id', 'staff_payment_status', 'staff_submission_id', 'owner_charge_invoice_id',
        'expense_no',
        'expense_date',
        'category',
        'vendor_id',
        'supplier',
        'property_id',
        'landlord_id',
        'booking_id',
        'responsibility',
        'paid_from_account_id',
        'owner_billable',
        'net_amount',
        'vat_rate',
        'cost_vat_mode',
        'vat_amount',
        'gross_amount',
        'sale_net_amount',
        'sale_vat_amount',
        'sale_gross_amount',
        'sale_vat_included',
        'sale_vat_rate',
        'sale_vat_mode',
        'profit_amount',
        'payment_method',
        'transaction_reference',
        'receipt_path',
        'invoice_path',
        'import_source_type',
        'import_source_file',
        'imported_transaction_id',
        'imported_payload',
        'needs_review',
        'approval_status',
        'owner_approval_status', 'owner_approval_remarks', 'owner_decision_remarks',
        'owner_approval_deadline', 'owner_approval_requested_at', 'owner_approval_sent_at',
        'owner_decided_at', 'owner_decided_by', 'quotation_path',
        'reversed_at', 'reversed_by', 'reversal_reason',
        'description',
        'accounting_entry_id',
        'created_by',
        'recurring_enabled', 'recurrence_day', 'next_recurrence_date', 'recurring_until',
        'recurring_parent_id', 'recurring_for_month',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'owner_billable' => 'boolean',
        'net_amount' => 'decimal:2',
        'vat_rate' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'sale_net_amount' => 'decimal:2',
        'sale_vat_amount' => 'decimal:2',
        'sale_gross_amount' => 'decimal:2',
        'sale_vat_included' => 'boolean',
        'sale_vat_rate' => 'decimal:2',
        'profit_amount' => 'decimal:2',
        'imported_payload' => 'array',
        'needs_review' => 'boolean',
        'reversed_at' => 'datetime',
        'owner_approval_deadline' => 'date',
        'owner_approval_requested_at' => 'datetime',
        'owner_approval_sent_at' => 'datetime',
        'owner_decided_at' => 'datetime',
        'recurring_enabled' => 'boolean',
        'next_recurrence_date' => 'date',
        'recurring_until' => 'date',
        'recurring_for_month' => 'date',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function landlord(): BelongsTo
    {
        return $this->belongsTo(User::class, 'landlord_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function paidFromAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class, 'paid_from_account_id');
    }

    public function accountingEntry(): BelongsTo
    {
        return $this->belongsTo(AccountingEntry::class);
    }

    public function ownerChargeInvoice(): BelongsTo
    {
        return $this->belongsTo(OwnerChargeInvoice::class);
    }

    public function audits()
    {
        return $this->hasMany(ExpenseAudit::class);
    }

    public function ownerDecisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_decided_by');
    }
}
