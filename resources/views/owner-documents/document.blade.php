@php
    $unitNo = $property->name;
    $buildingName = $building->building_name ?? $building->name ?? '';
    $community = $property->community ?: trim($buildingName . ' ' . ($building->address ?? ''));
    $propertyType = $property->unit_type_label;
    $startDate = $document->sent_at ? $document->sent_at->format('d/m/Y') : now()->format('d/m/Y');
    $endDate = $document->expires_at ? $document->expires_at->format('d/m/Y') : now()->addYear()->format('d/m/Y');
    $money = fn ($value) => number_format((float) $value, 2) . ' AED';

    $assetData = function (string $path, string $mime = 'image/png') {
        return file_exists($path) ? 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path)) : null;
    };

    $logoSrc = $assetData(public_path('assets/images/pattern-contract-logo.png'));
    $companySignatureSrc = $assetData(public_path('assets/images/mr-sultan-signature.png'));
    $stampSrc = $assetData(public_path('assets/images/vacation-homes-rental-stamp.png'));

    $ownerShares = $property->relationLoaded('ownerShares') ? $property->ownerShares : $property->ownerShares()->with('owner')->get();
    if ($ownerShares->isEmpty() && $landlord) {
        $ownerShares = collect([(object) ['owner' => $landlord, 'share_percent' => 100, 'is_primary' => true]]);
    }

    $titles = [
        'noc' => ['en' => 'NO OBJECTION CERTIFICATE', 'ar' => 'شهادة عدم ممانعة'],
        'management_letter' => ['en' => 'Property Management Letter', 'ar' => 'خطاب توكيل لإدارة العقار'],
        'management_contract' => ['en' => 'Property Management Contract', 'ar' => 'عقد إدارة عقار'],
    ];
    $title = $titles[$document->type] ?? ['en' => $document->title, 'ar' => 'مستند'];

    $terms = [
        [
            'en' => 'Definition of Terms and Conditions',
            'ar' => 'تعريف الشروط والأحكام',
            'items' => [
                ['Company means Pattern Vacation Homes Rental LLC, holder of Trade License No. 1123804, operating in Dubai, United Arab Emirates.', 'تعني الشركة باترن لتأجير بيوت العطلات ذ.م.م، حاملة الرخصة التجارية رقم 1123804 والعاملة في دبي، الإمارات العربية المتحدة.'],
                ['Owner means each person or entity holding legal ownership or ownership share in the Property.', 'يعني المالك كل شخص أو كيان يملك العقار أو حصة ملكية فيه.'],
                ['Property means the unit described in this agreement and approved for holiday home operation where applicable.', 'يعني العقار الوحدة الموضحة في هذه الاتفاقية والمعتمدة للتشغيل كبيت عطلات حيثما ينطبق.'],
            ],
        ],
        [
            'en' => 'Obligations of the Owner',
            'ar' => 'التزامات المالك',
            'items' => [
                ['The Owner authorizes the Company to manage, market, lease, and operate the Property as a holiday home.', 'يفوض المالك الشركة بإدارة وتسويق وتأجير وتشغيل العقار كبيت عطلات.'],
                ['The Owner shall hand over the Property vacant, accessible, furnished where agreed, and ready for operation under authority requirements.', 'يلتزم المالك بتسليم العقار شاغرا وقابلا للدخول ومؤثثا حسب الاتفاق وجاهزا للتشغيل وفق متطلبات الجهات المختصة.'],
                ['The Owner shall provide all identity, title deed, permit, utility, community, access, and bank documents required for operation.', 'يلتزم المالك بتقديم جميع مستندات الهوية وسند الملكية والتصاريح والمرافق والمجتمع والدخول والبيانات البنكية المطلوبة للتشغيل.'],
                ['The Owner shall notify the Company of any legal, authority, building, or community restriction affecting the Property.', 'يلتزم المالك بإخطار الشركة بأي قيد قانوني أو حكومي أو متعلق بالمبنى أو المجتمع يؤثر على العقار.'],
            ],
        ],
        [
            'en' => 'Obligations of the Company',
            'ar' => 'التزامات الشركة',
            'items' => [
                ['The Company shall manage guest communication, listing support, booking coordination, housekeeping coordination, maintenance follow-up, and revenue reporting.', 'تلتزم الشركة بإدارة تواصل النزلاء ودعم الإدراج وتنسيق الحجوزات والتنظيف ومتابعة الصيانة وإعداد تقارير الإيرادات.'],
                ['The Company shall coordinate check-in and check-out processes through authorized staff and systems.', 'تلتزم الشركة بتنسيق إجراءات الدخول والمغادرة من خلال الموظفين والأنظمة المعتمدة.'],
                ['The Company shall issue owner statements showing income, expenses, deductions, management fees, and payout balances.', 'تلتزم الشركة بإصدار كشوف حساب للمالك توضح الدخل والمصروفات والخصومات ورسوم الإدارة وأرصدة التحويل.'],
                ['Maintenance charges may be processed without prior approval when the estimated cost does not exceed AED 500 unless another approval limit is agreed.', 'يجوز تنفيذ مصاريف الصيانة دون موافقة مسبقة عندما لا تتجاوز التكلفة التقديرية 500 درهم ما لم يتم الاتفاق على حد آخر.'],
            ],
        ],
        [
            'en' => 'Financial Obligations',
            'ar' => 'الالتزامات المالية',
            'items' => [
                ['The Owner shall pay the agreed management fee to the Company from rental income or as otherwise agreed in writing.', 'يلتزم المالك بدفع رسوم الإدارة المتفق عليها للشركة من دخل الإيجار أو حسب ما يتم الاتفاق عليه كتابة.'],
                ['Furniture, startup, DTCM, licensing, compliance, photography, onboarding, and setup fees shall be charged as stated in this agreement or approved separately.', 'تحتسب رسوم الأثاث وبدء التشغيل ورسوم دائرة السياحة والترخيص والامتثال والتصوير والإعداد كما هو مبين في هذه الاتفاقية أو حسب الموافقات المنفصلة.'],
                ['VAT shall be applied where required by UAE law and shown separately in invoices or financial schedules.', 'تطبق ضريبة القيمة المضافة حيثما يقتضي قانون دولة الإمارات ويتم بيانها بشكل منفصل في الفواتير أو الجداول المالية.'],
                ['Net owner payout shall be calculated after deducting approved expenses, management fee, refunds, penalties, payment charges, and agreed dues.', 'يتم احتساب صافي مستحقات المالك بعد خصم المصروفات المعتمدة ورسوم الإدارة والمبالغ المستردة والغرامات ورسوم الدفع والمستحقات المتفق عليها.'],
            ],
        ],
        [
            'en' => 'Termination and Renewal',
            'ar' => 'الإنهاء والتجديد',
            'items' => [
                ['The initial term is twelve months from the Effective Date unless renewed, extended, or terminated in writing.', 'تكون المدة الأولية اثني عشر شهرا من تاريخ السريان ما لم يتم تجديدها أو تمديدها أو إنهاؤها كتابة.'],
                ['Either party may terminate this agreement by giving thirty days written notice to the other party.', 'يجوز لأي طرف إنهاء هذه الاتفاقية بإشعار خطي مدته ثلاثون يوما للطرف الآخر.'],
                ['Before termination, both parties shall settle bookings, payouts, expenses, liabilities, penalties, and authority obligations.', 'قبل الإنهاء، يلتزم الطرفان بتسوية الحجوزات والتحويلات والمصروفات والالتزامات والغرامات والالتزامات الحكومية.'],
            ],
        ],
        [
            'en' => 'Applicable Law and Jurisdiction',
            'ar' => 'القانون الواجب التطبيق والاختصاص',
            'items' => [
                ['This agreement is governed by the laws of Dubai and the federal laws of the United Arab Emirates.', 'تخضع هذه الاتفاقية لقوانين دبي والقوانين الاتحادية لدولة الإمارات العربية المتحدة.'],
                ['Unresolved disputes shall be referred to the competent courts or authorities in Dubai.', 'تحال النزاعات غير المحلولة إلى المحاكم أو الجهات المختصة في دبي.'],
            ],
        ],
    ];
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $document->title }} - {{ $document->reference_no }}</title>
    <style>
        @page { size: A4; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #fff; color: #1c2b3a; font-family: dejavusans, Arial, sans-serif; font-size: 12px; line-height: 1.55; }
        .page { width: 210mm; min-height: 297mm; padding: 30px 42px 22px; position: relative; page-break-after: always; }
        .page:last-child { page-break-after: auto; }
        .ar { direction: rtl; text-align: right; font-family: xbriyaz, lateef, dejavusans, sans-serif; font-size: 14px; line-height: 1.65; }
        .top-bar { display: table; width: 100%; border-bottom: 2px solid #b98a5e; padding-bottom: 10px; margin-bottom: 16px; }
        .checker-pattern { display: table-cell; width: 88px; vertical-align: top; }
        .checker-pattern span { display: inline-block; width: 14px; height: 14px; margin: 1px; }
        .c1 { background: #d8c3a5; } .c2 { background: #b98a5e; } .c3 { background: #8a5a35; }
        .logo-block { display: table-cell; text-align: center; vertical-align: top; }
        .brand-logo { width: 255px; max-height: 64px; }
        .ref-block { display: table-cell; width: 170px; text-align: right; font-size: 11px; line-height: 1.7; vertical-align: top; padding-top: 4px; }
        .title-row { display: table; width: 100%; margin: 12px 0 18px; font-weight: 700; font-size: 16px; }
        .title-row div { display: table-cell; width: 50%; vertical-align: middle; }
        .title-center { text-align: center; font-size: 18px; font-weight: 700; letter-spacing: .5px; margin: 22px 0 26px; }
        .bi-row { display: table; width: 100%; margin-bottom: 8px; }
        .en-col { display: table-cell; width: 58%; padding-right: 18px; vertical-align: top; }
        .ar-col { display: table-cell; width: 42%; vertical-align: top; }
        .body-text { font-size: 13px; line-height: 1.85; }
        .body-text p { margin: 0 0 12px; }
        .sechead { display: table; width: 100%; background: #e5e6e2; border: 1px solid #c9d2d9; margin-top: 12px; font-weight: 700; }
        .sechead div { display: table-cell; width: 50%; padding: 6px 10px; }
        .subhead { display: table; width: 100%; background: #f1f2f0; border: 1px solid #c9d2d9; border-top: 0; color: #5b6b7a; font-style: italic; }
        .subhead div { display: table-cell; width: 50%; padding: 5px 10px; }
        table.datatable { width: 100%; border-collapse: collapse; border: 1px solid #c9d2d9; border-top: 0; table-layout: fixed; }
        table.datatable td, table.datatable th { border-bottom: 1px solid #c9d2d9; padding: 7px 10px; vertical-align: middle; }
        table.datatable th { background: #f1f2f0; }
        .label-en { width: 26%; }
        .value { width: 48%; text-align: center; font-weight: 600; }
        .label-ar { width: 26%; }
        .clause-title { display: table; width: 100%; background: #e5e6e2; border: 1px solid #c9d2d9; margin-top: 12px; font-weight: 700; page-break-after: avoid; }
        .clause-title div { display: table-cell; width: 50%; padding: 6px 10px; }
        .clause-row { display: table; width: 100%; border-left: 1px solid #c9d2d9; border-right: 1px solid #c9d2d9; border-bottom: 1px solid #c9d2d9; page-break-inside: avoid; }
        .clause-row div { display: table-cell; width: 50%; padding: 7px 10px; vertical-align: top; }
        .financial-table td { font-size: 12px; }
        .amount { text-align: center; font-weight: 700; }
        .sig-section { margin-top: 18px; page-break-inside: avoid; }
        .sig-grid { display: table; width: 100%; border: 1px solid #c9d2d9; }
        .sig-cell { display: table-cell; width: 50%; padding: 12px 16px; vertical-align: top; border-right: 1px solid #c9d2d9; min-height: 135px; }
        .sig-cell:last-child { border-right: 0; }
        .sig-heading { display: table; width: 100%; font-weight: 700; border-bottom: 1px solid #c9d2d9; padding-bottom: 6px; margin-bottom: 8px; }
        .sig-heading span { display: table-cell; width: 50%; }
        .sigline { height: 76px; border-top: 1px dashed #c9d2d9; margin-top: 8px; padding-top: 4px; text-align: center; }
        .sig-img { max-width: 190px; max-height: 68px; }
        .stamp-img { width: 95px; max-height: 95px; opacity: .92; }
        .stamp-fallback { display: inline-block; border: 3px solid #1450a3; color: #1450a3; border-radius: 50%; width: 95px; height: 95px; text-align: center; font-size: 8px; line-height: 1.25; padding-top: 28px; transform: rotate(-8deg); }
        .stamp-wrap { text-align: right; margin-top: -10px; height: 96px; }
        .footer { border-top: 3px solid #b98a5e; margin-top: 24px; padding-top: 10px; text-align: center; font-size: 10.5px; color: #333; line-height: 1.6; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
<div class="page">
    <div class="top-bar">
        <div class="checker-pattern">
            @foreach(['c1','c2','c1','c3','c2','c2','c1','c3','c1','c2','c3','c2','c1','c2','c1','c1','c3','c2','c1','c3','c2','c1','c3','c2','c1'] as $class)
                <span class="{{ $class }}"></span>
            @endforeach
        </div>
        <div class="logo-block">@if($logoSrc)<img src="{{ $logoSrc }}" class="brand-logo" alt="Pattern">@endif</div>
        <div class="ref-block">
            <div>Ref. No.: {{ $document->reference_no }}</div>
            <div>Date: {{ $startDate }}</div>
            <div>Valid Until: {{ $endDate }}</div>
        </div>
    </div>

    @if($document->type === 'noc')
        <div class="title-center">{{ $title['en'] }}</div>
        <div class="body-text">
            <p>I/We, the owner(s) listed below, being the rightful owner(s) of the apartment listed below:</p>
            <p><b>Property Details:</b> {{ $propertyType }}<br><b>Unit:</b> {{ $unitNo }}<br><b>Community:</b> {{ $community ?: 'N/A' }}</p>
            <p>Hereby confirm that I/we have no objection to Pattern Vacation Homes Rental LLC, holder of Trade License No. 1123804, to manage and operate my/our above-mentioned property on my/our behalf.</p>
            <p>This includes activities related to short-term rental management, guest handling, cleaning, maintenance coordination, and all necessary operations related to property leasing and hospitality services.</p>
            <p>This No Objection Certificate is issued upon my/our request to support management and registration requirements.</p>
        </div>
    @else
        <div class="title-row">
            <div>{{ $title['en'] }}</div>
            <div class="ar">{{ $title['ar'] }}</div>
        </div>
        <div class="bi-row">
            <div class="en-col">
                @if($document->type === 'management_letter')
                    The owner(s) authorize Pattern Vacation Homes Rental LLC to manage the property below for holiday home operation and related authority, building, guest, cleaning, maintenance, and service-provider coordination.
                @else
                    This contract constitutes an agreement to operate the property as a holiday home between Pattern Vacation Homes Rental LLC and the owner(s) listed below.
                @endif
            </div>
            <div class="ar-col ar">
                @if($document->type === 'management_letter')
                    يفوض المالك أو الملاك شركة باترن لتأجير بيوت العطلات ذ.م.م بإدارة العقار أدناه لتشغيله كبيت عطلات والتنسيق مع الجهات المختصة وإدارة المبنى والنزلاء والتنظيف والصيانة ومزودي الخدمات.
                @else
                    يشكل هذا العقد اتفاقية لتشغيل العقار كبيت عطلات بين شركة باترن لتأجير بيوت العطلات ذ.م.م والمالك أو الملاك المذكورين أدناه.
                @endif
            </div>
        </div>
    @endif

    <div class="sechead"><div>1) Owner Details</div><div class="ar">1) بيانات المالك</div></div>
    <table class="datatable">
        <tr><th>Owner Name</th><th>Email / Phone</th><th>EID / Passport</th><th>Share</th></tr>
        @foreach($ownerShares as $share)
            <tr>
                <td>{{ $share->owner?->name ?? $landlord?->name }}</td>
                <td>{{ $share->owner?->email ?? $landlord?->email }}<br>{{ $share->owner?->phone ?? $landlord?->phone }}</td>
                <td>{{ $share->owner?->eid_passport_no ?? $landlord?->eid_passport_no ?? 'N/A' }}</td>
                <td class="amount">{{ number_format((float) $share->share_percent, 2) }}%</td>
            </tr>
        @endforeach
    </table>

    <div class="sechead"><div>2) Property Detail</div><div class="ar">2) تفاصيل العقار</div></div>
    <table class="datatable">
        <tr><td class="label-en">Property Name</td><td class="value">{{ $buildingName ?: 'N/A' }}</td><td class="label-ar ar">اسم العقار</td></tr>
        <tr><td class="label-en">Floor No.</td><td class="value">{{ $property->floor ?? 'N/A' }}</td><td class="label-ar ar">رقم الطابق</td></tr>
        <tr><td class="label-en">Community</td><td class="value">{{ $community ?: 'N/A' }}</td><td class="label-ar ar">المنطقة</td></tr>
        <tr><td class="label-en">Property No.</td><td class="value">{{ $unitNo }}</td><td class="label-ar ar">رقم الوحدة</td></tr>
        <tr><td class="label-en">Property Type</td><td class="value">{{ $propertyType }}</td><td class="label-ar ar">نوع الوحدة</td></tr>
        <tr><td class="label-en">DEWA Account No.</td><td class="value">{{ $property->electricity_account_no ?? 'N/A' }}</td><td class="label-ar ar">رقم حساب ديوا</td></tr>
        <tr><td class="label-en">DTCM Permit No.</td><td class="value">{{ $property->dtcm_permit_no ?? 'N/A' }}</td><td class="label-ar ar">رقم تصريح دائرة السياحة</td></tr>
    </table>

    @if($document->type === 'management_contract')
        <div class="sechead"><div>3) Financial Liabilities</div><div class="ar">3) الالتزامات المالية</div></div>
        <table class="datatable financial-table">
            <tr><td>Furniture Supply & Installation</td><td class="amount">{{ $money($document->furniture_amount) }}</td><td class="ar">توريد وتركيب الأثاث</td></tr>
            <tr><td>Startup / DTCM Fee</td><td class="amount">{{ $money($document->startup_dtcm_fee) }}</td><td class="ar">رسوم بدء التشغيل / دائرة السياحة</td></tr>
            <tr><td>VAT 5% on Furniture Only</td><td class="amount">{{ $money($document->vat_amount) }}</td><td class="ar">ضريبة القيمة المضافة 5% على الأثاث فقط</td></tr>
            <tr><th>Grand Total</th><th class="amount">{{ $money($document->total_amount) }}</th><th class="ar">الإجمالي</th></tr>
            <tr><td>Management Fee</td><td class="amount">{{ $property->management_fee_percent ? number_format((float) $property->management_fee_percent, 2) . '%' : 'As agreed' }}</td><td class="ar">رسوم الإدارة</td></tr>
        </table>
    @endif

    <div class="sig-section">
        <div class="sig-grid">
            <div class="sig-cell">
                <div class="sig-heading"><span>The COMPANY</span><span class="ar">الشركة</span></div>
                Pattern Vacation Homes Rental LLC<br>
                Sultan Alhemeiri<br>
                Chief Executive Officer
                <div class="sigline">
                    @if($companySignatureSrc)<img src="{{ $companySignatureSrc }}" class="sig-img" alt="Signature">@else Signature on file @endif
                </div>
                Date: {{ $document->signed_at?->format('d/m/Y') ?? $startDate }}
            </div>
            <div class="sig-cell">
                <div class="sig-heading"><span>The OWNER</span><span class="ar">المالك</span></div>
                Name: {{ $signedByName ?: $landlord?->name }}<br>
                Signature
                <div class="sigline">@if($signatureData)<img src="{{ $signatureData }}" class="sig-img" alt="Owner Signature">@endif</div>
                Date: {{ $document->signed_at?->format('d/m/Y H:i') ?? 'Pending signature' }}
            </div>
        </div>
    </div>

    <div class="stamp-wrap">
        @if($stampSrc)<img src="{{ $stampSrc }}" class="stamp-img" alt="Stamp">@else <span class="stamp-fallback">PATTERN<br>VACATION HOMES<br>RENTAL</span>@endif
    </div>

    <div class="footer">
        Phone : +971 50 334 4887 - Email : customerservice@pattern.ae - www.pattern.ae<br>
        Office 413, P.O. Box 1327, Al Attar Business Centre, Al-Barsha, Dubai, UAE
    </div>
</div>

@if($document->type === 'management_contract')
    <div class="page">
        <div class="top-bar">
            <div class="checker-pattern">
                @foreach(['c1','c2','c1','c3','c2','c2','c1','c3','c1','c2','c3','c2','c1','c2','c1','c1','c3','c2','c1','c3','c2','c1','c3','c2','c1'] as $class)
                    <span class="{{ $class }}"></span>
                @endforeach
            </div>
            <div class="logo-block">@if($logoSrc)<img src="{{ $logoSrc }}" class="brand-logo" alt="Pattern">@endif</div>
            <div class="ref-block">Ref. No.: {{ $document->reference_no }}</div>
        </div>

        <div class="sechead"><div>4) Terms and Conditions</div><div class="ar">4) الشروط والأحكام</div></div>
        @foreach($terms as $termIndex => $term)
            <div class="clause-title"><div>{{ $termIndex + 4 }}. {{ $term['en'] }}</div><div class="ar">{{ $termIndex + 4 }}. {{ $term['ar'] }}</div></div>
            @foreach($term['items'] as $itemIndex => $item)
                <div class="clause-row">
                    <div>{{ $termIndex + 4 }}.{{ $itemIndex + 1 }} {{ $item[0] }}</div>
                    <div class="ar">{{ $termIndex + 4 }}.{{ $itemIndex + 1 }} {{ $item[1] }}</div>
                </div>
            @endforeach
        @endforeach

        <div class="footer">
            Phone : +971 50 334 4887 - Email : customerservice@pattern.ae - www.pattern.ae<br>
            Office 413, P.O. Box 1327, Al Attar Business Centre, Al-Barsha, Dubai, UAE
        </div>
    </div>
@endif
</body>
</html>
