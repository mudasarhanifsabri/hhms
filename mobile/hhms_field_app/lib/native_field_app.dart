import 'dart:async';

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

const _navy = Color(0xFF0A4167);
const _teal = Color(0xFF00A58E);
const _muted = Color(0xFF718391);
const _paper = Color(0xFFF6F9FA);
const _mint = Color(0xFFE1F8F2);
const _origin = 'https://rms.pattern.ae';

class NativeFieldApp extends StatefulWidget {
  const NativeFieldApp({
    super.key,
    required this.user,
    required this.request,
    required this.upload,
    this.onNewTask,
    required this.signOut,
  });
  final Map<String, dynamic> user;
  final FieldRequest request;
  final FieldUpload upload;
  final Future<void> Function(Map<String, dynamic> task)? onNewTask;
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
  Timer? _taskPoller;
  final Set<String> _knownTaskIds = {};
  bool _taskPollInProgress = false;

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
    unawaited(_refresh());
    if (!_admin) {
      _taskPoller = Timer.periodic(
        const Duration(seconds: 8),
        (_) => unawaited(_pollForNewTasks()),
      );
    }
  }

  @override
  void dispose() {
    _taskPoller?.cancel();
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

  String _expectedCompletionDate() {
    if (!_isInspectionTask && _secondary.text.trim().isNotEmpty) {
      return _secondary.text.trim();
    }

    final now = DateTime.now();
    final today = DateTime(now.year, now.month, now.day);
    final dueDate = DateTime.tryParse(_task['due_date']?.toString() ?? '');
    final selected = dueDate != null && !dueDate.isBefore(today)
        ? dueDate
        : today;

    return '${selected.year.toString().padLeft(4, '0')}-'
        '${selected.month.toString().padLeft(2, '0')}-'
        '${selected.day.toString().padLeft(2, '0')}';
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
        _knownTaskIds.addAll(
          _tasks
              .map((task) => task['id']?.toString() ?? '')
              .where((id) => id.isNotEmpty),
        );
      }
    });
  });

  Future<void> _pollForNewTasks() async {
    if (_taskPollInProgress || !mounted || _admin) return;
    _taskPollInProgress = true;

    try {
      final response = await widget.request('GET', '/field/api/tasks');
      final latestTasks = _maps(response['tasks']);
      final newTasks = latestTasks.where((task) {
        final id = task['id']?.toString() ?? '';
        final status = task['status']?.toString() ?? '';
        return id.isNotEmpty &&
            !_knownTaskIds.contains(id) &&
            const ['new', 'open', 'assigned'].contains(status);
      }).toList();

      _knownTaskIds.addAll(
        latestTasks
            .map((task) => task['id']?.toString() ?? '')
            .where((id) => id.isNotEmpty),
      );

      if (mounted) setState(() => _tasks = latestTasks);
      for (final task in newTasks) {
        await widget.onNewTask?.call(task);
      }
    } catch (_) {
      // A temporary network failure is retried on the next poll.
    } finally {
      _taskPollInProgress = false;
    }
  }

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
        child: Padding(
          padding: const EdgeInsets.fromLTRB(18, 4, 18, 22),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Add photo evidence',
                style: TextStyle(
                  color: _navy,
                  fontSize: 19,
                  fontWeight: FontWeight.w900,
                ),
              ),
              const SizedBox(height: 5),
              const Text(
                'Choose camera or select multiple images from your gallery.',
                style: TextStyle(color: _muted, fontSize: 12),
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  Expanded(
                    child: _photoSourceCard(
                      context,
                      ImageSource.camera,
                      Icons.photo_camera_rounded,
                      'Camera',
                      'Take a new photo',
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: _photoSourceCard(
                      context,
                      ImageSource.gallery,
                      Icons.collections_rounded,
                      'Gallery',
                      'Select up to 20',
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
    if (source == null) return;
    final item = _items.firstWhere((row) => row['id'].toString() == itemId);
    final remaining = 20 - ((item['photos'] as List?)?.length ?? 0);
    if (remaining <= 0) throw Exception('Maximum 20 photos per item.');

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
    final uploadedPaths = <String>[];
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
      final path = response?['path']?.toString();
      if (path != null && path.isNotEmpty) uploadedPaths.add(path);
    }
    if (mounted && uploaded.isNotEmpty) {
      setState(() {
        final photos = List<String>.from(
          (item['photos'] as List? ?? const []).map(
            (value) => value.toString(),
          ),
        );
        item['photos'] = [...photos, ...uploaded];
        final paths = List<String>.from(
          (item['photo_paths'] as List? ?? const []).map(
            (value) => value.toString(),
          ),
        );
        item['photo_paths'] = [...paths, ...uploadedPaths];
      });
    }
  });

  Widget _photoSourceCard(
    BuildContext sheetContext,
    ImageSource source,
    IconData icon,
    String title,
    String subtitle,
  ) => Material(
    color: Colors.white,
    borderRadius: BorderRadius.circular(16),
    child: InkWell(
      borderRadius: BorderRadius.circular(16),
      onTap: () => Navigator.pop(sheetContext, source),
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 18),
        child: Column(
          children: [
            Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                color: _mint,
                borderRadius: BorderRadius.circular(13),
              ),
              child: Icon(icon, color: _teal),
            ),
            const SizedBox(height: 10),
            Text(
              title,
              style: const TextStyle(color: _navy, fontWeight: FontWeight.w900),
            ),
            Text(subtitle, style: const TextStyle(color: _muted, fontSize: 10)),
          ],
        ),
      ),
    ),
  );

  Future<void> _deletePhoto(Map<String, dynamic> item, int index) =>
      _run(() async {
        final paths = List<String>.from(
          (item['photo_paths'] as List? ?? const []).map(
            (value) => value.toString(),
          ),
        );
        if (index >= paths.length) {
          throw Exception('Refresh the inspection before deleting this photo.');
        }
        await widget.request(
          'DELETE',
          '/maintainer/tasks/$_taskId/inspection/photo',
          {'item_id': item['id'].toString(), 'path': paths[index]},
        );
        if (mounted) {
          setState(() {
            final photos = List<dynamic>.from(
              item['photos'] as List? ?? const [],
            );
            if (index < photos.length) photos.removeAt(index);
            paths.removeAt(index);
            item['photos'] = photos;
            item['photo_paths'] = paths;
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

  Future<void> _openSelfInspection() => _run(() async {
    final response = await widget.request(
      'GET',
      '/field/api/inspection-options',
    );
    if (mounted) {
      setState(() {
        _options = response;
        _selectedProperty = null;
        _selectedType = 'routine';
        _secondary.text = DateTime.now().toIso8601String().substring(0, 10);
        _text.clear();
        _page = 'self_inspection';
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

  Future<void> _callTenant() async {
    final phone = (_task['guest_phone'] ?? '').toString().trim();
    if (phone.isEmpty) return;
    final uri = Uri(
      scheme: 'tel',
      path: phone.replaceAll(RegExp(r'[^0-9+]'), ''),
    );
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      throw Exception('Could not open the phone dialler.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final title = _titles[_page] ?? 'HHMS Field';
    final rootPage = _rootPages.contains(_page);
    final initials = (widget.user['name']?.toString() ?? 'Team')
        .trim()
        .split(RegExp(r'\s+'))
        .where((part) => part.isNotEmpty)
        .take(2)
        .map((part) => part[0].toUpperCase())
        .join();
    return Scaffold(
      backgroundColor: _paper,
      appBar: AppBar(
        backgroundColor: _paper,
        surfaceTintColor: _paper,
        foregroundColor: _navy,
        elevation: 0,
        toolbarHeight: 78,
        leadingWidth: 70,
        leading: rootPage
            ? Padding(
                padding: const EdgeInsets.fromLTRB(16, 13, 6, 13),
                child: Container(
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: _navy,
                    borderRadius: BorderRadius.circular(15),
                  ),
                  child: Text(
                    initials.isEmpty ? 'HH' : initials,
                    style: const TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w900,
                    ),
                  ),
                ),
              )
            : IconButton(
                icon: const Icon(Icons.arrow_back_rounded),
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
          if (rootPage)
            Stack(
              alignment: Alignment.center,
              children: [
                IconButton(
                  icon: const Icon(Icons.notifications_none_rounded),
                  tooltip: 'Notifications',
                  onPressed: () => _go('notifications'),
                ),
                if (!_admin &&
                    _tasks
                        .where((task) => task['status'] == 'assigned')
                        .isNotEmpty)
                  Positioned(
                    right: 8,
                    top: 16,
                    child: Container(
                      width: 8,
                      height: 8,
                      decoration: const BoxDecoration(
                        color: Color(0xFFEF746C),
                        shape: BoxShape.circle,
                      ),
                    ),
                  ),
              ],
            )
          else
            PopupMenuButton<String>(
              tooltip: 'Page actions',
              icon: const Icon(Icons.more_vert_rounded),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(16),
              ),
              onSelected: (value) {
                if (value == 'refresh') _refresh();
                if (value == 'home') _go('home');
              },
              itemBuilder: (_) => const [
                PopupMenuItem(
                  value: 'refresh',
                  child: ListTile(
                    leading: Icon(Icons.refresh_rounded),
                    title: Text('Refresh'),
                  ),
                ),
                PopupMenuItem(
                  value: 'home',
                  child: ListTile(
                    leading: Icon(Icons.home_outlined),
                    title: Text('Go to home'),
                  ),
                ),
              ],
            ),
          const SizedBox(width: 8),
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
                padding: const EdgeInsets.fromLTRB(16, 12, 16, 104),
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
    'self_inspection': 'Create inspection',
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
    'self_inspection' => _selfInspection(),
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
      _homeSummary(total, active),
      const SizedBox(height: 14),
      if (!_admin)
        _actionCard(
          'Create inspection',
          'Choose any unit and start immediately',
          Icons.add_task_rounded,
          _openSelfInspection,
          primary: true,
        ),
      _heading(_admin ? 'Operations' : 'My workspace'),
      _workspaceAction(
        _admin ? 'Inspection queue' : 'My inspections',
        _admin ? 'Reports and approvals' : 'Assigned checklists',
        Icons.fact_check_rounded,
        () => _go('inspections'),
      ),
      _workspaceAction(
        _admin ? 'Request inspection' : 'All tasks',
        _admin ? 'Assign a new checklist' : 'Maintenance and more',
        Icons.assignment_rounded,
        _admin ? _openRequest : () => _go('tasks'),
      ),
      _heading('Recent activity'),
      ...(_admin ? _inspections : _tasks)
          .take(3)
          .map((item) => _compactActivity(item)),
    ];
  }

  Widget _homeSummary(int total, int active) {
    final completed = (total - active).clamp(0, total);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 16),
      decoration: BoxDecoration(
        color: _navy,
        borderRadius: BorderRadius.circular(18),
      ),
      child: Row(
        children: [
          _summaryStat('$total', _admin ? 'Reports' : 'Today'),
          _summaryDivider(),
          _summaryStat('$completed', 'Done'),
          _summaryDivider(),
          _summaryStat('$active', _admin ? 'Review' : 'Active'),
        ],
      ),
    );
  }

  Widget _workspaceAction(
    String title,
    String subtitle,
    IconData icon,
    VoidCallback onTap,
  ) => Padding(
    padding: const EdgeInsets.only(bottom: 9),
    child: Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(16),
      clipBehavior: Clip.antiAlias,
      child: InkWell(
        onTap: onTap,
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          child: Row(
            children: [
              Container(
                width: 44,
                height: 44,
                decoration: BoxDecoration(
                  color: _mint,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(icon, color: _teal),
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
                        fontWeight: FontWeight.w900,
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
      ),
    ),
  );

  Widget _summaryStat(String value, String label) => Expanded(
    child: Column(
      children: [
        Text(
          value,
          style: const TextStyle(
            color: Colors.white,
            fontSize: 24,
            fontWeight: FontWeight.w900,
          ),
        ),
        Text(
          label,
          style: const TextStyle(color: Color(0xFFB8D2DF), fontSize: 11),
        ),
      ],
    ),
  );

  Widget _summaryDivider() => Container(
    width: 1,
    height: 34,
    color: Colors.white.withValues(alpha: .18),
  );

  Widget _compactActivity(Map<String, dynamic> item) {
    final urgent =
        item['priority'] == 'urgent' ||
        item['status_label']?.toString().toLowerCase() == 'overdue';
    return Padding(
      padding: const EdgeInsets.only(bottom: 9),
      child: Material(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
        clipBehavior: Clip.antiAlias,
        child: Row(
          children: [
            Container(
              width: 4,
              height: 68,
              color: urgent ? const Color(0xFFEF746C) : _teal,
            ),
            Expanded(
              child: ListTile(
                minTileHeight: 68,
                leading: Container(
                  width: 42,
                  height: 42,
                  decoration: BoxDecoration(
                    color: urgent ? const Color(0xFFFFECEA) : _mint,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    _admin
                        ? Icons.fact_check_rounded
                        : Icons.assignment_rounded,
                    color: urgent ? const Color(0xFFCF4D43) : _teal,
                  ),
                ),
                title: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item['number']?.toString() ?? 'Inspection',
                      style: const TextStyle(
                        color: _teal,
                        fontSize: 10,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                    Text(
                      item['title']?.toString().isNotEmpty == true
                          ? item['title'].toString()
                          : 'Inspection',
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: _navy,
                        fontWeight: FontWeight.w800,
                      ),
                    ),
                  ],
                ),
                subtitle: Text(
                  '${item['building'] ?? ''} · Unit ${item['property'] ?? '—'}',
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: _muted, fontSize: 11),
                ),
                trailing: const Icon(
                  Icons.chevron_right_rounded,
                  color: _muted,
                ),
                onTap: () => _admin ? _openReview(item) : _openTask(item),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _roomProgress(String room) {
    final roomItems = _items.where((item) => item['area'] == room).toList();
    final completed = roomItems.where((item) {
      final id = item['id'].toString();
      return (_conditions[id] ?? item['condition']) != null;
    }).length;
    final ratio = roomItems.isEmpty ? 0.0 : completed / roomItems.length;
    return Container(
      margin: const EdgeInsets.only(bottom: 13),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(15),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: Text(
                  room,
                  style: const TextStyle(
                    color: _navy,
                    fontWeight: FontWeight.w900,
                  ),
                ),
              ),
              Text(
                '$completed / ${roomItems.length}',
                style: const TextStyle(
                  color: _teal,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: LinearProgressIndicator(
              value: ratio,
              minHeight: 7,
              color: _teal,
              backgroundColor: const Color(0xFFE4ECEF),
            ),
          ),
        ],
      ),
    );
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
    return [...rows.map(_compactActivity)];
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
      if ((_task['guest_phone'] ?? '').toString().trim().isNotEmpty)
        _actionCard(
          'Call ${(_task['guest_name'] ?? 'tenant').toString()}',
          (_task['guest_phone'] ?? '').toString(),
          Icons.phone_outlined,
          () => _run(_callTenant),
        ),
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
            'expected_completion_date': _expectedCompletionDate(),
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
      _roomProgress(room),
      ..._items.where((e) => e['area'] == room).map((item) {
        final id = item['id'].toString();
        final condition = _conditions[id] ?? item['condition']?.toString();
        return Container(
          margin: const EdgeInsets.only(bottom: 10),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(15),
            border: Border(
              left: BorderSide(
                color: condition == 'issue'
                    ? const Color(0xFFEF746C)
                    : condition == 'good'
                    ? _teal
                    : const Color(0xFFD6E0E4),
                width: 4,
              ),
            ),
          ),
          child: ExpansionTile(
            shape: const Border(),
            collapsedShape: const Border(),
            tilePadding: const EdgeInsets.symmetric(
              horizontal: 14,
              vertical: 3,
            ),
            childrenPadding: const EdgeInsets.fromLTRB(14, 0, 14, 14),
            leading: Icon(
              condition == 'issue'
                  ? Icons.warning_amber_rounded
                  : condition == 'good'
                  ? Icons.check_circle_rounded
                  : Icons.radio_button_unchecked_rounded,
              color: condition == 'issue'
                  ? const Color(0xFFEF746C)
                  : condition == 'good'
                  ? _teal
                  : _muted,
            ),
            title: Text(
              item['name']?.toString() ?? '',
              style: const TextStyle(color: _navy, fontWeight: FontWeight.w900),
            ),
            subtitle: Text(
              condition == null
                  ? 'Tap to inspect'
                  : '${condition.toUpperCase()} · ${(item['photos'] as List?)?.length ?? 0} photos',
              style: const TextStyle(color: _muted, fontSize: 11),
            ),
            children: [
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
              Padding(
                padding: const EdgeInsets.only(top: 10),
                child: TextFormField(
                  initialValue: _comments[id] ?? '',
                  maxLines: 2,
                  decoration: const InputDecoration(
                    labelText: 'Remark',
                    prefixIcon: Icon(Icons.edit_note_rounded),
                  ),
                  onChanged: (value) => _comments[id] = value,
                ),
              ),
              const SizedBox(height: 9),
              TextButton.icon(
                onPressed: () => _addPhoto(id),
                icon: const Icon(Icons.add_a_photo_outlined),
                label: Text(
                  'Add evidence (${(item['photos'] as List?)?.length ?? 0}/20)',
                ),
              ),
              _photoStrip(
                item['photos'],
                onDelete: (index) => _deletePhoto(item, index),
              ),
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

  List<Widget> _selfInspection() {
    final properties = _maps(_options['properties']);
    final selected = properties.where(
      (property) => property['id'].toString() == _selectedProperty,
    );
    final selectedProperty = selected.isEmpty ? null : selected.first;
    return [
      Container(
        padding: const EdgeInsets.all(13),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
        ),
        child: DropdownButtonFormField<String>(
          initialValue: _selectedProperty,
          decoration: InputDecoration(
            labelText: 'SELECTED UNIT',
            prefixIcon: Container(
              margin: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: _mint,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.apartment_rounded, color: _teal),
            ),
          ),
          hint: const Text('Choose building and unit'),
          items: properties
              .map(
                (property) => DropdownMenuItem(
                  value: property['id'].toString(),
                  child: Text(
                    '${property['name']} · ${property['building'] ?? 'No building'}',
                    overflow: TextOverflow.ellipsis,
                  ),
                ),
              )
              .toList(),
          onChanged: (value) => setState(() => _selectedProperty = value),
        ),
      ),
      if (selectedProperty != null)
        Padding(
          padding: const EdgeInsets.fromLTRB(4, 8, 4, 0),
          child: Text(
            'Unit ${selectedProperty['name']} · ${selectedProperty['building'] ?? ''}',
            style: const TextStyle(color: _muted, fontSize: 11),
          ),
        ),
      const SizedBox(height: 22),
      _heading('Choose inspection'),
      Row(
        children: [
          _inspectionTypeCard(
            'check_in',
            'Check-in',
            'Guest arrival',
            Icons.login_rounded,
          ),
          const SizedBox(width: 8),
          _inspectionTypeCard(
            'check_out',
            'Check-out',
            'Departure',
            Icons.logout_rounded,
          ),
          const SizedBox(width: 8),
          _inspectionTypeCard(
            'routine',
            'Routine',
            'General check',
            Icons.manage_search_rounded,
          ),
        ],
      ),
      const SizedBox(height: 18),
      _field('Inspection date', _secondary, hint: 'YYYY-MM-DD'),
      _field(
        'Reason / notes',
        _text,
        hint: 'Why are you inspecting this unit?',
        lines: 3,
      ),
      _button('Create & start inspection', () async {
        if (_selectedProperty == null) {
          throw Exception('Choose a unit first.');
        }
        await _post('/maintainer/inspections', {
          'property_id': _selectedProperty,
          'inspection_type': _selectedType,
          'priority': 'medium',
          'due_date': _secondary.text,
          'notes': _text.text,
        }, 'inspections');
      }),
    ];
  }

  Widget _inspectionTypeCard(
    String value,
    String title,
    String subtitle,
    IconData icon,
  ) {
    final selected = _selectedType == value;
    return Expanded(
      child: InkWell(
        borderRadius: BorderRadius.circular(14),
        onTap: () => setState(() => _selectedType = value),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 160),
          padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 14),
          decoration: BoxDecoration(
            color: selected ? _mint : Colors.white,
            borderRadius: BorderRadius.circular(14),
            border: Border.all(
              color: selected ? _teal : const Color(0xFFDCE5E8),
              width: selected ? 1.5 : 1,
            ),
          ),
          child: Column(
            children: [
              Icon(icon, color: selected ? _teal : _navy),
              const SizedBox(height: 8),
              Text(
                title,
                style: TextStyle(
                  color: selected ? _teal : _navy,
                  fontSize: 12,
                  fontWeight: FontWeight.w900,
                ),
              ),
              Text(
                subtitle,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(color: _muted, fontSize: 9),
              ),
            ],
          ),
        ),
      ),
    );
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

  Widget _bottom() => Container(
    decoration: const BoxDecoration(
      color: Colors.white,
      boxShadow: [
        BoxShadow(
          color: Color(0x140A4167),
          blurRadius: 22,
          offset: Offset(0, -5),
        ),
      ],
    ),
    child: SafeArea(
      top: false,
      minimum: const EdgeInsets.only(top: 5),
      child: BottomNavigationBar(
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
        backgroundColor: Colors.white,
        elevation: 0,
        iconSize: 25,
        selectedFontSize: 11,
        unselectedFontSize: 11,
        selectedLabelStyle: const TextStyle(fontWeight: FontWeight.w900),
        items: [
          const BottomNavigationBarItem(
            icon: Icon(Icons.home_outlined),
            activeIcon: Icon(Icons.home_rounded),
            label: 'Home',
          ),
          BottomNavigationBarItem(
            icon: Icon(
              _admin ? Icons.fact_check_outlined : Icons.assignment_outlined,
            ),
            activeIcon: Icon(
              _admin ? Icons.fact_check_rounded : Icons.assignment_rounded,
            ),
            label: _admin ? 'Queue' : 'Tasks',
          ),
          BottomNavigationBarItem(
            icon: Icon(
              _admin ? Icons.add_circle_outline : Icons.notifications_outlined,
            ),
            activeIcon: Icon(
              _admin ? Icons.add_circle_rounded : Icons.notifications_rounded,
            ),
            label: _admin ? 'Request' : 'Alerts',
          ),
          const BottomNavigationBarItem(
            icon: Icon(Icons.person_outline_rounded),
            activeIcon: Icon(Icons.person_rounded),
            label: 'Profile',
          ),
        ],
      ),
    ),
  );

  Widget _photoStrip(
    dynamic photos, {
    Future<void> Function(int index)? onDelete,
  }) {
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
          return Stack(
            clipBehavior: Clip.none,
            children: [
              InkWell(
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
              ),
              if (onDelete != null)
                Positioned(
                  right: -7,
                  top: -7,
                  child: Material(
                    color: Colors.red,
                    shape: const CircleBorder(),
                    child: IconButton(
                      constraints: const BoxConstraints.tightFor(
                        width: 30,
                        height: 30,
                      ),
                      padding: EdgeInsets.zero,
                      iconSize: 17,
                      color: Colors.white,
                      tooltip: 'Delete photo',
                      onPressed: () => onDelete(index),
                      icon: const Icon(Icons.delete_outline),
                    ),
                  ),
                ),
            ],
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
