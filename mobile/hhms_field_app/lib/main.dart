import 'package:flutter/material.dart';
import 'live_field_app.dart';

void main() => runApp(const LiveFieldApp());

const navy = Color(0xFF143248);
const teal = Color(0xFF12A99A);
const muted = Color(0xFF748895);
const paper = Color(0xFFF4F7F5);
const mint = Color(0xFFE5F5F1);
const amber = Color(0xFFE9A34A);

class ScreenSpec {
  const ScreenSpec(
    this.title,
    this.subtitle,
    this.role,
    this.kind,
    this.rows,
    this.action,
    this.next,
  );
  final String title, subtitle, role, kind, action;
  final List<String> rows;
  final int next;
}

const screens = <ScreenSpec>[
  ScreenSpec(
    'Sign in',
    'Secure access for the field team',
    '',
    'login',
    [],
    'Sign in',
    6,
  ),
  ScreenSpec(
    'Operations home',
    'Saturday, 19 September · Good morning, Sarah',
    'OPERATIONS',
    'home',
    [
      '12 inspections in progress',
      '05 awaiting review',
      '03 due today',
      'Request inspection|Assign a new checklist',
      'Review reports|5 reports need approval',
    ],
    'Open inspection queue',
    2,
  ),
  ScreenSpec(
    'Inspection queue',
    'Track requests from assignment to approval',
    'OPERATIONS',
    'list',
    [
      'INSP-0919-02|Check-out · Marina Residence 502|Review needed',
      'INSP-0919-03|Routine · Harbour View 101|In progress',
      'INSP-0918-08|Check-in · Marina Residence 701|Approved',
    ],
    'New inspection request',
    3,
  ),
  ScreenSpec(
    'Request inspection',
    'Create a clear request for the field team',
    'OPERATIONS',
    'form',
    [
      'Apartment|Marina Residence · 502',
      'Inspection type|Check-out inspection',
      'Related booking|BK-0919-001 · Omar Ali',
      'Assign to|Ahmed · Field technician',
      'Due date|20 Sep 2026',
      'Instructions|Check condition and count inventory',
    ],
    'Send inspection request',
    2,
  ),
  ScreenSpec(
    'Review inspection',
    'Check evidence before accepting the report',
    'OPERATIONS',
    'review',
    [
      'Submitted by Ahmed|Today at 10:42 · INSP-0919-02',
      '24 items checked|02 issues found',
      'Living room|4 good · 1 issue',
      'Kitchen|5 good · 0 issues',
      'Bedroom|5 good · 0 issues',
      'Evidence photos|2 uploaded',
    ],
    'Review inventory counts',
    5,
  ),
  ScreenSpec(
    'Approve inventory',
    'Confirm counted stock and record changes',
    'OPERATIONS',
    'stock',
    [
      'Kitchen · Glasses|Required 6|Found 5 · Damaged 1',
      'Living · TV remote|Required 1|Found 1 · Damaged 0',
      'Bedroom · Linen|Required 2|Found 2 · Damaged 0',
      'Review note|Verified evidence and counts',
    ],
    'Approve inventory',
    2,
  ),
  ScreenSpec(
    'Maintainer home',
    'Saturday, 19 September · Good morning, Ahmed',
    'MAINTAINER',
    'home',
    [
      '08 tasks assigned to you',
      '03 inspections',
      '02 due today',
      'My inspections|3 checklists assigned',
      'All tasks|Maintenance, cleaning & more',
    ],
    'Open my inspections',
    8,
  ),
  ScreenSpec(
    'My tasks',
    'Your assigned work in one place',
    'MAINTAINER',
    'list',
    [
      'TSK-0919-02|Check-out · Marina Residence 502|Assigned',
      'TSK-0919-04|AC maintenance · Harbour View 101|In progress',
      'TSK-0918-11|Cleaning · Palm Suites 207|Completed',
    ],
    'Open task details',
    9,
  ),
  ScreenSpec(
    'My inspections',
    'Assigned checklists ready for inspection',
    'MAINTAINER',
    'list',
    [
      'INSP-0919-02|Check-out · Marina Residence 502|Continue draft',
      'INSP-0919-03|Routine · Harbour View 101|Assigned',
      'INSP-0918-08|Check-in · Marina Residence 701|Submitted',
    ],
    'Continue inspection',
    9,
  ),
  ScreenSpec(
    'Task details',
    'Marina Residence · Apartment 502',
    'MAINTAINER',
    'detail',
    [
      'Check-out inspection|TSK-0919-02 · Due 20 Sep',
      'Assigned by|Sarah · Operations',
      'Priority|High',
      'Status|Assigned',
      'Instructions|Inspect every room and count inventory',
    ],
    'Accept task',
    10,
  ),
  ScreenSpec(
    'Accept task',
    'Confirm your schedule and start work',
    'MAINTAINER',
    'form',
    [
      'Expected completion|20 Sep 2026',
      'Initial remark|I will complete the inspection today',
      'Evidence photos|Optional',
    ],
    'Accept & start inspection',
    11,
  ),
  ScreenSpec(
    'Inspection rooms',
    'Check each room before submitting',
    'MAINTAINER',
    'rooms',
    [
      'Living room|2 of 5 checked',
      'Kitchen|0 of 5 checked',
      'Bedroom|0 of 5 checked',
      'Bathroom|0 of 5 checked',
      'Inventory|0 of 12 counted',
    ],
    'Continue living room',
    12,
  ),
  ScreenSpec(
    'Room checklist',
    'Living room · 2 of 5 checks complete',
    'MAINTAINER',
    'checklist',
    [
      'Sofa / chairs|Check upholstery, frame and cleanliness',
      'Condition|Good  ·  Issue  ·  N/A',
      'Remark|No visible damage',
      'Photo evidence|2 photos saved',
    ],
    'Add photo evidence',
    13,
  ),
  ScreenSpec(
    'Photo evidence',
    'Living room · Sofa / chairs',
    'MAINTAINER',
    'photo',
    [
      'Camera|Capture photo',
      'Gallery|Choose existing',
      'Sofa-front.jpg|Uploaded successfully',
      'Sofa-side.jpg|Waiting to upload · Retry',
    ],
    'Done · continue inspection',
    14,
  ),
  ScreenSpec(
    'Inventory count',
    'Count actual stock found in the unit',
    'MAINTAINER',
    'stock',
    [
      'Kitchen · Glasses|Required 6|Found 5 · Damaged 1',
      'Living · TV remote|Required 1|Found 1 · Damaged 0',
      'Bedroom · Linen|Required 2|Found 2 · Damaged 0',
      'Evidence note|One glass chipped',
    ],
    'Review inspection',
    15,
  ),
  ScreenSpec(
    'Review & submit',
    'Review your work and send it to Operations',
    'MAINTAINER',
    'review',
    [
      '24 items checked|02 issues noted',
      'Living room|4 good · 1 issue',
      'Kitchen|5 good · 0 issues',
      'Bedroom|5 good · 0 issues',
      'Inventory|12 items counted',
      'Final notes|One chipped glass',
      'Photos|All uploaded',
    ],
    'Submit inspection',
    16,
  ),
  ScreenSpec(
    'Submitted',
    'Your report is ready for office review',
    'MAINTAINER',
    'success',
    ['INSP-0919-02|Submitted at 10:42 AM', 'Status|Awaiting Operations review'],
    'Back to my inspections',
    8,
  ),
  ScreenSpec(
    'Task timeline',
    'Every update in chronological order',
    'MAINTAINER',
    'timeline',
    [
      'Inspection submitted|Today · 10:42',
      'Draft saved|Today · 10:38',
      'Evidence uploaded|Today · 10:21',
      'Task started|Today · 9:05',
      'Task accepted|Yesterday · 4:12',
      'Assigned by Operations|Yesterday · 3:50',
    ],
    'Add update',
    18,
  ),
  ScreenSpec(
    'Add update',
    'Keep Operations informed of progress',
    'MAINTAINER',
    'form',
    [
      'Status|In progress',
      'Update|Living room checked; kitchen next',
      'Evidence photos|Optional',
    ],
    'Send update',
    17,
  ),
  ScreenSpec(
    'Record cost',
    'Record labour, materials and other costs',
    'MAINTAINER',
    'form',
    [
      'Cost type|Material',
      'Item|Replacement glass',
      'Quantity|1',
      'Unit price (AED)|25.00',
      'Estimated total|AED 25.00',
    ],
    'Save cost',
    9,
  ),
  ScreenSpec(
    'Expense request',
    'Send a supplier invoice for office review',
    'MAINTAINER',
    'form',
    [
      'Payment status|Unpaid · office to pay',
      'Supplier|Marina Building Supplies',
      'Total (AED)|125.00',
      'Purpose|Replacement parts',
      'Supplier invoice|Add PDF or photo',
    ],
    'Send request to office',
    9,
  ),
  ScreenSpec(
    'Notifications',
    'Updates that need your attention',
    'MAINTAINER',
    'list',
    [
      'New inspection assigned|Marina Residence 502|10 min ago',
      'Report reviewed|Harbour View 101|Yesterday',
      'Task due tomorrow|Check-out inspection|20 Sep',
    ],
    'Open my tasks',
    7,
  ),
  ScreenSpec(
    'Profile',
    'Your account and app settings',
    'MAINTAINER',
    'detail',
    [
      'Ahmed Hassan|Field maintainer',
      'Email|ahmed@hhms.example',
      'Phone|+971 50 000 0000',
      'Notifications|Enabled',
      'Help & support|Inspection guidance',
    ],
    'Sign out',
    0,
  ),
];

class FieldApp extends StatelessWidget {
  const FieldApp({super.key, this.initialScreen = 0});
  final int initialScreen;
  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'HHMS Field',
    debugShowCheckedModeBanner: false,
    theme: ThemeData(
      useMaterial3: true,
      scaffoldBackgroundColor: paper,
      colorScheme: ColorScheme.fromSeed(seedColor: teal, primary: teal),
      fontFamily: 'Roboto',
    ),
    home: PreviewScreen(index: initialScreen),
  );
}

class PreviewScreen extends StatelessWidget {
  const PreviewScreen({super.key, required this.index});
  final int index;
  ScreenSpec get spec => screens[index];
  void go(BuildContext context, int target) => Navigator.of(
    context,
  ).push(MaterialPageRoute(builder: (_) => PreviewScreen(index: target)));

  @override
  Widget build(BuildContext context) => Scaffold(
    body: SafeArea(
      child: Column(
        children: [
          if (index != 0) _top(context),
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.fromLTRB(20, 20, 20, 25),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  if (index == 0)
                    _login(context)
                  else ...[
                    Text(
                      spec.title,
                      style: const TextStyle(
                        color: navy,
                        fontSize: 27,
                        fontWeight: FontWeight.w900,
                      ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      spec.subtitle,
                      style: const TextStyle(color: muted, fontSize: 13),
                    ),
                    const SizedBox(height: 19),
                    _body(context),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    ),
    bottomNavigationBar: index == 0
        ? null
        : Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                color: Colors.white,
                padding: const EdgeInsets.fromLTRB(20, 9, 20, 6),
                child: _button(context, spec.action, spec.next),
              ),
              _bottom(context),
            ],
          ),
  );

  Widget _top(BuildContext context) => Container(
    color: Colors.white,
    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
    child: Row(
      children: [
        InkWell(
          onTap: () => Navigator.canPop(context)
              ? Navigator.pop(context)
              : go(context, 0),
          child: const Icon(
            Icons.arrow_back_ios_new_rounded,
            size: 19,
            color: navy,
          ),
        ),
        const SizedBox(width: 13),
        Container(
          width: 34,
          height: 34,
          decoration: BoxDecoration(
            color: navy,
            borderRadius: BorderRadius.circular(10),
          ),
          child: const Icon(
            Icons.home_work_rounded,
            color: Colors.white,
            size: 20,
          ),
        ),
        const SizedBox(width: 7),
        const Text(
          'HHMS',
          style: TextStyle(
            color: navy,
            fontSize: 19,
            fontWeight: FontWeight.w900,
          ),
        ),
        const Spacer(),
        _pill(
          spec.role,
          spec.role == 'OPERATIONS' ? mint : const Color(0xFFFFF1DC),
          spec.role == 'OPERATIONS' ? teal : const Color(0xFFA46A20),
        ),
      ],
    ),
  );

  Widget _bottom(BuildContext context) {
    final ops = spec.role == 'OPERATIONS';
    final items = <(IconData, String, int)>[
      (Icons.home_outlined, 'Home', ops ? 1 : 6),
      (Icons.list_alt_outlined, ops ? 'Queue' : 'Tasks', ops ? 2 : 7),
      (Icons.fact_check_outlined, 'Inspect', ops ? 3 : 8),
      (Icons.notifications_none, 'Alerts', 21),
      (Icons.person_outline, 'Profile', 22),
    ];
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.only(top: 9, bottom: 8),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          for (final item in items)
            InkWell(
              onTap: () => go(context, item.$3),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Icon(
                    item.$1,
                    size: 23,
                    color: index == item.$3 ? teal : muted,
                  ),
                  const SizedBox(height: 3),
                  Text(
                    item.$2,
                    style: TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.w700,
                      color: index == item.$3 ? teal : muted,
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _body(BuildContext context) => Column(
    crossAxisAlignment: CrossAxisAlignment.start,
    children: [
      if (spec.kind == 'home') ..._home(context),
      if (spec.kind == 'list') ..._list(context),
      if (spec.kind == 'form') ..._form(context),
      if (spec.kind == 'review') ..._review(context),
      if (spec.kind == 'stock') ..._stock(context),
      if (spec.kind == 'detail') ..._detail(context),
      if (spec.kind == 'rooms') ..._rooms(context),
      if (spec.kind == 'checklist') ..._checklist(context),
      if (spec.kind == 'photo') ..._photo(context),
      if (spec.kind == 'success') ..._success(context),
      if (spec.kind == 'timeline') ..._timeline(context),
      const SizedBox(height: 12),
    ],
  );

  List<Widget> _home(BuildContext context) => [
    Container(
      width: double.infinity,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        color: navy,
        borderRadius: BorderRadius.circular(22),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'YOUR OVERVIEW',
            style: TextStyle(
              color: Color(0xFF9EDDD7),
              fontSize: 11,
              fontWeight: FontWeight.w800,
              letterSpacing: 1.2,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            spec.rows[0].split(' ').first,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 47,
              fontWeight: FontWeight.w900,
            ),
          ),
          Text(
            spec.rows[0].substring(3),
            style: const TextStyle(color: Colors.white, fontSize: 15),
          ),
        ],
      ),
    ),
    const SizedBox(height: 13),
    Row(
      children: [
        _metric(spec.rows[1]),
        const SizedBox(width: 12),
        _metric(spec.rows[2]),
      ],
    ),
    const SizedBox(height: 22),
    _heading('Quick actions'),
    for (final row in spec.rows.skip(3))
      _row(row, Icons.arrow_forward_ios_rounded, context),
  ];

  List<Widget> _list(BuildContext context) => [
    _search(),
    const SizedBox(height: 12),
    Row(
      children: [
        _pill('All', navy, Colors.white),
        const SizedBox(width: 8),
        _pill('Assigned', Colors.white, muted),
        const SizedBox(width: 8),
        _pill('In progress', Colors.white, muted),
      ],
    ),
    const SizedBox(height: 18),
    for (final row in spec.rows)
      _row(row, Icons.arrow_forward_ios_rounded, context),
  ];

  List<Widget> _form(BuildContext context) => [
    _notice(
      Icons.info_outline,
      index == 3
          ? 'Choose the unit and correct booking before assigning.'
          : 'Your changes are saved when you continue.',
    ),
    const SizedBox(height: 15),
    _card(
      Column(
        children: [
          for (final row in spec.rows)
            Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: _field(row),
            ),
        ],
      ),
    ),
  ];

  List<Widget> _review(BuildContext context) => [
    _notice(Icons.verified_outlined, spec.rows.first.replaceAll('|', ' · ')),
    const SizedBox(height: 15),
    Row(
      children: [
        _metric('24 items checked'),
        const SizedBox(width: 12),
        _metric('02 issues found'),
      ],
    ),
    const SizedBox(height: 20),
    _heading('Room results'),
    for (final row in spec.rows.skip(index == 4 ? 2 : 1))
      _row(row, Icons.check_circle_outline, context),
  ];

  List<Widget> _stock(BuildContext context) => [
    _notice(
      Icons.inventory_2_outlined,
      index == 5
          ? 'Counts update stock only after office approval.'
          : 'Damaged items are included in Found.',
    ),
    const SizedBox(height: 15),
    for (final row in spec.rows) _row(row, Icons.inventory_2_outlined, context),
  ];

  List<Widget> _detail(BuildContext context) => [
    _notice(
      Icons.assignment_turned_in_outlined,
      spec.rows.first.replaceAll('|', ' · '),
    ),
    const SizedBox(height: 15),
    _card(
      Column(
        children: [
          for (final row in spec.rows.skip(1))
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 10),
              child: Row(
                children: [
                  Text(
                    row.split('|').first,
                    style: const TextStyle(color: muted, fontSize: 13),
                  ),
                  const Spacer(),
                  Flexible(
                    child: Text(
                      row.split('|').last,
                      textAlign: TextAlign.right,
                      style: const TextStyle(
                        color: navy,
                        fontWeight: FontWeight.w800,
                        fontSize: 13,
                      ),
                    ),
                  ),
                ],
              ),
            ),
        ],
      ),
    ),
    if (index == 9) ...[
      const SizedBox(height: 17),
      _heading('Task actions'),
      _row(
        'Open inspection checklist|Continue saved draft',
        Icons.fact_check_outlined,
        context,
      ),
      _row('Task timeline|View updates', Icons.history, context),
    ],
  ];

  List<Widget> _rooms(BuildContext context) => [
    _progress('Rooms', 1, 6),
    _notice(Icons.cloud_done_outlined, 'Draft saved · Continue from any room.'),
    const SizedBox(height: 14),
    for (final row in spec.rows)
      _row(row, Icons.meeting_room_outlined, context),
  ];
  List<Widget> _checklist(BuildContext context) => [
    _progress('Living room', 2, 6),
    _card(
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'Sofa / chairs',
            style: TextStyle(
              color: navy,
              fontSize: 21,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 7),
          const Text(
            'Check upholstery, frame and cleanliness.',
            style: TextStyle(color: muted),
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              _choice('Good', true),
              const SizedBox(width: 7),
              _choice('Issue', false),
              const SizedBox(width: 7),
              _choice('N/A', false),
            ],
          ),
          const SizedBox(height: 19),
          _field('Remark|No visible damage'),
          const SizedBox(height: 17),
          _photoTiles(),
        ],
      ),
    ),
    const SizedBox(height: 14),
    _notice(Icons.cloud_done_outlined, 'Saved automatically · 10:38 AM'),
  ];
  List<Widget> _photo(BuildContext context) => [
    _progress('Photo evidence', 3, 6),
    _notice(
      Icons.privacy_tip_outlined,
      'Photos upload one at a time. Retry if the connection fails.',
    ),
    const SizedBox(height: 15),
    _card(
      Column(
        children: [
          const Icon(Icons.add_a_photo_outlined, size: 50, color: teal),
          const SizedBox(height: 10),
          const Text(
            'Show the condition clearly',
            style: TextStyle(
              color: navy,
              fontWeight: FontWeight.w900,
              fontSize: 18,
            ),
          ),
          const SizedBox(height: 7),
          const Text(
            'Up to 10 photos per item · JPG, PNG or WebP',
            style: TextStyle(color: muted),
          ),
          const SizedBox(height: 17),
          _photoTiles(),
        ],
      ),
    ),
    const SizedBox(height: 20),
    _heading('Upload status'),
    for (final row in spec.rows.skip(2))
      _row(row, Icons.cloud_upload_outlined, context),
  ];
  List<Widget> _success(BuildContext context) => [
    const SizedBox(height: 36),
    Center(
      child: Container(
        width: 110,
        height: 110,
        decoration: const BoxDecoration(color: mint, shape: BoxShape.circle),
        child: const Icon(Icons.check_circle_rounded, color: teal, size: 70),
      ),
    ),
    const SizedBox(height: 25),
    const Center(
      child: Text(
        'Inspection submitted',
        style: TextStyle(
          color: navy,
          fontSize: 27,
          fontWeight: FontWeight.w900,
        ),
      ),
    ),
    const SizedBox(height: 10),
    const Text(
      'Operations can now review your report and approve inventory counts.',
      textAlign: TextAlign.center,
      style: TextStyle(color: muted, height: 1.5),
    ),
    const SizedBox(height: 27),
    for (final row in spec.rows) _row(row, Icons.verified_outlined, context),
  ];
  List<Widget> _timeline(BuildContext context) => [
    for (final row in spec.rows) _row(row, Icons.history_rounded, context),
  ];

  Widget _login(BuildContext context) => SizedBox(
    height: 690,
    child: Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const SizedBox(height: 55),
        Container(
          width: 65,
          height: 65,
          decoration: BoxDecoration(
            color: navy,
            borderRadius: BorderRadius.circular(20),
          ),
          child: const Icon(
            Icons.home_work_rounded,
            color: Colors.white,
            size: 38,
          ),
        ),
        const SizedBox(height: 22),
        const Text(
          'HHMS Field',
          style: TextStyle(
            color: navy,
            fontSize: 33,
            fontWeight: FontWeight.w900,
          ),
        ),
        const SizedBox(height: 8),
        const Text(
          'Inspections and tasks, clearly in hand.',
          style: TextStyle(color: muted, fontSize: 16),
        ),
        const SizedBox(height: 36),
        _card(
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Welcome back',
                style: TextStyle(
                  color: navy,
                  fontSize: 20,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 20),
              _field('Email address|name@company.com'),
              const SizedBox(height: 15),
              _field('Password|••••••••'),
              const SizedBox(height: 23),
              _button(context, 'Sign in', 6),
              const SizedBox(height: 12),
              const Center(
                child: Text(
                  'Forgot password?',
                  style: TextStyle(color: teal, fontWeight: FontWeight.w800),
                ),
              ),
            ],
          ),
        ),
        const Spacer(),
        const Center(
          child: Text(
            'Operations & Maintainers',
            style: TextStyle(color: muted, fontSize: 12),
          ),
        ),
      ],
    ),
  );

  Widget _card(Widget child) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(17),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(19),
      boxShadow: const [
        BoxShadow(
          color: Color(0x0D143248),
          blurRadius: 17,
          offset: Offset(0, 5),
        ),
      ],
    ),
    child: child,
  );
  Widget _pill(String label, Color bg, Color fg) => Container(
    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
    decoration: BoxDecoration(
      color: bg,
      borderRadius: BorderRadius.circular(28),
    ),
    child: Text(
      label,
      style: TextStyle(color: fg, fontSize: 10, fontWeight: FontWeight.w900),
    ),
  );
  Widget _button(BuildContext context, String label, int target) => SizedBox(
    width: double.infinity,
    height: 51,
    child: ElevatedButton(
      onPressed: () => go(context, target),
      style: ElevatedButton.styleFrom(
        backgroundColor: teal,
        foregroundColor: Colors.white,
        elevation: 0,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
      ),
      child: Text(label, style: const TextStyle(fontWeight: FontWeight.w900)),
    ),
  );
  Widget _field(String row) {
    final bits = row.split('|');
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          bits.first,
          style: const TextStyle(
            color: navy,
            fontSize: 13,
            fontWeight: FontWeight.w800,
          ),
        ),
        const SizedBox(height: 7),
        Container(
          width: double.infinity,
          height: 47,
          alignment: Alignment.centerLeft,
          padding: const EdgeInsets.symmetric(horizontal: 13),
          decoration: BoxDecoration(
            color: paper,
            borderRadius: BorderRadius.circular(11),
          ),
          child: Text(
            bits.length > 1 ? bits[1] : '',
            overflow: TextOverflow.ellipsis,
            style: const TextStyle(color: Color(0xFF536B79), fontSize: 13),
          ),
        ),
      ],
    );
  }

  Widget _row(String row, IconData icon, BuildContext context) {
    final bits = row.split('|');
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: InkWell(
        onTap: () => go(context, spec.next),
        child: _card(
          Row(
            children: [
              Container(
                width: 42,
                height: 42,
                decoration: BoxDecoration(
                  color: mint,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: teal, size: 21),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      bits.first,
                      style: const TextStyle(
                        color: navy,
                        fontWeight: FontWeight.w800,
                        fontSize: 14,
                      ),
                    ),
                    if (bits.length > 1)
                      Text(
                        bits.sublist(1).join(' · '),
                        style: const TextStyle(color: muted, fontSize: 12),
                      ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_right_rounded, color: muted, size: 20),
            ],
          ),
        ),
      ),
    );
  }

  Widget _notice(IconData icon, String text) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(15),
    decoration: BoxDecoration(
      color: mint,
      borderRadius: BorderRadius.circular(15),
    ),
    child: Row(
      children: [
        Icon(icon, color: teal),
        const SizedBox(width: 11),
        Expanded(
          child: Text(
            text,
            style: const TextStyle(
              color: navy,
              fontWeight: FontWeight.w700,
              fontSize: 13,
            ),
          ),
        ),
      ],
    ),
  );
  Widget _heading(String text) => Padding(
    padding: const EdgeInsets.only(bottom: 11),
    child: Text(
      text,
      style: const TextStyle(
        color: navy,
        fontSize: 17,
        fontWeight: FontWeight.w900,
      ),
    ),
  );
  Widget _metric(String text) => Expanded(
    child: _card(
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            text.split(' ').first,
            style: const TextStyle(
              color: teal,
              fontSize: 27,
              fontWeight: FontWeight.w900,
            ),
          ),
          Text(
            text.substring(3),
            style: const TextStyle(color: muted, fontSize: 11),
          ),
        ],
      ),
    ),
  );
  Widget _search() => Container(
    height: 48,
    padding: const EdgeInsets.symmetric(horizontal: 14),
    decoration: BoxDecoration(
      color: Colors.white,
      borderRadius: BorderRadius.circular(13),
    ),
    child: const Row(
      children: [
        Icon(Icons.search, color: muted),
        SizedBox(width: 10),
        Expanded(
          child: Text(
            'Search inspections or tasks',
            overflow: TextOverflow.ellipsis,
            style: TextStyle(color: muted, fontSize: 13),
          ),
        ),
      ],
    ),
  );
  Widget _progress(String label, int step, int total) => Padding(
    padding: const EdgeInsets.only(bottom: 15),
    child: Column(
      children: [
        Row(
          children: [
            Text(
              label,
              style: const TextStyle(color: navy, fontWeight: FontWeight.w800),
            ),
            const Spacer(),
            Text(
              '$step / $total',
              style: const TextStyle(color: teal, fontWeight: FontWeight.w800),
            ),
          ],
        ),
        const SizedBox(height: 9),
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: LinearProgressIndicator(
            value: step / total,
            minHeight: 6,
            backgroundColor: const Color(0xFFDBE7E3),
            color: teal,
          ),
        ),
      ],
    ),
  );
  Widget _choice(String label, bool selected) => Expanded(
    child: Container(
      height: 41,
      alignment: Alignment.center,
      decoration: BoxDecoration(
        color: selected ? teal : paper,
        borderRadius: BorderRadius.circular(10),
      ),
      child: Text(
        label,
        style: TextStyle(
          color: selected ? Colors.white : navy,
          fontSize: 12,
          fontWeight: FontWeight.w800,
        ),
      ),
    ),
  );
  Widget _photoTiles() => Row(
    children: [
      for (var i = 0; i < 2; i++)
        Container(
          width: 77,
          height: 77,
          margin: const EdgeInsets.only(right: 8),
          decoration: BoxDecoration(
            color: i == 0 ? const Color(0xFFDCE5DC) : const Color(0xFFE8DDD0),
            borderRadius: BorderRadius.circular(11),
          ),
          child: const Icon(Icons.image_outlined, color: muted),
        ),
      Container(
        width: 77,
        height: 77,
        decoration: BoxDecoration(
          color: mint,
          borderRadius: BorderRadius.circular(11),
        ),
        child: const Icon(Icons.add_a_photo_outlined, color: teal),
      ),
    ],
  );
}
