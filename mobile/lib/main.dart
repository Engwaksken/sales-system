import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'expenses.dart';

const brand = Color(0xFF176B57);

void main() => runApp(const NSalesApp());

class NSalesApp extends StatelessWidget {
  const NSalesApp({super.key});

  @override
  Widget build(BuildContext context) => MaterialApp(
        title: 'NSales',
        debugShowCheckedModeBanner: false,
        theme: ThemeData(
          colorScheme: ColorScheme.fromSeed(seedColor: brand),
          scaffoldBackgroundColor: const Color(0xFFF6F8F7),
          useMaterial3: true,
          inputDecorationTheme: InputDecorationTheme(
            filled: true,
            fillColor: Colors.white,
            border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: BorderSide.none),
          ),
        ),
        home: const SessionGate(),
      );
}

class Api {
  static String baseUrl = const String.fromEnvironment(
    'NSALES_API_URL',
    defaultValue: 'https://nsales.kemmytech.com/api/mobile',
  );
  static String? token;
  static Future<Map<String, dynamic>> get(String path, [Map<String, String>? query]) async {
    final response = await http.get(Uri.parse('$baseUrl/$path').replace(queryParameters: query), headers: _headers);
    return _decode(response);
  }

  static Future<Map<String, dynamic>> post(String path, Map<String, dynamic> body) async {
    final response = await http.post(Uri.parse('$baseUrl/$path'), headers: _headers, body: jsonEncode(body));
    return _decode(response);
  }

  static Future<Map<String, dynamic>> delete(String path) async {
    final response = await http.delete(Uri.parse('$baseUrl/$path'), headers: _headers);
    return _decode(response);
  }

  /// Multipart POST; pass `_method: PUT` in [fields] for updates, since PHP
  /// only parses multipart bodies on POST.
  static Future<Map<String, dynamic>> multipart(String path, Map<String, String> fields, {String? filePath, String fileField = 'file'}) async {
    final request = http.MultipartRequest('POST', Uri.parse('$baseUrl/$path'))
      ..headers.addAll(authHeaders)
      ..fields.addAll(fields);
    if (filePath != null) request.files.add(await http.MultipartFile.fromPath(fileField, filePath));
    return _decode(await http.Response.fromStream(await request.send()));
  }

  static Map<String, String> get authHeaders => {
        'Accept': 'application/json',
        if (token != null) 'Authorization': 'Bearer $token',
      };

  static Map<String, String> get _headers => {...authHeaders, 'Content-Type': 'application/json'};

  static Map<String, dynamic> _decode(http.Response response) {
    Map<String, dynamic>? body;
    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) body = decoded;
    } catch (_) {}
    if (response.statusCode < 200 || response.statusCode >= 300) {
      final errors = body?['errors'] as Map<String, dynamic>?;
      final firstError = errors?.values.firstOrNull;
      if (firstError is List && firstError.isNotEmpty) throw Exception(firstError.first);
      throw Exception(body?['message'] ?? switch (response.statusCode) {
        401 => 'Your session has expired. Please sign in again.',
        403 => 'You do not have permission to do this.',
        404 => 'Not found.',
        _ => 'Request failed (${response.statusCode}).',
      });
    }
    if (body == null) throw Exception('Unexpected response from server.');
    return body;
  }
}

class SessionGate extends StatefulWidget {
  const SessionGate({super.key});
  @override
  State<SessionGate> createState() => _SessionGateState();
}

class _SessionGateState extends State<SessionGate> {
  Map<String, dynamic>? user;
  bool loading = true;

  @override
  void initState() {
    super.initState();
    _restore();
  }

  Future<void> _restore() async {
    final prefs = await SharedPreferences.getInstance();
    Api.token = prefs.getString('token');
    if (Api.token != null) {
      try {
        user = (await Api.get('me'))['user'] as Map<String, dynamic>;
      } catch (_) {
        await prefs.remove('token');
        Api.token = null;
      }
    }
    if (mounted) setState(() => loading = false);
  }

  Future<void> _signedIn(Map<String, dynamic> signedInUser, String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('token', token);
    Api.token = token;
    setState(() => user = signedInUser);
  }

  Future<void> _signOut() async {
    try { await Api.post('logout', {}); } catch (_) {}
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('token');
    Api.token = null;
    setState(() => user = null);
  }

  @override
  Widget build(BuildContext context) {
    if (loading) return const Scaffold(body: Center(child: CircularProgressIndicator()));
    if (user == null) return LoginScreen(onSignedIn: _signedIn);
    return HomeScreen(user: user!, onSignOut: _signOut);
  }
}

class LoginScreen extends StatefulWidget {
  const LoginScreen({required this.onSignedIn, super.key});
  final Future<void> Function(Map<String, dynamic>, String) onSignedIn;
  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final email = TextEditingController();
  final password = TextEditingController();
  bool busy = false;
  String? error;

  Future<void> _login() async {
    setState(() { busy = true; error = null; });
    try {
      final response = await Api.post('login', {
        'email': email.text.trim(), 'password': password.text, 'device_name': 'NSales Mobile',
      });
      await widget.onSignedIn(response['user'] as Map<String, dynamic>, response['token'] as String);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally { if (mounted) setState(() => busy = false); }
  }

  @override
  Widget build(BuildContext context) => Scaffold(
        body: SafeArea(child: Center(child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: ConstrainedBox(constraints: const BoxConstraints(maxWidth: 440), child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Icon(Icons.storefront_rounded, color: brand, size: 58),
              const SizedBox(height: 16),
              Text('Welcome to NSales', textAlign: TextAlign.center, style: Theme.of(context).textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              const Text('Sign in to manage your business or shop.', textAlign: TextAlign.center),
              const SizedBox(height: 32),
              TextField(controller: email, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email', prefixIcon: Icon(Icons.email_outlined))),
              const SizedBox(height: 14),
              TextField(controller: password, obscureText: true, onSubmitted: (_) => _login(), decoration: const InputDecoration(labelText: 'Password', prefixIcon: Icon(Icons.lock_outline))),
              if (error != null) ...[const SizedBox(height: 12), Text(error!, style: const TextStyle(color: Colors.red))],
              const SizedBox(height: 20),
              FilledButton(onPressed: busy ? null : _login, style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)), child: busy ? const CircularProgressIndicator() : const Text('Sign in')),
              const SizedBox(height: 12),
              const Text('Use your NSales account credentials.', textAlign: TextAlign.center, style: TextStyle(color: Colors.black54)),
            ],
          )),
        ))),
      );
}

class HomeScreen extends StatefulWidget {
  const HomeScreen({required this.user, required this.onSignOut, super.key});
  final Map<String, dynamic> user;
  final Future<void> Function() onSignOut;
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  int tab = 0;
  String get role => '${widget.user['role'] ?? 'customer'}'.toLowerCase();
  bool get isAdmin => !['customer', 'client'].contains(role);
  List get _permissions => widget.user['permissions'] as List? ?? const [];
  bool can(String permission) => permission.isEmpty || _permissions.contains('*') || _permissions.contains(permission);

  static const _staffSections = [
    {'title': 'Overview', 'resource': 'dashboard', 'permission': ''},
    {'title': 'Sales', 'resource': 'sales', 'permission': 'manage_sales'},
    {'title': 'Orders', 'resource': 'orders', 'permission': 'manage_orders'},
    {'title': 'Expenses', 'resource': 'expenses', 'permission': 'manage_expenses'},
    {'title': 'Products', 'resource': 'products', 'permission': 'manage_products'},
    {'title': 'Customers', 'resource': 'customers', 'permission': 'manage_customers'},
    {'title': 'Stock', 'resource': 'stock', 'permission': 'manage_stock'},
  ];

  // Cashiers are refused by the expenses API regardless of permissions.
  List<Map<String, String>> get sections => isAdmin
      ? _staffSections.where((s) => can(s['permission']!) && !(s['resource'] == 'expenses' && role == 'cashier')).toList()
      : const [{'title': 'Shop', 'resource': 'products'}, {'title': 'My orders', 'resource': 'orders'}, {'title': 'Payments', 'resource': 'payments'}];

  /// NavigationBar fits five destinations; anything beyond four goes under "More".
  List<Map<String, String>> get _tabs => sections.length <= 5 ? sections : [...sections.take(4), const {'title': 'More', 'resource': 'more'}];

  Widget _sectionBody(Map<String, String> section) => switch (section['resource']) {
    'dashboard' => const DashboardView(),
    'expenses' => const ExpensesView(),
    'more' => ListView(padding: const EdgeInsets.symmetric(horizontal: 16), children: sections.skip(4).map((s) => Card(child: ListTile(
      leading: Icon(_icon(s['resource']!), color: brand), title: Text(s['title']!), trailing: const Icon(Icons.chevron_right),
      onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => Scaffold(appBar: AppBar(title: Text(s['title']!)), body: _sectionBody(s)))),
    ))).toList()),
    _ => ResourceView(key: ValueKey(section['resource']), title: section['title']!, resource: section['resource']!),
  };

  @override
  Widget build(BuildContext context) {
    final tabs = _tabs;
    final current = tabs[tab.clamp(0, tabs.length - 1)];
    return Scaffold(
      appBar: AppBar(title: const Text('NSales'), actions: [IconButton(onPressed: widget.onSignOut, tooltip: 'Sign out', icon: const Icon(Icons.logout))]),
      body: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
        Padding(padding: const EdgeInsets.fromLTRB(20, 8, 20, 16), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Text('Hello, ${widget.user['name'] ?? 'there'}', style: Theme.of(context).textTheme.headlineSmall?.copyWith(fontWeight: FontWeight.bold)),
          Text(isAdmin ? 'Administrator workspace' : 'Your customer account', style: const TextStyle(color: Colors.black54)),
        ])),
        Expanded(child: _sectionBody(current)),
      ]),
      bottomNavigationBar: NavigationBar(selectedIndex: tab.clamp(0, tabs.length - 1), onDestinationSelected: (i) => setState(() => tab = i), destinations: tabs.map((s) => NavigationDestination(icon: Icon(_icon(s['resource']!)), label: s['title']!)).toList()),
    );
  }
}

IconData _icon(String key) => switch (key) {
  'dashboard' => Icons.dashboard_outlined, 'products' => Icons.inventory_2_outlined,
  'orders' => Icons.shopping_bag_outlined, 'sales' => Icons.point_of_sale,
  'customers' => Icons.people_outline, 'stock' => Icons.warehouse_outlined,
  'payments' => Icons.payments_outlined, 'expenses' => Icons.request_quote_outlined,
  'more' => Icons.more_horiz, _ => Icons.circle_outlined,
};

class DashboardView extends StatefulWidget {
  const DashboardView({super.key});
  @override
  State<DashboardView> createState() => _DashboardViewState();
}

class _DashboardViewState extends State<DashboardView> {
  late Future<Map<String, dynamic>> future = Api.get('dashboard');
  Future<void> _reload() async {
    setState(() => future = Api.get('dashboard'));
    await future.catchError((_) => <String, dynamic>{});
  }

  @override
  Widget build(BuildContext context) => FutureBuilder<Map<String, dynamic>>(
    future: future,
    builder: (context, snapshot) {
      if (snapshot.connectionState == ConnectionState.waiting) return const Center(child: CircularProgressIndicator());
      if (snapshot.hasError) return ErrorMessage(message: snapshot.error.toString(), onRetry: _reload);
      final data = snapshot.data!;
      final stats = (data['stats'] as List? ?? []).cast<Map<String, dynamic>>();
      return RefreshIndicator(onRefresh: _reload, child: ListView(physics: const AlwaysScrollableScrollPhysics(), padding: const EdgeInsets.fromLTRB(16, 0, 16, 24), children: [
        GridView.count(shrinkWrap: true, physics: const NeverScrollableScrollPhysics(), crossAxisCount: 2, childAspectRatio: 1.55, mainAxisSpacing: 12, crossAxisSpacing: 12,
          children: stats.map((s) => Card(child: Padding(padding: const EdgeInsets.all(14), child: Column(crossAxisAlignment: CrossAxisAlignment.start, mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
            Icon(_icon('${s['icon']}'), color: brand), Text('${s['label']}', style: const TextStyle(color: Colors.black54)), Text('${s['type'] == 'money' ? data['currency'] ?? 'UGX ' : ''}${s['value']}', style: const TextStyle(fontSize: 17, fontWeight: FontWeight.bold)),
          ])))).toList()),
        const SizedBox(height: 18), Text('Recent orders', style: Theme.of(context).textTheme.titleLarge),
        ...(data['recent_orders'] as List? ?? []).map((row) => DataCard(row: Map<String, dynamic>.from(row))),
      ]));
    },
  );
}

class ResourceView extends StatefulWidget {
  const ResourceView({required this.title, required this.resource, super.key});
  final String title, resource;
  @override
  State<ResourceView> createState() => _ResourceViewState();
}

class _ResourceViewState extends State<ResourceView> {
  late Future<Map<String, dynamic>> future = Api.get('resources/${widget.resource}');
  Future<void> _reload() async {
    setState(() => future = Api.get('resources/${widget.resource}'));
    await future.catchError((_) => <String, dynamic>{});
  }

  @override
  Widget build(BuildContext context) => FutureBuilder<Map<String, dynamic>>(
    future: future,
    builder: (context, snapshot) {
      if (snapshot.connectionState == ConnectionState.waiting) return const Center(child: CircularProgressIndicator());
      if (snapshot.hasError) return ErrorMessage(message: snapshot.error.toString(), onRetry: _reload);
      final rows = (snapshot.data?['data'] as List? ?? []).map((r) => Map<String, dynamic>.from(r)).toList();
      return RefreshIndicator(onRefresh: _reload, child: rows.isEmpty
          ? ListView(physics: const AlwaysScrollableScrollPhysics(), children: [const SizedBox(height: 120), Center(child: Text('No ${widget.title} found yet.'))])
          : ListView.separated(physics: const AlwaysScrollableScrollPhysics(), padding: const EdgeInsets.all(16), itemCount: rows.length, separatorBuilder: (_, __) => const SizedBox(height: 8), itemBuilder: (context, index) => DataCard(row: rows[index])));
    },
  );
}

class DataCard extends StatelessWidget {
  const DataCard({required this.row, super.key});
  final Map<String, dynamic> row;
  @override
  Widget build(BuildContext context) {
    final title = row['name'] ?? row['order_number'] ?? row['invoice_no'] ?? row['product_name'] ?? row['title'] ?? 'Record #${row['id'] ?? ''}';
    final subtitle = row['status'] ?? row['email'] ?? row['sku'] ?? row['created_at'] ?? '';
    final amount = row['total_amount'] ?? row['total'] ?? row['amount'];
    return Card(child: ListTile(leading: const CircleAvatar(backgroundColor: Color(0xFFE6F2EE), child: Icon(Icons.receipt_long, color: brand)), title: Text('$title', maxLines: 1, overflow: TextOverflow.ellipsis), subtitle: Text('$subtitle', maxLines: 1, overflow: TextOverflow.ellipsis), trailing: amount == null ? null : Text('$amount', style: const TextStyle(fontWeight: FontWeight.w600))));
  }
}

class ErrorMessage extends StatelessWidget {
  const ErrorMessage({required this.message, this.onRetry, super.key});
  final String message;
  final VoidCallback? onRetry;
  @override
  Widget build(BuildContext context) => Center(child: Padding(padding: const EdgeInsets.all(24), child: Column(mainAxisSize: MainAxisSize.min, children: [
    Text(message.replaceFirst('Exception: ', ''), textAlign: TextAlign.center, style: const TextStyle(color: Colors.red)),
    if (onRetry != null) ...[const SizedBox(height: 12), OutlinedButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh), label: const Text('Try again'))],
  ])));
}
