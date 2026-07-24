@php
    $unitNo = $property->name;
    $buildingName = $building->building_name ?? $building->name ?? '';
    $community = $property->community ?: trim($buildingName . ' ' . ($building->address ?? ''));
    $propertyType = $property->bedrooms ? $property->bedrooms . ' Bedroom' : ($property->category ?? 'Unit');
    $startDate = $document->sent_at ? $document->sent_at->format('d/m/Y') : now()->format('d/m/Y');
    $endDate = $document->expires_at ? $document->expires_at->format('d/m/Y') : now()->addYear()->format('d/m/Y');
    $logoPath = public_path('assets/images/pattern-contract-logo.png');
    $logoSrc = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
    $money = fn ($value) => number_format((float) $value, 2) . ' AED';
    $ownerShares = $property->relationLoaded('ownerShares') ? $property->ownerShares : $property->ownerShares()->with('owner')->get();
    if ($ownerShares->isEmpty() && $landlord) {
        $ownerShares = collect([(object) ['owner' => $landlord, 'share_percent' => 100, 'is_primary' => true]]);
    }

    $managementTerms = [
        [
            'en' => 'Definition of Terms and Conditions',
            'ar' => 'تعريف الشروط والأحكام',
            'items' => [
                ['Company means Pattern Vacation Homes Rental LLC, holder of Trade License No. 1123804, operating in Dubai, United Arab Emirates.', 'تعني الشركة باترن لتأجير بيوت العطلات ذ.م.م، حاملة الرخصة التجارية رقم 1123804 والعاملة في دبي، الإمارات العربية المتحدة.'],
                ['Owner means each person or entity holding legal ownership or ownership share in the Property.', 'يعني المالك كل شخص أو كيان يملك العقار أو حصة ملكية فيه.'],
                ['Property means the unit described in this agreement and approved for holiday home operation where applicable.', 'يعني العقار الوحدة الموضحة في هذه الاتفاقية والمعتمدة للتشغيل كبيت عطلات حيثما ينطبق.'],
                ['Effective Date means the signing date or document sending date unless another date is agreed in writing.', 'يعني تاريخ السريان تاريخ التوقيع أو تاريخ إرسال المستند ما لم يتم الاتفاق كتابة على تاريخ آخر.'],
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
                ['The Owner shall bear owner-side dues, bank charges, exchange differences, service charges, utility dues, and authority penalties unless agreed otherwise.', 'يتحمل المالك المستحقات الخاصة به والرسوم البنكية وفروقات الصرف ورسوم الخدمات وفواتير المرافق والغرامات الحكومية ما لم يتم الاتفاق على خلاف ذلك.'],
            ],
        ],
        [
            'en' => 'Obligations of the Company',
            'ar' => 'التزامات الشركة',
            'items' => [
                ['The Company shall manage guest communication, listing support, booking coordination, housekeeping coordination, maintenance follow-up, and revenue reporting.', 'تلتزم الشركة بإدارة تواصل النزلاء ودعم الإدراج وتنسيق الحجوزات والتنظيف ومتابعة الصيانة وإعداد تقارير الإيرادات.'],
                ['The Company shall coordinate check-in and check-out processes through authorized staff and systems.', 'تلتزم الشركة بتنسيق إجراءات الدخول والمغادرة من خلال الموظفين والأنظمة المعتمدة.'],
                ['The Company shall issue owner statements showing income, expenses, deductions, management fees, and payout balances.', 'تلتزم الشركة بإصدار كشوف حساب للمالك توضح الدخل والمصروفات والخصومات ورسوم الإدارة وأرصدة التحويل.'],
                ['The Company may coordinate urgent maintenance without prior approval where delay may affect guest safety, property condition, or active bookings.', 'يجوز للشركة تنسيق الصيانة العاجلة دون موافقة مسبقة إذا كان التأخير قد يؤثر على سلامة النزلاء أو حالة العقار أو الحجوزات القائمة.'],
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
            'en' => 'Owner Use, Bookings, and Operations',
            'ar' => 'استخدام المالك والحجوزات والتشغيل',
            'items' => [
                ['Owner personal use must be requested in advance and is subject to confirmed bookings and operational availability.', 'يجب طلب استخدام المالك الشخصي مسبقا ويخضع للحجوزات المؤكدة والتوفر التشغيلي.'],
                ['Existing confirmed bookings must be honored unless cancellation is approved and related penalties are settled.', 'يجب الالتزام بالحجوزات المؤكدة القائمة ما لم تتم الموافقة على الإلغاء وتسوية الغرامات المتعلقة به.'],
                ['The Company may block dates required for maintenance, cleaning, inspection, compliance, or operational readiness.', 'يجوز للشركة حجب تواريخ مطلوبة للصيانة أو التنظيف أو الفحص أو الامتثال أو الجاهزية التشغيلية.'],
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $document->title }} - {{ $document->reference_no }}</title>
    <style>
        @page { size: A4; margin: 18mm 15mm 16mm; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 10.8px; line-height: 1.45; }
        .page { width: 100%; background: #fff; }
        .page-break { page-break-before: always; }
        .topbar { display: table; width: 100%; border-bottom: 2px solid #96764a; padding-bottom: 8px; margin-bottom: 12px; }
        .brand-wrap { display: table-cell; width: 56%; vertical-align: middle; }
        .brand-logo { width: 255px; max-height: 62px; object-fit: contain; }
        .ref { display: table-cell; width: 44%; vertical-align: middle; text-align: right; color: #4b5563; font-size: 10px; }
        h1 { text-align: center; font-size: 17px; margin: 10px 0 2px; }
        .title-ar { text-align: center; direction: rtl; font-size: 15px; font-weight: 700; margin-bottom: 12px; }
        .intro { display: table; width: 100%; margin: 8px 0 10px; }
        .intro div { display: table-cell; width: 50%; vertical-align: top; }
        .ar { direction: rtl; text-align: right; }
        .section { display: table; width: 100%; background: #efefef; border: 1px solid #7f7f7f; margin-top: 8px; font-weight: 700; }
        .section div { display: table-cell; width: 50%; padding: 5px 7px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; margin: 0 0 8px; }
        th, td { border: 1px solid #c9d2d9; padding: 5px 6px; vertical-align: top; }
        th { background: #f5f5f5; text-align: left; font-weight: 700; }
        .amount { text-align: right; font-weight: 700; }
        .clause-title { display: table; width: 100%; background: #efefef; border: 1px solid #7f7f7f; margin-top: 8px; page-break-after: avoid; }
        .clause-title div { display: table-cell; width: 50%; padding: 6px 8px; font-weight: 700; }
        .clause-row { display: table; width: 100%; border-left: 1px solid #c9d2d9; border-right: 1px solid #c9d2d9; border-bottom: 1px solid #c9d2d9; page-break-inside: avoid; }
        .clause-row div { display: table-cell; width: 50%; padding: 6px 8px; vertical-align: top; }
        .signature { display: table; width: 100%; margin-top: 16px; page-break-inside: avoid; }
        .sigbox { display: table-cell; width: 50%; border: 1px solid #c9d2d9; min-height: 92px; padding: 9px; vertical-align: top; }
        .sigbox + .sigbox { border-left: 0; }
        .sigline { height: 40px; border-bottom: 1px solid #111; margin: 8px 0 5px; }
        .sigbox img { max-width: 210px; max-height: 70px; }
        .footer { border-top: 1px solid #96764a; margin-top: 12px; padding-top: 6px; color: #4b5563; text-align: center; font-size: 9px; }
        .compact p { margin: 7px 0; }
    </style>
</head>
<body>
<div class="page {{ $document->type === 'management_contract' ? '' : 'compact' }}">
    <div class="topbar">
        <div class="brand-wrap">
            @if($logoSrc)
                <img src="{{ $logoSrc }}" class="brand-logo" alt="Pattern Vacation Homes Rental">
            @else
                <strong>PATTERN Vacation Homes Rental</strong>
            @endif
        </div>
        <div class="ref">
            <div>Ref No. {{ $document->reference_no }}</div>
            <div>Date: {{ $startDate }}</div>
            <div>Valid Until: {{ $endDate }}</div>
        </div>
    </div>

    <h1>{{ $document->title }}</h1>
    <div class="title-ar">
        @if($document->type === 'noc')
            شهادة عدم ممانعة
        @elseif($document->type === 'management_letter')
            خطاب إدارة العقار
        @else
            عقد إدارة عقار
        @endif
    </div>

    @if($document->type === 'noc')
        <div class="intro">
            <div>I/We, the owner(s) listed below, confirm that there is no objection to Pattern Vacation Homes Rental LLC, Trade License No. 1123804, managing and operating the property described below as a holiday home and completing related authority, building, guest, cleaning, maintenance, and registration requirements.</div>
            <div class="ar">نحن المالكين المذكورين أدناه نقر بعدم الممانعة من قيام شركة باترن لتأجير بيوت العطلات ذ.م.م، رخصة تجارية رقم 1123804، بإدارة وتشغيل العقار الموضح أدناه كبيت عطلات واستكمال متطلبات الجهات المختصة والمبنى والنزلاء والتنظيف والصيانة والتسجيل.</div>
        </div>
    @elseif($document->type === 'management_letter')
        <div class="intro">
            <div>The owner(s) authorize Pattern Vacation Homes Rental LLC to manage the property below for holiday home operation from {{ $startDate }} until {{ $endDate }}, including coordination with Dubai Tourism and Commerce Marketing, building management, guests, cleaning, maintenance, and operational service providers.</div>
            <div class="ar">يفوض المالك/الملاك شركة باترن لتأجير بيوت العطلات ذ.م.م بإدارة العقار أدناه لتشغيله كبيت عطلات من {{ $startDate }} حتى {{ $endDate }}، بما يشمل التنسيق مع دائرة السياحة والتسويق التجاري بدبي وإدارة المبنى والنزلاء والتنظيف والصيانة ومزودي الخدمات التشغيلية.</div>
        </div>
    @else
        <div class="intro">
            <div>This contract constitutes an agreement to operate the property as a holiday home between Pattern Vacation Homes Rental LLC and the owner(s) listed below.</div>
            <div class="ar">يشكل هذا العقد اتفاقية لتشغيل العقار كبيت عطلات بين شركة باترن لتأجير بيوت العطلات ذ.م.م والمالك/الملاك المذكورين أدناه.</div>
        </div>
    @endif

    <div class="section"><div>1) Owner Details</div><div class="ar">1) بيانات المالك</div></div>
    <table>
        <thead>
            <tr>
                <th>Owner Name</th>
                <th>Email / Phone</th>
                <th>EID / Passport</th>
                <th>Share</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ownerShares as $share)
                <tr>
                    <td>{{ $share->owner?->name ?? $landlord?->name }}</td>
                    <td>{{ $share->owner?->email ?? $landlord?->email }}<br>{{ $share->owner?->phone ?? $landlord?->phone }}</td>
                    <td>{{ $share->owner?->eid_passport_no ?? $landlord?->eid_passport_no ?? 'N/A' }}</td>
                    <td>{{ number_format((float) $share->share_percent, 2) }}%</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section"><div>2) Property Details</div><div class="ar">2) بيانات العقار</div></div>
    <table>
        <tr><th>Property / Building</th><td>{{ $buildingName ?: 'N/A' }}</td><th class="ar">العقار / المبنى</th></tr>
        <tr><th>Unit No.</th><td>{{ $unitNo }}</td><th class="ar">رقم الوحدة</th></tr>
        <tr><th>Community</th><td>{{ $community ?: 'N/A' }}</td><th class="ar">المنطقة</th></tr>
        <tr><th>Property Type</th><td>{{ $propertyType }}</td><th class="ar">نوع الوحدة</th></tr>
        <tr><th>Floor No.</th><td>{{ $property->floor ?? 'N/A' }}</td><th class="ar">رقم الطابق</th></tr>
        <tr><th>DEWA Account No.</th><td>{{ $property->electricity_account_no ?? 'N/A' }}</td><th class="ar">رقم حساب ديوا</th></tr>
        <tr><th>DTCM Permit No.</th><td>{{ $property->dtcm_permit_no ?? 'N/A' }}</td><th class="ar">رقم تصريح دائرة السياحة</th></tr>
    </table>

    @if($document->type === 'management_contract')
        <div class="section"><div>3) Financial Schedule</div><div class="ar">3) الجدول المالي</div></div>
        <table>
            <tr><td>Furniture Supply & Installation</td><td class="amount">{{ $money($document->furniture_amount) }}</td><td class="ar">توريد وتركيب الأثاث</td></tr>
            <tr><td>Startup / DTCM Fee</td><td class="amount">{{ $money($document->startup_dtcm_fee) }}</td><td class="ar">رسوم بدء التشغيل / دائرة السياحة</td></tr>
            <tr><td>VAT 5% on Furniture Only</td><td class="amount">{{ $money($document->vat_amount) }}</td><td class="ar">ضريبة القيمة المضافة 5% على الأثاث فقط</td></tr>
            <tr><th>Grand Total</th><th class="amount">{{ $money($document->total_amount) }}</th><th class="ar">الإجمالي</th></tr>
            <tr><td>Management Fee</td><td class="amount">{{ $property->management_fee_percent ? number_format((float) $property->management_fee_percent, 2) . '%' : 'As agreed' }}</td><td class="ar">رسوم الإدارة</td></tr>
        </table>

        <div class="page-break"></div>
        <div class="topbar">
            <div class="brand-wrap">@if($logoSrc)<img src="{{ $logoSrc }}" class="brand-logo" alt="Pattern">@endif</div>
            <div class="ref"><div>Ref No. {{ $document->reference_no }}</div></div>
        </div>
        <div class="section"><div>4) Terms and Conditions</div><div class="ar">4) الشروط والأحكام</div></div>
        @foreach($managementTerms as $termIndex => $term)
            <div class="clause-title"><div>{{ $termIndex + 4 }}. {{ $term['en'] }}</div><div class="ar">{{ $termIndex + 4 }}. {{ $term['ar'] }}</div></div>
            @foreach($term['items'] as $itemIndex => $item)
                <div class="clause-row">
                    <div>{{ $termIndex + 4 }}.{{ $itemIndex + 1 }} {{ $item[0] }}</div>
                    <div class="ar">{{ $termIndex + 4 }}.{{ $itemIndex + 1 }} {{ $item[1] }}</div>
                </div>
            @endforeach
        @endforeach
    @endif

    <div class="signature">
        <div class="sigbox">
            <strong>Pattern Vacation Homes Rental LLC</strong><br>
            Authorized Signature
            <div class="sigline">Signature on file</div>
            Date: {{ $document->signed_at?->format('d/m/Y') ?? $startDate }}
        </div>
        <div class="sigbox ar">
            <strong>المالك / الملاك</strong><br>
            Name: {{ $signedByName ?: $landlord?->name }}
            <div class="sigline">@if($signatureData)<img src="{{ $signatureData }}" alt="Owner Signature">@endif</div>
            Date: {{ $document->signed_at?->format('d/m/Y H:i') ?? 'Pending signature' }}
        </div>
    </div>

    <div class="footer">
        Office 413, P.O. Box 1327, Al Attar Business Centre, Al-Barsha, Dubai, UAE | customerservice@pattern.ae | www.pattern.ae
    </div>
</div>
</body>
</html>
