import 'dart:async';
import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';
import 'native_field_app.dart';

const _baseUrl = 'https://rms.pattern.ae';
const _navy = Color(0xFF143248);
const _teal = Color(0xFF12A99A);

class LiveFieldApp extends StatelessWidget {
  const LiveFieldApp({super.key});

  @override
  Widget build(BuildContext context) => MaterialApp(
    title: 'HHMS Field',
    debugShowCheckedModeBanner: false,
    theme: ThemeData(
      colorScheme: ColorScheme.fromSeed(seedColor: _teal),
      scaffoldBackgroundColor: Colors.white,
      fontFamily: 'Roboto',
    ),
    home: const LiveFieldScreen(),
  );
}

class LiveFieldScreen extends StatefulWidget {
  const LiveFieldScreen({super.key});

  @override
  State<LiveFieldScreen> createState() => _LiveFieldScreenState();
}

class _LiveFieldScreenState extends State<LiveFieldScreen> {
  late final WebViewController _web;
  final _picker = ImagePicker();
  bool _loading = true;
  bool _failed = false;
  int _progress = 0;
  String _title = 'HHMS Field';
  bool _native = false;
  bool _loginPage = false;
  bool _loginSubmitted = false;
  String? _loginError;
  final _email = TextEditingController();
  final _password = TextEditingController();
  Map<String, dynamic>? _user;
  final Map<int, Completer<Map<String, dynamic>>> _requests = {};
  int _requestId = 0;

  Map<String, dynamic> _jsonMap(dynamic value) {
    if (value is Map<String, dynamic>) return value;
    if (value is Map) {
      return value.map((key, item) => MapEntry(key.toString(), item));
    }
    if (value is List && value.isNotEmpty) return _jsonMap(value.first);
    return <String, dynamic>{};
  }

  @override
  void initState() {
    super.initState();
    _web = WebViewController()
      ..setJavaScriptMode(JavaScriptMode.unrestricted)
      ..setBackgroundColor(Colors.white)
      ..addJavaScriptChannel(
        'FieldBridge',
        onMessageReceived: (message) {
          try {
            final result = jsonDecode(message.message) as Map<String, dynamic>;
            _requests.remove(result['id'])?.complete(result);
          } catch (_) {}
        },
      )
      ..setNavigationDelegate(
        NavigationDelegate(
          onProgress: (value) {
            if (mounted) setState(() => _progress = value);
          },
          onPageStarted: (_) {
            if (mounted) {
              setState(() {
                _loading = true;
                _failed = false;
              });
            }
          },
          onPageFinished: (url) {
            if (!mounted) return;
            final path = Uri.tryParse(url)?.path ?? '';
            setState(() {
              _loading = false;
              _loginPage = path == '/login';
              if (_loginPage && _loginSubmitted) {
                _loginError = 'Sign in failed. Check your email and password.';
                _loginSubmitted = false;
              }
              _title = path.contains('inspection')
                  ? 'Inspections'
                  : path.contains('tasks')
                  ? 'My tasks'
                  : path.contains('profile')
                  ? 'Profile'
                  : 'HHMS Field';
            });
            if (path != '/login') unawaited(_startNative());
          },
          onWebResourceError: (error) {
            if (error.isForMainFrame != true || !mounted) return;
            setState(() {
              _loading = false;
              _failed = true;
            });
          },
          onNavigationRequest: (request) {
            final uri = Uri.tryParse(request.url);
            if (uri == null) return NavigationDecision.prevent;
            if (uri.scheme == 'https' && uri.host == Uri.parse(_baseUrl).host) {
              return NavigationDecision.navigate;
            }
            unawaited(launchUrl(uri, mode: LaunchMode.externalApplication));
            return NavigationDecision.prevent;
          },
        ),
      )
      ..loadRequest(Uri.parse('$_baseUrl/dashboard'));

    final platform = _web.platform;
    if (platform is AndroidWebViewController) {
      unawaited(platform.setOnShowFileSelector(_selectPhoto));
    }
  }

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _signIn() async {
    if (!_loginPage || _loading) return;
    setState(() {
      _loginSubmitted = true;
      _loginError = null;
      _loading = true;
    });
    final email = jsonEncode(_email.text.trim());
    final password = jsonEncode(_password.text);
    try {
      await _web.runJavaScript('''
        (() => {
          const form = document.querySelector('form[action\$="/login"]');
          if (!form) return;
          form.querySelector('input[name="email"]').value = $email;
          form.querySelector('input[name="password"]').value = $password;
          form.requestSubmit();
        })();
      ''');
    } catch (_) {
      if (mounted) {
        setState(() {
          _loading = false;
          _loginError = 'Could not submit sign in. Try again.';
        });
      }
    }
  }

  Widget _nativeLogin() => Scaffold(
    backgroundColor: const Color(0xFFF4F7F5),
    body: SafeArea(
      child: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(26),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 430),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Center(
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(20),
                    child: Image.asset(
                      'assets/brand-mark.png',
                      width: 88,
                      height: 88,
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                const Text(
                  'Sign in',
                  style: TextStyle(
                    color: _navy,
                    fontSize: 30,
                    fontWeight: FontWeight.w900,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 6),
                const Text(
                  'Secure access for the field team',
                  style: TextStyle(color: Color(0xFF748895)),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 28),
                TextField(
                  controller: _email,
                  keyboardType: TextInputType.emailAddress,
                  decoration: const InputDecoration(
                    labelText: 'Email address',
                    filled: true,
                    fillColor: Colors.white,
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 14),
                TextField(
                  controller: _password,
                  obscureText: true,
                  decoration: const InputDecoration(
                    labelText: 'Password',
                    filled: true,
                    fillColor: Colors.white,
                    border: OutlineInputBorder(),
                  ),
                ),
                if (_loginError != null)
                  Padding(
                    padding: const EdgeInsets.only(top: 12),
                    child: Text(
                      _loginError!,
                      style: const TextStyle(color: Colors.red),
                    ),
                  ),
                const SizedBox(height: 20),
                SizedBox(
                  height: 52,
                  child: FilledButton(
                    onPressed: _loading ? null : _signIn,
                    style: FilledButton.styleFrom(backgroundColor: _teal),
                    child: const Text('Sign in'),
                  ),
                ),
                TextButton(
                  onPressed: () => launchUrl(
                    Uri.parse('$_baseUrl/forgot-password'),
                    mode: LaunchMode.externalApplication,
                  ),
                  child: const Text('Forgot password?'),
                ),
              ],
            ),
          ),
        ),
      ),
    ),
  );

  Future<Map<String, dynamic>> _api(
    String method,
    String path, [
    Map<String, dynamic>? data,
  ]) async {
    final id = ++_requestId;
    final completer = Completer<Map<String, dynamic>>();
    _requests[id] = completer;
    final payload = jsonEncode({
      'id': id,
      'method': method,
      'path': path,
      'data': data,
    });
    await _web.runJavaScript('''
      (async () => {
        const p = $payload;
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.content ||
            document.querySelector('input[name="_token"]')?.value || '';
          const response = await fetch(p.path, {
            method: p.method, credentials: 'same-origin',
            headers: {'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},
            body: p.method === 'GET' ? undefined : JSON.stringify(p.data || {})
          });
          const content = response.headers.get('content-type') || '';
          const body = content.includes('json') ? await response.json() : null;
          FieldBridge.postMessage(JSON.stringify({id:p.id,status:response.status,body:body,url:response.url}));
        } catch (error) {
          FieldBridge.postMessage(JSON.stringify({id:p.id,status:0,error:String(error)}));
        }
      })();
    ''');
    final result = await completer.future.timeout(
      const Duration(seconds: 45),
      onTimeout: () {
        _requests.remove(id);
        return {'status': 0, 'error': 'Connection timed out'};
      },
    );
    final status = result['status'] as int? ?? 0;
    if (status < 200 || status >= 300) {
      final body = result['body'];
      throw Exception(
        body is Map
            ? (body['message'] ?? 'Request failed ($status)')
            : (result['error'] ?? 'Request failed ($status)'),
      );
    }
    return _jsonMap(result['body']);
  }

  Future<Map<String, dynamic>> _upload(
    String path,
    Map<String, String> fields,
    XFile file, [
    Map<String, XFile>? extras,
  ]) async {
    final id = ++_requestId;
    final completer = Completer<Map<String, dynamic>>();
    _requests[id] = completer;
    final bytes = await file.readAsBytes();
    if (bytes.length > 8 * 1024 * 1024) {
      throw Exception('Choose a file smaller than 8 MB.');
    }
    final extraPayload = <String, dynamic>{};
    for (final entry in (extras ?? {}).entries) {
      final extraBytes = await entry.value.readAsBytes();
      if (extraBytes.length > 8 * 1024 * 1024) {
        throw Exception('Choose a file smaller than 8 MB.');
      }
      extraPayload[entry.key] = {
        'base64': base64Encode(extraBytes),
        'name': entry.value.name,
      };
    }
    final payload = jsonEncode({
      'id': id,
      'path': path,
      'fields': fields,
      'base64': base64Encode(bytes),
      'name': file.name,
      'extras': extraPayload,
    });
    await _web.runJavaScript('''
      (async () => {
        const p = $payload;
        try {
          const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
          const raw = atob(p.base64);
          const bytes = Uint8Array.from(raw, c => c.charCodeAt(0));
          const form = new FormData();
          for (const [key,value] of Object.entries(p.fields)) {
            if (key !== '__file_field') form.append(key,value);
          }
          const mime = name => name.toLowerCase().endsWith('.pdf') ? 'application/pdf' :
            name.toLowerCase().endsWith('.png') ? 'image/png' :
            name.toLowerCase().endsWith('.webp') ? 'image/webp' : 'image/jpeg';
          form.append(p.fields.__file_field || 'photo', new Blob([bytes], {type:mime(p.name)}), p.name);
          for (const [key,file] of Object.entries(p.extras || {})) {
            const extraBytes = Uint8Array.from(atob(file.base64), c => c.charCodeAt(0));
            form.append(key, new Blob([extraBytes], {type:mime(file.name)}), file.name);
          }
          const response = await fetch(p.path, {method:'POST',credentials:'same-origin',
            headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf},body:form});
          const content = response.headers.get('content-type') || '';
          const body = content.includes('json') ? await response.json() : null;
          FieldBridge.postMessage(JSON.stringify({id:p.id,status:response.status,body:body}));
        } catch (error) {
          FieldBridge.postMessage(JSON.stringify({id:p.id,status:0,error:String(error)}));
        }
      })();
    ''');
    final result = await completer.future.timeout(
      const Duration(seconds: 60),
      onTimeout: () {
        _requests.remove(id);
        return {'status': 0, 'error': 'Upload timed out'};
      },
    );
    final status = result['status'] as int? ?? 0;
    if (status < 200 || status >= 300) {
      throw Exception(
        _jsonMap(result['body'])['message'] ??
            result['error'] ??
            'Photo upload failed',
      );
    }
    return _jsonMap(result['body']);
  }

  Future<void> _startNative() async {
    try {
      final data = await _api('GET', '/field/api/bootstrap');
      if (!mounted) return;
      setState(() {
        _user = _jsonMap(data['user']);
        _native = true;
      });
    } catch (_) {
      // The server may not have the field API yet; keep the existing site usable.
    }
  }

  Future<List<String>> _selectPhoto(FileSelectorParams params) async {
    if (!mounted) return [];
    final source = await showModalBottomSheet<ImageSource>(
      context: context,
      showDragHandle: true,
      builder: (context) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            ListTile(
              leading: const Icon(Icons.photo_camera_outlined, color: _teal),
              title: const Text('Take photo'),
              onTap: () => Navigator.pop(context, ImageSource.camera),
            ),
            ListTile(
              leading: const Icon(Icons.photo_library_outlined, color: _teal),
              title: const Text('Choose from gallery'),
              onTap: () => Navigator.pop(context, ImageSource.gallery),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
    if (source == null) return [];
    try {
      if (source == ImageSource.gallery &&
          params.mode == FileSelectorMode.openMultiple) {
        final files = await _picker.pickMultiImage(
          imageQuality: 72,
          maxWidth: 1600,
          maxHeight: 1600,
          limit: 5,
          requestFullMetadata: false,
        );
        return files.map((file) => Uri.file(file.path).toString()).toList();
      }
      final file = await _picker.pickImage(
        source: source,
        imageQuality: 72,
        maxWidth: 1600,
        maxHeight: 1600,
        requestFullMetadata: false,
      );
      return file == null ? [] : [Uri.file(file.path).toString()];
    } catch (_) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Photo could not be opened. Please try again.'),
          ),
        );
      }
      return [];
    }
  }

  Future<void> _back() async {
    if (await _web.canGoBack()) {
      await _web.goBack();
    } else if (mounted) {
      await _web.loadRequest(Uri.parse('$_baseUrl/dashboard'));
    }
  }

  @override
  Widget build(BuildContext context) => PopScope(
    canPop: false,
    onPopInvokedWithResult: (didPop, result) {
      if (!didPop) unawaited(_back());
    },
    child: _native && _user != null
        ? Stack(
            children: [
              Opacity(
                opacity: 0,
                child: IgnorePointer(child: WebViewWidget(controller: _web)),
              ),
              NativeFieldApp(
                user: _user!,
                request: _api,
                upload: _upload,
                signOut: () async {
                  await _api('POST', '/logout');
                  await _web.loadRequest(Uri.parse('$_baseUrl/login'));
                  if (mounted) {
                    setState(() {
                      _native = false;
                      _user = null;
                    });
                  }
                },
              ),
            ],
          )
        : _loginPage
        ? Stack(
            children: [
              Opacity(
                opacity: 0,
                child: IgnorePointer(child: WebViewWidget(controller: _web)),
              ),
              _nativeLogin(),
            ],
          )
        : Scaffold(
            appBar: AppBar(
              backgroundColor: _navy,
              foregroundColor: Colors.white,
              titleSpacing: 12,
              title: Row(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: Image.asset(
                      'assets/brand-mark.png',
                      width: 25,
                      height: 25,
                    ),
                  ),
                  const SizedBox(width: 10),
                  Text(
                    _title,
                    style: const TextStyle(
                      fontSize: 17,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                ],
              ),
              actions: [
                IconButton(
                  tooltip: 'Home',
                  icon: const Icon(Icons.home_outlined),
                  onPressed: () =>
                      _web.loadRequest(Uri.parse('$_baseUrl/dashboard')),
                ),
                IconButton(
                  tooltip: 'Refresh',
                  icon: const Icon(Icons.refresh),
                  onPressed: () => _web.reload(),
                ),
              ],
            ),
            body: Stack(
              children: [
                WebViewWidget(controller: _web),
                if (_loading)
                  LinearProgressIndicator(
                    value: _progress > 0 ? _progress / 100 : null,
                    color: _teal,
                  ),
                if (_failed)
                  Center(
                    child: Padding(
                      padding: const EdgeInsets.all(28),
                      child: Column(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(
                            Icons.wifi_off_rounded,
                            color: _navy,
                            size: 44,
                          ),
                          const SizedBox(height: 14),
                          const Text(
                            'Cannot reach HHMS',
                            style: TextStyle(
                              fontSize: 20,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 8),
                          const Text(
                            'Check your internet connection and try again.',
                            textAlign: TextAlign.center,
                          ),
                          const SizedBox(height: 16),
                          FilledButton(
                            onPressed: () => _web.reload(),
                            child: const Text('Try again'),
                          ),
                        ],
                      ),
                    ),
                  ),
              ],
            ),
          ),
  );
}
