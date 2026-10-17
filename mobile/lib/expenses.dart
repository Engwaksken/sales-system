import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';

import 'main.dart';

String _isoDate(DateTime d) => '${d.year.toString().padLeft(4, '0')}-${d.month.toString().padLeft(2, '0')}-${d.day.toString().padLeft(2, '0')}';

class ExpensesView extends StatefulWidget {
  const ExpensesView({super.key});
  @override
  State<ExpensesView> createState() => _ExpensesViewState();
}

class _ExpensesViewState extends State<ExpensesView> {
  final rows = <Map<String, dynamic>>[];
  final search = TextEditingController();
  final scroll = ScrollController();
  int page = 0, lastPage = 1;
  bool loading = false;
  String? error;

  @override
  void initState() {
    super.initState();
    scroll.addListener(() {
      if (scroll.position.extentAfter < 300) _load();
    });
    _reload();
  }

  @override
  void dispose() {
    search.dispose();
    scroll.dispose();
    super.dispose();
  }

  Future<void> _reload() async {
    setState(() { rows.clear(); page = 0; lastPage = 1; error = null; });
    await _load();
  }

  Future<void> _load() async {
    if (loading || page >= lastPage) return;
    setState(() => loading = true);
    try {
      final response = await Api.get('expenses', {'page': '${page + 1}', if (search.text.trim().isNotEmpty) 'q': search.text.trim()});
      final meta = response['meta'] as Map<String, dynamic>? ?? const {};
      setState(() {
        rows.addAll((response['data'] as List? ?? []).map((r) => Map<String, dynamic>.from(r)));
        page = meta['current_page'] as int? ?? page + 1;
        lastPage = meta['last_page'] as int? ?? page;
      });
    } catch (e) {
      setState(() => error = e.toString());
    } finally {
      if (mounted) setState(() => loading = false);
    }
  }

  Future<void> _open([Map<String, dynamic>? expense]) async {
    final changed = await Navigator.of(context).push<bool>(MaterialPageRoute(builder: (_) => ExpenseFormScreen(expense: expense)));
    if (changed == true) _reload();
  }

  @override
  Widget build(BuildContext context) => Scaffold(
    backgroundColor: Colors.transparent,
    floatingActionButton: FloatingActionButton.extended(onPressed: () => _open(), icon: const Icon(Icons.add), label: const Text('Expense')),
    body: Column(children: [
      Padding(padding: const EdgeInsets.fromLTRB(16, 0, 16, 8), child: TextField(
        controller: search, textInputAction: TextInputAction.search, onSubmitted: (_) => _reload(), onChanged: (_) => setState(() {}),
        decoration: InputDecoration(hintText: 'Search expenses', prefixIcon: const Icon(Icons.search), suffixIcon: search.text.isEmpty ? null : IconButton(icon: const Icon(Icons.close), onPressed: () { search.clear(); _reload(); })),
      )),
      Expanded(child: rows.isEmpty && error != null
          ? ErrorMessage(message: error!, onRetry: _reload)
          : rows.isEmpty && loading
              ? const Center(child: CircularProgressIndicator())
              : RefreshIndicator(onRefresh: _reload, child: rows.isEmpty
                  ? ListView(physics: const AlwaysScrollableScrollPhysics(), children: const [SizedBox(height: 120), Center(child: Text('No expenses recorded yet.'))])
                  : ListView.separated(
                      controller: scroll, physics: const AlwaysScrollableScrollPhysics(), padding: const EdgeInsets.fromLTRB(16, 8, 16, 96),
                      itemCount: rows.length + (page < lastPage ? 1 : 0), separatorBuilder: (_, __) => const SizedBox(height: 8),
                      itemBuilder: (context, i) => i == rows.length ? const Padding(padding: EdgeInsets.all(16), child: Center(child: CircularProgressIndicator())) : _ExpenseTile(expense: rows[i], onTap: () => _open(rows[i])),
                    ))),
    ]),
  );
}

class _ExpenseTile extends StatelessWidget {
  const _ExpenseTile({required this.expense, required this.onTap});
  final Map<String, dynamic> expense;
  final VoidCallback onTap;
  @override
  Widget build(BuildContext context) => Card(child: ListTile(
    onTap: onTap,
    leading: CircleAvatar(backgroundColor: const Color(0xFFE6F2EE), child: Icon(expense['receipt_url'] != null ? Icons.receipt_long : Icons.request_quote_outlined, color: brand)),
    title: Text('${expense['title'] ?? 'Expense'}', maxLines: 1, overflow: TextOverflow.ellipsis),
    subtitle: Text('${expense['category'] ?? ''} · ${'${expense['expense_date'] ?? ''}'.split('T').first}', maxLines: 1, overflow: TextOverflow.ellipsis),
    trailing: Text('${expense['amount'] ?? ''}', style: const TextStyle(fontWeight: FontWeight.w600)),
  ));
}

class ExpenseFormScreen extends StatefulWidget {
  const ExpenseFormScreen({this.expense, super.key});
  final Map<String, dynamic>? expense;
  @override
  State<ExpenseFormScreen> createState() => _ExpenseFormScreenState();
}

class _ExpenseFormScreenState extends State<ExpenseFormScreen> {
  final form = GlobalKey<FormState>();
  late final title = TextEditingController(text: widget.expense?['title']?.toString());
  late final amount = TextEditingController(text: widget.expense?['amount']?.toString());
  late final category = TextEditingController(text: widget.expense?['category']?.toString());
  late final description = TextEditingController(text: widget.expense?['description']?.toString());
  late DateTime date = DateTime.tryParse('${widget.expense?['expense_date'] ?? ''}') ?? DateTime.now();
  XFile? receipt;
  bool busy = false;
  String? error;

  bool get editing => widget.expense != null;
  String? get receiptUrl => widget.expense?['receipt_url'] as String?;

  @override
  void dispose() {
    for (final c in [title, amount, category, description]) {
      c.dispose();
    }
    super.dispose();
  }

  Future<void> _pickReceipt(ImageSource source) async {
    final file = await ImagePicker().pickImage(source: source, imageQuality: 85, maxWidth: 2000);
    if (file != null) setState(() => receipt = file);
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(context: context, initialDate: date, firstDate: DateTime(2000), lastDate: DateTime.now().add(const Duration(days: 1)));
    if (picked != null) setState(() => date = picked);
  }

  Future<void> _save() async {
    if (!form.currentState!.validate()) return;
    setState(() { busy = true; error = null; });
    try {
      await Api.multipart(editing ? 'expenses/${widget.expense!['id']}' : 'expenses', {
        if (editing) '_method': 'PUT',
        'title': title.text.trim(), 'amount': amount.text.trim(), 'category': category.text.trim(),
        'expense_date': _isoDate(date), 'description': description.text.trim(),
      }, filePath: receipt?.path, fileField: 'receipt_image');
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      setState(() => error = e.toString().replaceFirst('Exception: ', ''));
    } finally {
      if (mounted) setState(() => busy = false);
    }
  }

  Future<void> _delete() async {
    final confirmed = await showDialog<bool>(context: context, builder: (context) => AlertDialog(
      title: const Text('Delete expense?'), content: const Text('This cannot be undone.'),
      actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')), FilledButton(onPressed: () => Navigator.pop(context, true), child: const Text('Delete'))],
    ));
    if (confirmed != true) return;
    setState(() { busy = true; error = null; });
    try {
      await Api.delete('expenses/${widget.expense!['id']}');
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      setState(() { busy = false; error = e.toString().replaceFirst('Exception: ', ''); });
    }
  }

  String? _required(String? v) => v == null || v.trim().isEmpty ? 'Required' : null;

  @override
  Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: Text(editing ? 'Edit expense' : 'New expense'), actions: [
      if (editing) IconButton(onPressed: busy ? null : _delete, tooltip: 'Delete', icon: const Icon(Icons.delete_outline)),
    ]),
    body: Form(key: form, child: ListView(padding: const EdgeInsets.all(16), children: [
      TextFormField(controller: title, validator: _required, textCapitalization: TextCapitalization.sentences, decoration: const InputDecoration(labelText: 'Title')),
      const SizedBox(height: 12),
      TextFormField(controller: amount, keyboardType: const TextInputType.numberWithOptions(decimal: true), decoration: const InputDecoration(labelText: 'Amount'),
        validator: (v) => _required(v) ?? ((double.tryParse(v!.trim()) ?? -1) < 0 ? 'Enter a valid amount' : null)),
      const SizedBox(height: 12),
      TextFormField(controller: category, validator: _required, textCapitalization: TextCapitalization.words, decoration: const InputDecoration(labelText: 'Category')),
      const SizedBox(height: 12),
      ListTile(tileColor: Colors.white, shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)), leading: const Icon(Icons.event), title: const Text('Date'), subtitle: Text(_isoDate(date)), onTap: _pickDate),
      const SizedBox(height: 12),
      TextFormField(controller: description, maxLines: 3, textCapitalization: TextCapitalization.sentences, decoration: const InputDecoration(labelText: 'Description (optional)')),
      const SizedBox(height: 20),
      Text('Receipt', style: Theme.of(context).textTheme.titleMedium),
      const SizedBox(height: 8),
      if (receipt != null)
        ClipRRect(borderRadius: BorderRadius.circular(14), child: Image.file(File(receipt!.path), height: 220, fit: BoxFit.cover))
      else if (receiptUrl != null)
        ClipRRect(borderRadius: BorderRadius.circular(14), child: Image.network(receiptUrl!, headers: Api.authHeaders, height: 220, fit: BoxFit.cover,
          errorBuilder: (_, __, ___) => const SizedBox(height: 60, child: Center(child: Text('Could not load receipt image.'))))),
      const SizedBox(height: 8),
      Row(children: [
        Expanded(child: OutlinedButton.icon(onPressed: busy ? null : () => _pickReceipt(ImageSource.camera), icon: const Icon(Icons.photo_camera_outlined), label: const Text('Camera'))),
        const SizedBox(width: 12),
        Expanded(child: OutlinedButton.icon(onPressed: busy ? null : () => _pickReceipt(ImageSource.gallery), icon: const Icon(Icons.photo_library_outlined), label: const Text('Gallery'))),
      ]),
      if (error != null) ...[const SizedBox(height: 12), Text(error!, style: const TextStyle(color: Colors.red))],
      const SizedBox(height: 20),
      FilledButton(onPressed: busy ? null : _save, style: FilledButton.styleFrom(minimumSize: const Size.fromHeight(52)), child: busy ? const CircularProgressIndicator() : Text(editing ? 'Save changes' : 'Save expense')),
    ])),
  );
}
