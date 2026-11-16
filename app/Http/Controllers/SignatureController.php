<?php

namespace App\Http\Controllers;

use App\Support\DocumentSignature;
use Illuminate\Http\Request;

/** The signed-in user's own signature, printed on the invoices and receipts they sell. */
class SignatureController extends Controller
{
    public function edit(Request $request)
    {
        return view('signature.edit', [
            'signature' => DocumentSignature::dataUri($request->user()),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'signature' => ['required_without:signature_data', 'nullable', 'image', 'mimes:png,jpg,jpeg', 'max:512'],
            'signature_data' => ['required_without:signature', 'nullable', 'string', 'max:700000'],
        ], [
            'signature.required_without' => 'Draw your signature or choose an image first.',
            'signature_data.required_without' => 'Draw your signature or choose an image first.',
            'signature.max' => 'The signature image must be under 500 KB.',
        ]);

        DocumentSignature::store($request->user(), $request->file('signature') ?? $request->input('signature_data'));

        return redirect()->route('signature.edit')->with('status', 'Signature saved. It will appear on your invoices and receipts.');
    }

    public function destroy(Request $request)
    {
        DocumentSignature::remove($request->user());

        return redirect()->route('signature.edit')->with('status', 'Signature removed.');
    }
}
