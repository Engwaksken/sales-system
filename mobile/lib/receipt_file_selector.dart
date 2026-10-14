import 'dart:io';

import 'package:image_picker/image_picker.dart';
import 'package:webview_flutter/webview_flutter.dart';
import 'package:webview_flutter_android/webview_flutter_android.dart';

/// Enables HTML `<input type="file">` controls inside the Android WebView.
///
/// This is used by the Laravel expense receipt scanner so Android users can
/// either take a new receipt photo or pick an existing receipt from the phone.
Future<void> configureReceiptFileSelector(
  WebViewController controller,
) async {
  if (!Platform.isAndroid) {
    return;
  }

  final platformController = controller.platform;

  if (platformController is! AndroidWebViewController) {
    return;
  }

  await platformController.setOnShowFileSelector(
    (FileSelectorParams params) async {
      final picker = ImagePicker();

      // If the page specifically requests camera capture, open camera directly.
      final captureRequested = params.isCaptureEnabled;

      final XFile? file = await picker.pickImage(
        source: captureRequested
            ? ImageSource.camera
            : ImageSource.gallery,
        imageQuality: 92,
      );

      if (file == null) {
        return <String>[];
      }

      return <String>[Uri.file(file.path).toString()];
    },
  );
}
