import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:uuid/uuid.dart';
import 'package:url_launcher/url_launcher.dart';

typedef FieldRequest =
    Future<Map<String, dynamic>> Function(
      String method,
      String path, [
      Map<String, dynamic>? data,
    ]);
typedef FieldUpload =
    Future<Map<String, dynamic>> Function(
      String path,
      Map<String, String> fields,
      XFile file, [
      Map<String, XFile>? extras,
    ]);

const _navy = Color(0xFF143248);
const _teal = Color(0xFF12A99A);
const _muted = Color(0xFF748895);
const _paper = Color(0xFFF4F7F5);
const _mint = Color(0xFFE5F5F1);
const _origin = 'https://rms.pattern.ae';

class NativeFieldApp extends StatefulWidget {
  const NativeFieldApp({
    super.key,
    required this.user,
    required this.request,
    required this.upload,
    required this.signOut,
  });
  final Map<String, dynamic> user;
  final FieldRequest request;
  final FieldUpload upload;
  final Future<void> Function() signOut;

  @override
  State<NativeFieldApp> createState() => _NativeFieldAppState();
}

class _NativeFieldAppState extends State<NativeFieldApp> {
  final _picker = ImagePicker();
  final _notes = TextEditingController();
  final _text = TextEditingController();
  final _amount = TextEditingController();
  final _secondary = TextEditingController();
  List<Map<String, dynamic>> _tasks = [];
  List<Map<String, dynamic>> _inspections = [];
  Map<String, dynamic> _task = {};
  Map<String, dynamic> _inspection = {};
  Map<String, dynamic> _options = {};
  final Map<String, String> _conditions = {};
  final Map<String, String> _comments = {};
  final Map<String, String> _found = {};
  final Map<String, String> _damaged = {};
  final Map<String, String> _inventoryNotes = {};
  String _page = 'home';
  String? _selectedProperty;
  String? _selectedMaintainer;
  String? _selectedBooking;
  String _selectedType = 'routine';
  String _costType = 'other';
  String _paymentStatus = 'unpaid';
  bool _busy = false;
  String? _error;
  int _roomIndex = 0;
  XFile? _expenseInvoice;
  XFile? _expenseReceipt;

  Future<void> _pickExpenseFile(bool receipt) async {
    final selection = await FilePicker.pickFile(
      type: FileType.custom,
      allowedExtensions: ['pdf', 'jpg', 'jpeg', 'png', 'webp'],
    );
    final path = selection?.path;
    if (path != null && mounted) {
      setState(() {
        if (receipt) {
          _expenseReceipt = XFile(path);
        } else {
          _expenseInvoice = XFile(path);
        }
      });
    }
  }

  bool get _admin => widget.user['role'] == 'admin';
  String get _taskId => _task['id']?.toString() ?? '';
  String get _inspectionId => _inspection['id']?.toString() ?? '';
  bool get _isInspectionTask =>
      const ['inspection', 'checkout_inspection'].contains(_task['type']);
  List<Map<String, dynamic>> get _items => _maps(_inspection['items']);
  List<Map<String, dynamic>> get _inventory => _maps(_inspection['inventory']);
  List<String> get _rooms =>
      _items.map((e) => e['area']?.toString() ?? 'Room').toSet().toList();

  @override
  void initState() {
    super.initState();
    _refresh();
  }

  @override
  void dispose() {
    _notes.dispose();
    _text.dispose();
    _amount.dispose();
    _secondary.dispose();
    super.dispose();
  }

  List<Map<String, dynamic>> _maps(dynamic value) => value is List
      ? value.whereType<Map>().map((e) => e.cast<String, dynamic>()).toList()
      : [];

  Map<String, dynamic> _map(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) {
      return value.map((key, item) => MapEntry(key.toString(), item));
    }
    if (value is List && value.isNotEmpty) return _map(value.first);
    return <String, dynamic>{};
  }

  String _friendlyError(Object error) {
    final raw = error.toString().replaceFirst('Exception: ', '');
    debugPrint('HHMS Field error: $raw');
    final lower = raw.toLowerCase();
    if (lower.contains('type cast') ||
        lower.contains('is not a subtype') ||
        lower.contains('unexpected data')) {
      return 'Some inspection data could not be read. Refresh and try again.';
    }
    if (lower.contains('timed out') ||
        lower.contains('socket') ||
        lower.contains('network') ||
        lower.contains('connection')) {
      return 'Connection is slow or unavailable. Your saved work is safe; try again.';
    }
    return raw.isEmpty ? 'Something went wrong. Please try again.' : raw;
  }

  Future<void> _run(Future<void> Function() action) async {
    if (_busy) return;
    setState(() {
      _busy = true;
      _error = null;
    });
    try {
      await action();
    } catch (e) {
      if (mounted) {
        setState(() => _error = _friendlyError(e));
      }
    } finally {
      if (mounted) setState(() => _busy = false);
    }
  }

  Future<void> _refresh() => _run(() async {
    final response = await widget.request(
      'GET',
      _admin ? '/field/api/inspections' : '/field/api/tasks',
    );
    if (!mounted) return;
    setState(() {
      if (_admin) {
        _inspections = _maps(response['inspections']);
      } else {
        _tasks = _maps(response['tasks']);
      }
    });
  });

  Future<void> _openTask(Map<String, dynamic> task) => _run(() async {
    final response = await widget.request(
      'GET',
      '/field/api/tasks/${task['id']}',
    );
    if (mounted) {
      setState(() {
        _task = _map(response['task']);
        _secondary.text = _task['due_date']?.toString() ?? '';
        _text.clear();
        _page = 'task';
      });
    }
  });

  Future<void> _openInspection() => _run(() async {
    final response = await widget.request(
      'GET',
      '/field/api/tasks/$_taskId/inspection',
    );
    final inspection = _map(response['inspection']);
    _conditions.clear();
    _comments.clear();
    _found.clear();
    _damaged.clear();
    _inventoryNotes.clear();
    for (final item in _maps(inspection['items'])) {
      final id = item['id'].toString();
      if (item['condition'] != null) {
        _conditions[id] = item['condition'].toString();
      }
      _comments[id] = item['comment']?.toString() ?? '';
    }
    for (final row in _maps(inspection['inventory'])) {
      final id = row['id'].toString();
      _found[id] = row['draft_found']?.toString() ?? '';
      _damaged[id] = row['draft_damaged']?.toString() ?? '';
      _inventoryNotes[id] = row['draft_notes']?.toString() ?? '';
    }
    _notes.text = _map(inspection['draft'])['notes']?.toString() ?? '';
    if (mounted) {
      setState(() {
        _inspection = inspection;
        _roomIndex = 0;
        _page = 'rooms';
      });
    }
  });

  Future<void> _saveDraft() => _run(() async {
    final items = <String, dynamic>{};
    for (final item in _items) {
      final id = item['id'].toString();
      items[id] = {
        'condition': _conditions[id],
        'comment': _comments[id] ?? '',
      };
    }
    final inventory = <String, dynamic>{};
    for (final row in _inventory) {
      final id = row['id'].toString();
      inventory[id] = {
        'found': _found[id],
        'damaged': _damaged[id],
        'notes': _inventoryNotes[id] ?? '',
      };
    }
    final response = await widget
        .request('POST', '/maintainer/tasks/$_taskId/inspection/draft', {
          'revision': _inspection['revision'],
          'items': items,
          'inventory': inventory,
          'notes': _notes.text,
        });
    if (mounted) setState(() => _inspection['revision'] = response['revision']);
  });

  Future<void> _addPhoto(String itemId) => _run(() async {
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined),
              title: const Text('Camera'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined),
              title: const Text('Gallery'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
          ],
        ),
      ),
    );
    if (source == null) return;
    final item = _items.firstWhere((row) => row['id'].toString() == itemId);
    final remaining = 5 - ((item['photos'] as List?)?.length ?? 0);
    if (remaining <= 0) throw Exception('Maximum 5 photos per item.');

    final List<XFile> files;
    if (source == ImageSource.gallery) {
      files = await _picker.pickMultiImage(
        imageQuality: 72,
        maxWidth: 1600,
        maxHeight: 1600,
        limit: remaining,
        requestFullMetadata: false,
      );
    } else {
      final file = await _picker.pickImage(
        source: ImageSource.camera,
        imageQuality: 72,
        maxWidth: 1600,
        maxHeight: 1600,
        requestFullMetadata: false,
      );
      files = file == null ? [] : [file];
    }
    if (files.isEmpty) return;
    if (files.length > remaining) {
      throw Exception(
        'You can add only $remaining more photo(s) to this item.',
      );
    }

    final uploaded = <String>[];
    for (final file in files) {
      final uploadId = const Uuid().v4();
      Map<String, dynamic>? response;
      for (var attempt = 0; attempt < 2; attempt++) {
        try {
          response = await widget.upload(
            '/maintainer/tasks/$_taskId/inspection/photo',
            {'upload_id': uploadId, 'item_id': itemId},
            file,
          );
          break;
        } catch (_) {
          if (attempt == 1) rethrow;
          await Future<void>.delayed(const Duration(milliseconds: 500));
        }
      }
      final url = response?['url']?.toString();
      if (url != null && url.isNotEmpty) uploaded.add(url);
    }
    if (mounted && uploaded.isNotEmpty) {
      setState(() {
        final photos = List<String>.from(
          (item['photos'] as List? ?? const []).map(
            (value) => value.toString(),
          ),
        );
        item['photos'] = [...photos, ...uploaded];
      });
    }
  });

  Future<void> _submitInspection() => _run(() async {
    for (final item in _items) {
      if (!_conditions.containsKey(item['id'].toString())) {
        throw Exception('Choose a condition for every checklist item.');
      }
    }
    final inventory = <String, dynamic>{};
    for (final row in _inventory) {
      final id = row['id'].toString();
      final found = int.tryParse(_found[id] ?? '');
      final damaged = int.tryParse(_damaged[id] ?? '');
      if (found == null || damaged == null || damaged > found) {
        throw Exception(
          'Complete the inventory counts. Damaged cannot exceed found.',
        );
      }
      inventory[id] = {
        'found': found,
        'damaged': damaged,
        'notes': _inventoryNotes[id] ?? '',
      };
    }
    final items = <String, dynamic>{};
    for (final item in _items) {
      final id = item['id'].toString();
      items[id] = {
        'condition': _conditions[id],
        'comment': _comments[id] ?? '',
      };
    }
    final response = await widget
        .request('POST', '/maintainer/tasks/$_taskId/inspection', {
          'draft_revision': _inspection['revision'],
          'items': items,
          'inventory': inventory,
          'notes': _notes.text,
        });
    if (mounted) {
      setState(() {
        final submitted = _map(response['inspection']);
        if (submitted.isNotEmpty) _inspection = submitted;
        _page = 'success';
      });
    }
  });

  Future<void> _openReview(Map<String, dynamic> inspection) => _run(() async {
    final response = await widget.request(
      'GET',
      '/field/api/inspections/${inspection['id']}',
    );
    if (mounted) {
      setState(() {
        _inspection = _map(response['inspection']);
        _page = 'ops_review';
      });
    }
  });

  Future<void> _openRequest() => _run(() async {
    final response = await widget.request('GET', '/field/api/options');
    if (mounted) {
      setState(() {
        _options = response;
        _page = 'ops_request';
      });
    }
  });

  Future<void> _post(String path, Map<String, dynamic> body, String next) =>
      _run(() async {
        await widget.request('POST', path, body);
        if (mounted) setState(() => _page = next);
        if (next == 'home' || next == 'tasks' || next == 'inspections') {
          final response = await widget.request(
            'GET',
            _admin ? '/field/api/inspections' : '/field/api/tasks',
          );
          if (mounted) {
            setState(() {
              if (_admin) {
                _inspections = _maps(response['inspections']);
              } else {
                _tasks = _maps(response['tasks']);
              }
            });
          }
        }
      });

  void _go(String page) => setState(() {
    _page = page;
    _error = null;
  });

  Future<void> _retry() async {
    if (_page == 'task' && _taskId.isNotEmpty) {
      await _openTask(_task);
    } else if (const {
          'rooms',
          'checklist',
          'inventory',
          'review',
        }.contains(_page) &&
        _taskId.isNotEmpty) {
      await _openInspection();
    } else if (_page == 'ops_review' && _inspectionId.isNotEmpty) {
      await _openReview(_inspection);
    } else {
      await _refresh();
    }
  }

  Future<void> _openInspectionPdf() => _run(() async {
    final value = _inspection['pdf_url'] ?? _task['pdf_url'];
    final uri = Uri.tryParse(value?.toString() ?? '');
    if (uri == null || !uri.hasScheme) {
      throw Exception('The PDF is not ready yet. Refresh after submission.');
    }
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      throw Exception('Could not open the inspection PDF.');
    }
  });

  @override
  Widget build(BuildContext context) {
    final title = _titles[_page] ?? 'HHMS Field';
    final rootPage = _rootPages.contains(_page);
    return Scaffold(
      backgroundColor: _paper,
      appBar: AppBar(
        backgroundColor: Colors.white,
        surfaceTintColor: Colors.white,
        foregroundColor: _navy,
        elevation: 0,
        toolbarHeight: 72,
        shape: const Border(
          bottom: BorderSide(color: Color(0xFFE5ECEE), width: 1),
        ),
        leadingWidth: 62,
        leading: rootPage
            ? Padding(
                padding: const EdgeInsets.fromLTRB(16, 14, 6, 14),
                child: Container(
                  padding: const EdgeInsets.all(7),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    border: Border.all(color: const Color(0xFFDCE8EA)),
                    borderRadius: BorderRadius.circular(13),
                  ),
                  child: Image.asset(
                    'assets/brand-mark.png',
                    fit: BoxFit.contain,
                    errorBuilder: (_, _, _) => const Icon(
                      Icons.apartment_rounded,
                      color: _navy,
                      size: 20,
                    ),
                  ),
                ),
              )
            : IconButton(
                icon: const Icon(Icons.arrow_back_ios_new_rounded),
                tooltip: 'Back',
                onPressed: _back,
              ),
        titleSpacing: rootPage ? 4 : 0,
        title: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              _headerTitle(title),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 17, fontWeight: FontWeight.w900),
            ),
            const SizedBox(height: 2),
            Text(
              _headerSubtitle(),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                color: _muted,
                fontSize: 11,
                fontWeight: FontWeight.w500,
              ),
            ),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            tooltip: 'Refresh',
            onPressed: _refresh,
          ),
          const SizedBox(width: 6),
        ],
      ),
      body: SafeArea(
        child: Column(
          children: [
            if (_busy)
              const LinearProgressIndicator(color: _teal, minHeight: 3),
            if (_error != null)
              Container(
                width: double.infinity,
                padding: const EdgeInsets.fromLTRB(16, 10, 8, 10),
                color: const Color(0xFFFFE8E8),
                child: Row(
                  children: [
                    const Icon(Icons.error_outline, color: Color(0xFF9E3030)),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Text(
                        _error!,
                        style: const TextStyle(color: Color(0xFF9E3030)),
                      ),
                    ),
                    TextButton(
                      onPressed: _busy ? null : _retry,
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              ),
            Expanded(
              child: ListView(
                padding: const EdgeInsets.fromLTRB(16, 18, 16, 110),
                children: _body(),
              ),
            ),
          ],
        ),
      ),
      bottomNavigationBar: _bottom(),
    );
  }

  static const _rootPages = {
    'home',
    'tasks',
    'inspections',
    'notifications',
    'profile',
  };

  String _headerTitle(String fallback) {
    if (_page != 'home') return fallback;
    final firstName = widget.user['name']?.toString().trim().split(' ').first;
    return 'Hello, ${firstName?.isNotEmpty == true ? firstName : 'Team'}';
  }

  String _headerSubtitle() {
    if (_page == 'home') {
      return _admin ? 'Operations control centre' : 'Your field workspace';
    }
    if (_page == 'task') {
      return _task['number']?.toString() ?? 'Assigned work';
    }
    if (const {
      'accept',
      'rooms',
      'checklist',
      'inventory',
      'review',
    }.contains(_page)) {
      return '${_task['building'] ?? 'Property'} · Unit ${_task['property'] ?? '—'}';
    }
    if (_page.startsWith('ops_')) return 'Inspection operations';
    return _admin ? 'Live operations' : 'Assigned to you';
  }

  void _back() {
    if (const {
      'accept',
      'timeline',
      'update',
      'cost',
      'expense',
    }.contains(_page)) {
      _go('task');
      return;
    }
    if (const {'checklist', 'inventory'}.contains(_page)) {
      _go('rooms');
      return;
    }
    if (_page == 'review') {
      _go(_inventory.isEmpty ? 'rooms' : 'inventory');
      return;
    }
    _go(_admin ? 'inspections' : 'tasks');
  }

  static const _titles = <String, String>{
    'home': 'Home',
    'tasks': 'My tasks',
    'inspections': 'My inspections',
    'task': 'Task details',
    'accept': 'Accept task',
    'rooms': 'Rooms to inspect',
    'checklist': 'Room checklist',
    'inventory': 'Inventory count',
    'review': 'Review & submit',
    'success': 'Submitted',
    'timeline': 'Task timeline',
    'update': 'Add update',
    'cost': 'Record cost',
    'expense': 'Expense request',
    'notifications': 'Notifications',
    'profile': 'Profile',
    'ops_request': 'Request inspection',
    'ops_review': 'Review inspection',
    'ops_inventory': 'Approve inventory',
  };

  List<Widget> _body() => switch (_page) {
    'home' => _home(),
    'tasks' => _taskList(false),
    'inspections' => _admin ? _opsList() : _taskList(true),
    'task' => _taskDetail(),
    'accept' => _accept(),
    'rooms' => _roomList(),
    'checklist' => _checklist(),
    'inventory' => _inventoryPage(),
    'review' => _reviewPage(),
    'success' => [
      _notice(
        Icons.check_circle_outline,
        'Inspection submitted. Operations can now review it.',
      ),
      const SizedBox(height: 16),
      if ((_inspection['pdf_url'] ?? '').toString().isNotEmpty)
        _button('Download inspection PDF', _openInspectionPdf),
      _button('Back to my tasks', () => _go('tasks'), secondary: true),
    ],
    'timeline' => _timeline(),
    'update' => _updatePage(),
    'cost' => _costPage(),
    'expense' => _expensePage(),
    'notifications' => _notifications(),
    'profile' => _profile(),
    'ops_request' => _opsRequest(),
    'ops_review' => _opsReview(),
    'ops_inventory' => _opsInventory(),
    _ => [_notice(Icons.info_outline, 'Loading…')],
  };

  List<Widget> _home() {
    final total = _admin ? _inspections.length : _tasks.length;
    final active = _admin
        ? _inspections.where((e) => e['status'] == 'submitted').length
        : _tasks
              .where((e) => !['completed', 'closed'].contains(e['status']))
              .length;
    return [
      Row(
        children: [
          _metric('$total', _admin ? 'Inspections' : 'Assigned tasks'),
          const SizedBox(width: 12),
          _metric('$active', _admin ? 'Awaiting review' : 'Active'),
        ],
      ),
      const SizedBox(height: 20),
      _heading(_admin ? 'Operations' : 'Your work'),
      _tile(
        _admin ? 'Inspection queue' : 'My inspections',
        _admin ? 'Reports and approvals' : 'Assigned checklists',
        Icons.fact_check_outlined,
        () => _go('inspections'),
      ),
      _tile(
        _admin ? 'Request inspection' : 'All tasks',
        _admin ? 'Assign a new checklist' : 'Maintenance and more',
        Icons.assignment_outlined,
        _admin ? _openRequest : () => _go('tasks'),
      ),
      _heading('Recent activity'),
      ...(_admin ? _inspections : _tasks)
          .take(3)
          .map(
            (item) => _tile(
              item['number']?.toString() ?? '',
              item['property']?.toString() ?? '',
              Icons.schedule_outlined,
              () => _admin ? _openReview(item) : _openTask(item),
            ),
          ),
    ];
  }

  List<Widget> _taskList(bool onlyInspections) {
    final rows = onlyInspections
        ? _tasks
              .where(
                (t) =>
                    ['inspection', 'checkout_inspection'].contains(t['type']),
              )
              .toList()
        : _tasks;
    if (rows.isEmpty) {
      return [
        _notice(
          Icons.inbox_outlined,
          'No assigned ${onlyInspections ? 'inspections' : 'tasks'} yet.',
        ),
      ];
    }
    return [
      ...rows.map(
        (task) => _tile(
          task['number']?.toString() ?? '',
          '${task['title'] ?? ''}\n${task['building'] ?? ''} · ${task['property'] ?? ''}\n${task['status_label'] ?? ''}',
          Icons.assignment_outlined,
          () => _openTask(task),
        ),
      ),
    ];
  }

  List<Widget> _opsList() => _inspections.isEmpty
      ? [_notice(Icons.inbox_outlined, 'No inspections yet.')]
      : _inspections
            .map(
              (inspection) => _tile(
                inspection['number']?.toString() ?? '',
                '${inspection['type'] ?? ''} · ${inspection['building'] ?? ''} ${inspection['property'] ?? ''}\n${inspection['status'] ?? ''}',
                Icons.fact_check_outlined,
                () => _openReview(inspection),
              ),
            )
            .toList();

  List<Widget> _taskDetail() {
    final closed = [
      'completed',
      'closed',
      'cancelled',
    ].contains(_task['status']);
    final pending = ['assigned', 'new', 'open'].contains(_task['status']);
    return [
      _taskHero(),
      const SizedBox(height: 14),
      _taskFacts(),
      if ((_task['description'] ?? '').toString().isNotEmpty) ...[
        _heading('Instructions'),
        _card(
          Text(
            _task['description'].toString(),
            style: const TextStyle(color: _navy, height: 1.45),
          ),
        ),
      ],
      _heading(closed ? 'Task record' : 'Next action'),
      if (pending)
        _actionCard(
          _isInspectionTask ? 'Accept & start inspection' : 'Accept task',
          _isInspectionTask
              ? 'Confirm assignment, then open the room checklist'
              : 'Confirm assignment and expected completion',
          _isInspectionTask ? Icons.fact_check_outlined : Icons.task_alt,
          () => _go('accept'),
          primary: true,
        ),
      if (_task['status'] == 'accepted' && !_isInspectionTask)
        _actionCard(
          'Start work',
          'Mark this job as in progress',
          Icons.play_arrow_rounded,
          () => _post('/maintainer/tasks/$_taskId/start', {}, 'tasks'),
          primary: true,
        ),
      if (_isInspectionTask && !closed && !pending)
        _actionCard(
          'Continue inspection',
          'Room checklist, multiple photos and inventory count',
          Icons.fact_check_outlined,
          _openInspection,
          primary: true,
        ),
      if (!closed && !pending)
        _actionCard(
          'Add progress update',
          'Send a short update to Operations',
          Icons.edit_note_outlined,
          () => _go('update'),
        ),
      if (!_isInspectionTask && !closed) ...[
        _heading('Costs & purchasing'),
        _actionCard(
          'Record task cost',
          'Labour, materials or other completed work',
          Icons.receipt_long_outlined,
          () => _go('cost'),
        ),
        _actionCard(
          'Request office payment',
          'Attach a supplier invoice for approval',
          Icons.account_balance_wallet_outlined,
          () => _go('expense'),
        ),
      ],
      _heading('History'),
      if (closed && (_task['pdf_url'] ?? '').toString().isNotEmpty)
        _actionCard(
          'Download inspection PDF',
          'Completed checklist, notes and photo thumbnails',
          Icons.picture_as_pdf_outlined,
          _openInspectionPdf,
          primary: true,
        ),
      _actionCard(
        'View timeline',
        '${_maps(_task['activities']).length} recorded updates',
        Icons.timeline_outlined,
        () => _go('timeline'),
      ),
    ];
  }

  List<Widget> _accept() => [
    _notice(
      _isInspectionTask
          ? Icons.fact_check_outlined
          : Icons.event_available_outlined,
      _isInspectionTask
          ? 'Accept this assignment and begin the guided inspection.'
          : 'Confirm when you expect to complete this task.',
    ),
    const SizedBox(height: 18),
    _info('Due date', _task['due_date']),
    if (!_isInspectionTask)
      _field('Expected completion date', _secondary, hint: 'YYYY-MM-DD'),
    _field(
      _isInspectionTask ? 'Note to Operations (optional)' : 'Initial remark',
      _text,
      hint: _isInspectionTask
          ? 'Example: Arriving at the unit now'
          : 'Optional note',
    ),
    _button(
      _isInspectionTask ? 'Accept & start inspection' : 'Accept task',
      () async {
        await _run(() async {
          await widget.request('POST', '/maintainer/tasks/$_taskId/accept', {
            'expected_completion_date':
                _task['due_date']?.toString() ?? _secondary.text,
            'initial_remark': _text.text,
          });
        });
        if (_error == null && mounted) {
          if (_isInspectionTask) {
            await _openInspection();
          } else {
            _go('tasks');
            await _refresh();
          }
        }
      },
    ),
  ];

  List<Widget> _roomList() => [
    _notice(
      Icons.info_outline,
      'Work through each room. Nothing is marked good automatically.',
    ),
    const SizedBox(height: 16),
    ..._rooms.asMap().entries.map(
      (entry) => _tile(
        entry.value,
        '${_items.where((e) => e['area'] == entry.value).length} checks',
        Icons.meeting_room_outlined,
        () {
          setState(() {
            _roomIndex = entry.key;
            _page = 'checklist';
          });
        },
      ),
    ),
    _tile(
      'Inventory count',
      '${_inventory.length} items to count',
      Icons.inventory_2_outlined,
      () => _go('inventory'),
    ),
  ];

  List<Widget> _checklist() {
    if (_rooms.isEmpty) {
      return [_notice(Icons.inbox_outlined, 'No checklist items.')];
    }
    final room = _rooms[_roomIndex.clamp(0, _rooms.length - 1)];
    return [
      _heading(room),
      ..._items.where((e) => e['area'] == room).map((item) {
        final id = item['id'].toString();
        return _card(
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                item['name']?.toString() ?? '',
                style: const TextStyle(
                  color: _navy,
                  fontWeight: FontWeight.w800,
                ),
              ),
              const SizedBox(height: 12),
              SegmentedButton<String>(
                segments: const [
                  ButtonSegment(value: 'good', label: Text('Good')),
                  ButtonSegment(value: 'issue', label: Text('Issue')),
                  ButtonSegment(value: 'na', label: Text('N/A')),
                ],
                selected: _conditions[id] == null ? {} : {_conditions[id]!},
                emptySelectionAllowed: true,
                onSelectionChanged: (values) =>
                    setState(() => _conditions[id] = values.first),
              ),
              const SizedBox(height: 9),
              TextFormField(
                initialValue: _comments[id] ?? '',
                maxLines: 2,
                decoration: const InputDecoration(
                  labelText: 'Remark',
                  border: OutlineInputBorder(),
                ),
                onChanged: (value) => _comments[id] = value,
              ),
              const SizedBox(height: 9),
              TextButton.icon(
                onPressed: () => _addPhoto(id),
                icon: const Icon(Icons.add_a_photo_outlined),
                label: Text(
                  'Add photo (${(item['photos'] as List?)?.length ?? 0})',
                ),
              ),
              _photoStrip(item['photos']),
            ],
          ),
        );
      }),
      _button('Save & continue', () async {
        await _saveDraft();
        if (_error == null && mounted) {
          setState(() {
            if (_roomIndex < _rooms.length - 1) {
              _roomIndex++;
            } else {
              _page = 'inventory';
            }
          });
        }
      }),
    ];
  }

  List<Widget> _inventoryPage() => [
    _notice(
      Icons.inventory_2_outlined,
      'Damaged items are included in Found. Office approval updates stock.',
    ),
    const SizedBox(height: 15),
    ..._inventory.map((row) {
      final id = row['id'].toString();
      return _card(
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${row['room']} · ${row['name']}',
              style: const TextStyle(color: _navy, fontWeight: FontWeight.w800),
            ),
            Text(
              'Required ${row['required']} · Previous ${row['before']}',
              style: const TextStyle(color: _muted),
            ),
            const SizedBox(height: 9),
            Row(
              children: [
                Expanded(
                  child: TextFormField(
                    initialValue: _found[id],
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Found'),
                    onChanged: (v) => _found[id] = v,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: TextFormField(
                    initialValue: _damaged[id],
                    keyboardType: TextInputType.number,
                    decoration: const InputDecoration(labelText: 'Damaged'),
                    onChanged: (v) => _damaged[id] = v,
                  ),
                ),
              ],
            ),
            TextFormField(
              initialValue: _inventoryNotes[id],
              decoration: const InputDecoration(labelText: 'Evidence / notes'),
              onChanged: (v) => _inventoryNotes[id] = v,
            ),
          ],
        ),
      );
    }),
    _button('Save & review', () async {
      await _saveDraft();
      if (_error == null && mounted) _go('review');
    }),
  ];

  List<Widget> _reviewPage() => [
    _notice(
      Icons.fact_check_outlined,
      '${_items.length} checks · ${_conditions.values.where((e) => e == 'issue').length} issues',
    ),
    const SizedBox(height: 14),
    ..._rooms.map(
      (room) => _info(
        room,
        '${_items.where((e) => e['area'] == room && _conditions[e['id']] == 'good').length} good · '
        '${_items.where((e) => e['area'] == room && _conditions[e['id']] == 'issue').length} issues',
      ),
    ),
    ..._inventory.map(
      (row) => _info(
        '${row['room']} · ${row['name']}',
        '${_found[row['id']] ?? '-'} found · ${_damaged[row['id']] ?? '-'} damaged',
      ),
    ),
    _field('Final notes', _notes, hint: 'Overall inspection notes', lines: 3),
    _button('Submit inspection', _submitInspection),
  ];

  List<Widget> _timeline() => [
    ..._maps(_task['activities']).map(
      (a) => _tile(
        a['action']?.toString() ?? 'Update',
        '${a['at'] ?? ''}\n${a['comment'] ?? ''}',
        Icons.timeline_outlined,
        () {},
      ),
    ),
  ];

  List<Widget> _updatePage() => [
    _field(
      'Progress update',
      _text,
      hint: 'What has been completed?',
      lines: 3,
    ),
    _button(
      'Send update',
      () => _post('/maintainer/tasks/$_taskId/remarks', {
        'remark': _text.text,
      }, 'task'),
    ),
  ];

  List<Widget> _costPage() => [
    DropdownButtonFormField<String>(
      initialValue: _costType,
      decoration: const InputDecoration(labelText: 'Cost type'),
      items: const [
        DropdownMenuItem(value: 'other', child: Text('Other')),
        DropdownMenuItem(value: 'material', child: Text('Material')),
        DropdownMenuItem(value: 'labor', child: Text('Labour')),
      ],
      onChanged: (v) => setState(() => _costType = v ?? 'other'),
    ),
    _field('Item or description', _text, hint: 'What was purchased or done?'),
    _field(
      _costType == 'labor'
          ? 'Hours'
          : _costType == 'material'
          ? 'Quantity'
          : 'Amount (AED)',
      _amount,
      hint: '0.00',
      numeric: true,
    ),
    if (_costType != 'other')
      _field(
        _costType == 'labor' ? 'Rate (AED)' : 'Unit price (AED)',
        _secondary,
        hint: '0.00',
        numeric: true,
      ),
    _button(
      'Save cost',
      () => _post('/maintainer/tasks/$_taskId/costs', {
        'type': _costType,
        'label': _text.text,
        if (_costType == 'other') 'amount': _amount.text,
        if (_costType == 'material') ...{
          'quantity': _amount.text,
          'unit_price': _secondary.text,
        },
        if (_costType == 'labor') ...{
          'hours': _amount.text,
          'rate': _secondary.text,
          'worker': widget.user['name'],
        },
      }, 'task'),
    ),
  ];

  List<Widget> _expensePage() => [
    DropdownButtonFormField<String>(
      initialValue: _paymentStatus,
      decoration: const InputDecoration(labelText: 'Payment status'),
      items: const [
        DropdownMenuItem(
          value: 'unpaid',
          child: Text('Unpaid · office to pay'),
        ),
        DropdownMenuItem(value: 'paid_by_staff', child: Text('Paid by staff')),
      ],
      onChanged: (v) => setState(() => _paymentStatus = v ?? 'unpaid'),
    ),
    _field('Supplier', _text, hint: 'Supplier name'),
    _field('Total (AED)', _amount, hint: '0.00', numeric: true),
    _field('Purpose', _secondary, hint: 'At least 5 characters'),
    _tile(
      'Supplier invoice',
      _expenseInvoice?.name ?? 'Add PDF or photo',
      Icons.attach_file_outlined,
      () => _pickExpenseFile(false),
    ),
    if (_paymentStatus == 'paid_by_staff')
      _tile(
        'Payment receipt',
        _expenseReceipt?.name ?? 'Add PDF or photo',
        Icons.receipt_long_outlined,
        () => _pickExpenseFile(true),
      ),
    _button('Send request to office', () async {
      if (_expenseInvoice == null) {
        setState(() => _error = 'Add the supplier invoice first.');
        return;
      }
      if (_paymentStatus == 'paid_by_staff' && _expenseReceipt == null) {
        setState(() => _error = 'Add the payment receipt first.');
        return;
      }
      await _run(() async {
        await widget.upload(
          '/maintainer/tasks/$_taskId/expense-request',
          {
            '__file_field': 'invoice',
            'submission_id': const Uuid().v4(),
            'expense_date': DateTime.now().toIso8601String().substring(0, 10),
            'supplier': _text.text,
            'amount': _amount.text,
            'payment_status': _paymentStatus,
            'description': _secondary.text,
          },
          _expenseInvoice!,
          _expenseReceipt == null ? null : {'receipt': _expenseReceipt!},
        );
        if (mounted) setState(() => _page = 'task');
      });
    }),
  ];

  List<Widget> _notifications() => _tasks
      .where((t) => ['assigned', 'new', 'open'].contains(t['status']))
      .map(
        (t) => _tile(
          t['title']?.toString() ?? '',
          '${t['property'] ?? ''} · ${t['due_date'] ?? ''}',
          Icons.notifications_outlined,
          () => _openTask(t),
        ),
      )
      .toList();

  List<Widget> _profile() => [
    _notice(Icons.person_outline, widget.user['name']?.toString() ?? ''),
    const SizedBox(height: 14),
    _info('Email', widget.user['email']),
    _info('Phone', widget.user['phone']),
    _info('Role', widget.user['role']),
    const SizedBox(height: 20),
    _button('Sign out', widget.signOut),
  ];

  List<Widget> _opsRequest() {
    final properties = _maps(_options['properties']);
    final maintainers = _maps(_options['maintainers']);
    final bookings = _maps(
      _options['bookings'],
    ).where((b) => b['property_id']?.toString() == _selectedProperty).toList();
    return [
      DropdownButtonFormField<String>(
        initialValue: _selectedProperty,
        decoration: const InputDecoration(labelText: 'Apartment'),
        items: properties
            .map(
              (p) => DropdownMenuItem(
                value: p['id'].toString(),
                child: Text('${p['building'] ?? ''} · ${p['name']}'),
              ),
            )
            .toList(),
        onChanged: (v) => setState(() {
          _selectedProperty = v;
          _selectedBooking = null;
        }),
      ),
      DropdownButtonFormField<String>(
        initialValue: _selectedType,
        decoration: const InputDecoration(labelText: 'Inspection type'),
        items: const [
          DropdownMenuItem(value: 'routine', child: Text('Routine')),
          DropdownMenuItem(value: 'check_in', child: Text('Check in')),
          DropdownMenuItem(value: 'check_out', child: Text('Check out')),
          DropdownMenuItem(value: 'maintenance', child: Text('Maintenance')),
          DropdownMenuItem(value: 'cleaning', child: Text('Cleaning')),
        ],
        onChanged: (v) => setState(() => _selectedType = v ?? 'routine'),
      ),
      if (['check_in', 'check_out'].contains(_selectedType))
        DropdownButtonFormField<String>(
          initialValue: _selectedBooking,
          decoration: const InputDecoration(labelText: 'Related booking'),
          items: bookings
              .map(
                (b) => DropdownMenuItem(
                  value: b['id'].toString(),
                  child: Text(
                    '${b['booking_reference'] ?? ''} · ${b['guest_name'] ?? ''}',
                  ),
                ),
              )
              .toList(),
          onChanged: (v) => setState(() => _selectedBooking = v),
        ),
      DropdownButtonFormField<String>(
        initialValue: _selectedMaintainer,
        decoration: const InputDecoration(labelText: 'Assign to'),
        items: maintainers
            .map(
              (m) => DropdownMenuItem(
                value: m['id'].toString(),
                child: Text(m['name']?.toString() ?? ''),
              ),
            )
            .toList(),
        onChanged: (v) => setState(() => _selectedMaintainer = v),
      ),
      _field('Due date', _secondary, hint: 'YYYY-MM-DD'),
      _field(
        'Instructions',
        _text,
        hint: 'What should be inspected?',
        lines: 3,
      ),
      _button(
        'Send inspection request',
        () => _post('/admin/inspection-requests', {
          'property_id': _selectedProperty,
          'booking_id': _selectedBooking,
          'inspection_type': _selectedType,
          'assigned_to': _selectedMaintainer,
          'due_date': _secondary.text,
          'description': _text.text,
        }, 'inspections'),
      ),
    ];
  }

  List<Widget> _opsReview() => [
    _notice(
      Icons.fact_check_outlined,
      '${_inspection['number']} · ${_inspection['type']}',
    ),
    const SizedBox(height: 14),
    _info(
      'Property',
      '${_inspection['building'] ?? ''} · ${_inspection['property'] ?? ''}',
    ),
    _info('Submitted by', _inspection['submitted_by']),
    _info('Status', _inspection['status']),
    _info(
      'Checks',
      '${_inspection['total_items'] ?? 0} total · ${_inspection['issue_items'] ?? 0} issues',
    ),
    if ((_inspection['notes'] ?? '').toString().isNotEmpty)
      _info('Notes', _inspection['notes']),
    if ((_inspection['pdf_url'] ?? '').toString().isNotEmpty) ...[
      const SizedBox(height: 12),
      _button('Download PDF report', _openInspectionPdf),
    ],
    const SizedBox(height: 14),
    _heading('Checklist'),
    ..._maps(_inspection['items']).map(
      (item) => _card(
        Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              '${item['area']} · ${item['name']}',
              style: const TextStyle(color: _navy, fontWeight: FontWeight.w800),
            ),
            Text(
              '${item['condition'] ?? ''}${(item['comment'] ?? '').toString().isEmpty ? '' : ' · ${item['comment']}'}',
              style: const TextStyle(color: _muted),
            ),
            _photoStrip(item['photos']),
          ],
        ),
      ),
    ),
    _tile(
      'Review inventory counts',
      '${_maps(_inspection['inventory']).length} stock items',
      Icons.inventory_2_outlined,
      () => _go('ops_inventory'),
    ),
  ];

  List<Widget> _opsInventory() => [
    _notice(
      Icons.inventory_2_outlined,
      'Approval updates the apartment stock count.',
    ),
    const SizedBox(height: 14),
    ..._inventory.map(
      (row) => _info(
        '${row['room']} · ${row['name']}',
        'Required ${row['required']} · Found ${row['found']} · Damaged ${row['damaged']}',
      ),
    ),
    _field('Approval note', _text, hint: 'Explain your review', lines: 3),
    if (_inspection['inventory_status'] == 'submitted')
      _button(
        'Approve inventory',
        () => _post('/admin/inspections/$_inspectionId/inventory-approval', {
          'notes': _text.text,
        }, 'inspections'),
      )
    else
      _notice(
        Icons.info_outline,
        'Inventory status: ${_inspection['inventory_status'] ?? 'not submitted'}',
      ),
  ];

  Widget _bottom() => BottomNavigationBar(
    currentIndex: switch (_page) {
      'home' => 0,
      'tasks' || 'inspections' => 1,
      'notifications' || 'ops_request' => 2,
      'profile' => 3,
      _ => 0,
    },
    onTap: (index) {
      if (index == 0) _go('home');
      if (index == 1) _go(_admin ? 'inspections' : 'tasks');
      if (index == 2) {
        if (_admin) {
          _openRequest();
        } else {
          _go('notifications');
        }
      }
      if (index == 3) _go('profile');
    },
    type: BottomNavigationBarType.fixed,
    selectedItemColor: _teal,
    unselectedItemColor: _muted,
    items: [
      const BottomNavigationBarItem(
        icon: Icon(Icons.home_outlined),
        label: 'Home',
      ),
      BottomNavigationBarItem(
        icon: Icon(
          _admin ? Icons.fact_check_outlined : Icons.assignment_outlined,
        ),
        label: _admin ? 'Queue' : 'Tasks',
      ),
      BottomNavigationBarItem(
        icon: Icon(
          _admin ? Icons.add_circle_outline : Icons.notifications_outlined,
        ),
        label: _admin ? 'Request' : 'Alerts',
      ),
      const BottomNavigationBarItem(
        icon: Icon(Icons.person_outline),
        label: 'Profile',
      ),
    ],
  );

  Widget _photoStrip(dynamic photos) {
    if (photos is! List || photos.isEmpty) return const SizedBox.shrink();
    return SizedBox(
      height: 78,
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        itemCount: photos.length,
        separatorBuilder: (_, _) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final url = Uri.parse(
            _origin,
          ).resolve(photos[index].toString()).toString();
          return InkWell(
            onTap: () => showDialog<void>(
              context: context,
              builder: (context) => Dialog(
                child: InteractiveViewer(
                  child: Image.network(
                    url,
                    errorBuilder: (_, _, _) =>
                        const Center(child: Text('Photo unavailable')),
                  ),
                ),
              ),
            ),
            child: ClipRRect(
              borderRadius: BorderRadius.circular(10),
              child: Image.network(
                url,
                width: 78,
                height: 78,
                fit: BoxFit.cover,
                errorBuilder: (_, _, _) =>
                    const Icon(Icons.broken_image_outlined, size: 40),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _heading(String title) => Padding(
    padding: const EdgeInsets.only(bottom: 11, top: 6),
    child: Text(
      title,
      style: const TextStyle(
        color: _navy,
        fontSize: 17,
        fontWeight: FontWeight.w900,
      ),
    ),
  );

  Widget _taskHero() {
    final status = _task['status_label']?.toString() ?? 'Assigned';
    final isUrgent = _task['priority'] == 'urgent' || status == 'Overdue';
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(19),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [_navy, Color(0xFF20526A)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(22),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: .14),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  _task['number']?.toString() ?? '',
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 12,
                    fontWeight: FontWeight.w800,
                  ),
                ),
              ),
              const Spacer(),
              Container(
                padding: const EdgeInsets.symmetric(
                  horizontal: 10,
                  vertical: 6,
                ),
                decoration: BoxDecoration(
                  color: isUrgent
                      ? const Color(0xFFFFE4DF)
                      : const Color(0xFFDDF5EF),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(
                  status,
                  style: TextStyle(
                    color: isUrgent
                        ? const Color(0xFFB33A2B)
                        : const Color(0xFF087E70),
                    fontSize: 12,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Text(
            _task['title']?.toString() ?? '',
            style: const TextStyle(
              color: Colors.white,
              fontSize: 22,
              fontWeight: FontWeight.w900,
            ),
          ),
          const SizedBox(height: 7),
          Row(
            children: [
              const Icon(
                Icons.location_on_outlined,
                color: Color(0xFFB8D6DF),
                size: 17,
              ),
              const SizedBox(width: 5),
              Expanded(
                child: Text(
                  '${_task['building'] ?? 'Building'} · Unit ${_task['property'] ?? '—'}',
                  style: const TextStyle(
                    color: Color(0xFFD8E7EB),
                    fontSize: 13,
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _taskFacts() => _card(
    Row(
      children: [
        Expanded(
          child: _fact(Icons.flag_outlined, 'Priority', _task['priority']),
        ),
        Container(width: 1, height: 42, color: const Color(0xFFE7EEEF)),
        Expanded(child: _fact(Icons.event_outlined, 'Due', _task['due_date'])),
        Container(width: 1, height: 42, color: const Color(0xFFE7EEEF)),
        Expanded(
          child: _fact(
            Icons.person_outline,
            'Assigned by',
            _task['created_by'],
          ),
        ),
      ],
    ),
  );

  Widget _fact(IconData icon, String label, dynamic value) => Padding(
    padding: const EdgeInsets.symmetric(horizontal: 7),
    child: Column(
      children: [
        Icon(icon, color: _teal, size: 20),
        const SizedBox(height: 5),
        Text(label, style: const TextStyle(color: _muted, fontSize: 10)),
        const SizedBox(height: 2),
        Text(
          value?.toString() ?? '—',
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
          textAlign: TextAlign.center,
          style: const TextStyle(
            color: _navy,
            fontSize: 11,
            fontWeight: FontWeight.w800,
          ),
        ),
      ],
    ),
  );

  Widget _actionCard(
    String title,
    String subtitle,
    IconData icon,
    VoidCallback onTap, {
    bool primary = false,
  }) => Container(
    width: double.infinity,
    margin: const EdgeInsets.only(bottom: 10),
    decoration: BoxDecoration(
      color: primary ? _teal : Colors.white,
      borderRadius: BorderRadius.circular(16),
      border: primary ? null : Border.all(color: const Color(0xFFE4EBED)),
    ),
    child: InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: onTap,
      child: Padding(
        padding: const EdgeInsets.all(15),
        child: Row(
          children: [
            Icon(icon, color: primary ? Colors.white : _teal),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      color: primary ? Colors.white : _navy,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                  Text(
                    subtitle,
                    style: TextStyle(
                      color: primary ? Colors.white70 : _muted,
                      fontSize: 12,
                    ),
                  ),
                ],
              ),
            ),
            Icon(
              Icons.chevron_right_rounded,
              color: primary ? Colors.white : _muted,
            ),
          ],
        ),
      ),
    ),
  );

  Widget _card(Widget child) => Container(
    width: double.infinity,
    margin: const EdgeInsets.only(bottom: 12),
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

  Widget _tile(
    String title,
    String subtitle,
    IconData icon,
    VoidCallback onTap,
  ) => _card(
    InkWell(
      onTap: onTap,
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: _mint,
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: _teal, size: 21),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: const TextStyle(
                    color: _navy,
                    fontWeight: FontWeight.w800,
                  ),
                ),
                Text(
                  subtitle,
                  style: const TextStyle(color: _muted, fontSize: 12),
                ),
              ],
            ),
          ),
          const Icon(Icons.chevron_right_rounded, color: _muted),
        ],
      ),
    ),
  );

  Widget _info(String label, dynamic value) => _card(
    Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(color: _muted, fontSize: 12)),
        const SizedBox(height: 4),
        Text(
          value?.toString() ?? '—',
          style: const TextStyle(color: _navy, fontWeight: FontWeight.w800),
        ),
      ],
    ),
  );

  Widget _metric(String value, String label) => Expanded(
    child: _card(
      Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            value,
            style: const TextStyle(
              color: _teal,
              fontSize: 27,
              fontWeight: FontWeight.w900,
            ),
          ),
          Text(label, style: const TextStyle(color: _muted, fontSize: 11)),
        ],
      ),
    ),
  );

  Widget _notice(IconData icon, String text) => Container(
    width: double.infinity,
    padding: const EdgeInsets.all(15),
    decoration: BoxDecoration(
      color: _mint,
      borderRadius: BorderRadius.circular(15),
    ),
    child: Row(
      children: [
        Icon(icon, color: _teal),
        const SizedBox(width: 11),
        Expanded(
          child: Text(
            text,
            style: const TextStyle(
              color: _navy,
              fontWeight: FontWeight.w700,
              fontSize: 13,
            ),
          ),
        ),
      ],
    ),
  );

  Widget _field(
    String label,
    TextEditingController controller, {
    String? hint,
    int lines = 1,
    bool numeric = false,
  }) => Padding(
    padding: const EdgeInsets.only(bottom: 14),
    child: TextField(
      controller: controller,
      maxLines: lines,
      keyboardType: numeric ? TextInputType.number : TextInputType.text,
      decoration: InputDecoration(
        labelText: label,
        hintText: hint,
        filled: true,
        fillColor: Colors.white,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
      ),
    ),
  );

  Widget _button(
    String label,
    VoidCallback onTap, {
    bool secondary = false,
  }) => Padding(
    padding: const EdgeInsets.only(top: 8, bottom: 8),
    child: SizedBox(
      width: double.infinity,
      height: 51,
      child: ElevatedButton(
        onPressed: _busy ? null : onTap,
        style: ElevatedButton.styleFrom(
          backgroundColor: secondary ? Colors.white : _teal,
          foregroundColor: secondary ? _navy : Colors.white,
          side: secondary ? const BorderSide(color: Color(0xFFD8E3E6)) : null,
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
          ),
        ),
        child: Text(label, style: const TextStyle(fontWeight: FontWeight.w900)),
      ),
    ),
  );
}
