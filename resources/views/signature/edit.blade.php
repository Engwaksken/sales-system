@extends('layouts.app')
@section('title', 'My signature')
@section('content')
<div class="mx-auto max-w-2xl">
    <h1 class="text-xl font-semibold text-slate-800">My signature</h1>
    <p class="mb-5 text-sm text-slate-500">Your signature is added automatically to the invoices and receipts of sales you make, on the website and in the mobile app.</p>

    @if(session('status'))<div data-flash class="mb-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="mb-4 rounded bg-red-50 p-3 text-red-700">{{ $errors->first() }}</div>@endif

    <div class="mb-5 rounded-lg bg-white p-5 shadow">
        <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Current signature</div>
        @if($signature)
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="rounded border border-dashed border-slate-300 bg-slate-50 px-6 py-3">
                    <img src="{{ $signature }}" alt="Your signature" class="max-h-20 max-w-[260px]">
                </div>
                <form method="POST" action="{{ route('signature.destroy') }}" onsubmit="return confirm('Remove your signature from future invoices and receipts?')">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm font-semibold text-red-600 hover:text-red-800"><i class="fas fa-trash-can mr-1"></i>Remove</button>
                </form>
            </div>
        @else
            <p class="text-sm text-slate-500">You haven't added a signature yet. Your documents show your name with a blank signature line until you do.</p>
        @endif
    </div>

    <form method="POST" action="{{ route('signature.update') }}" enctype="multipart/form-data" class="rounded-lg bg-white shadow" id="signature-form">
        @csrf
        @method('PUT')
        <input type="hidden" name="signature_data" id="signature-data">

        <div class="border-b px-5 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Draw a new signature</div>
        <div class="p-5">
            <div class="relative rounded-lg border border-slate-300 bg-white">
                <canvas id="signature-pad" class="block h-48 w-full touch-none cursor-crosshair rounded-lg" aria-label="Signature drawing area"></canvas>
                <div id="signature-hint" class="pointer-events-none absolute inset-0 flex items-center justify-center text-sm text-slate-400">Sign here with your mouse, finger or stylus</div>
                <div class="pointer-events-none absolute bottom-10 left-6 right-6 border-b border-slate-300"></div>
            </div>
            <div class="mt-3 flex justify-between">
                <button type="button" id="signature-clear" class="text-sm font-semibold text-slate-600 hover:text-slate-900"><i class="fas fa-eraser mr-1"></i>Clear</button>
                <span class="text-xs text-slate-400">Saved as a transparent image</span>
            </div>
        </div>

        <div class="border-y px-5 pb-1 pt-4 text-xs font-semibold uppercase tracking-wider text-slate-500">Or upload an image</div>
        <div class="p-5">
            <input type="file" name="signature" id="signature-file" accept="image/png,image/jpeg"
                   class="block w-full text-sm text-slate-600 file:mr-4 file:rounded file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
            <p class="mt-2 text-xs text-slate-400">PNG or JPG under 500 KB. A signature on white or transparent background looks best.</p>
        </div>

        <div class="flex justify-end p-4">
            <button class="rounded px-5 py-2 text-sm font-semibold text-white" style="background: var(--theme-primary)">Save signature</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
(function () {
    const canvas = document.getElementById('signature-pad');
    const hint = document.getElementById('signature-hint');
    const data = document.getElementById('signature-data');
    const file = document.getElementById('signature-file');
    const ctx = canvas.getContext('2d');
    let drawing = false, drawn = false, last = null;

    function resize() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const keep = drawn ? canvas.toDataURL() : null;
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
        ctx.lineWidth = 2.4; ctx.lineCap = 'round'; ctx.lineJoin = 'round'; ctx.strokeStyle = '#0f172a';
        if (keep) { const img = new Image(); img.onload = () => ctx.drawImage(img, 0, 0, canvas.offsetWidth, canvas.offsetHeight); img.src = keep; }
    }
    function point(e) { const r = canvas.getBoundingClientRect(); return { x: e.clientX - r.left, y: e.clientY - r.top }; }

    canvas.addEventListener('pointerdown', (e) => { drawing = true; last = point(e); canvas.setPointerCapture(e.pointerId); hint.hidden = true; });
    canvas.addEventListener('pointermove', (e) => {
        if (!drawing) return;
        const p = point(e);
        ctx.beginPath(); ctx.moveTo(last.x, last.y); ctx.lineTo(p.x, p.y); ctx.stroke();
        last = p; drawn = true;
    });
    ['pointerup', 'pointercancel', 'pointerleave'].forEach((t) => canvas.addEventListener(t, () => { drawing = false; }));
    document.getElementById('signature-clear').addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height); drawn = false; hint.hidden = false;
    });

    // Send only the trimmed strokes, so the signature isn't tiny on documents.
    function trimmed() {
        const w = canvas.width, h = canvas.height, px = ctx.getImageData(0, 0, w, h).data;
        let minX = w, minY = h, maxX = -1, maxY = -1;
        for (let y = 0; y < h; y++) for (let x = 0; x < w; x++) {
            if (px[(y * w + x) * 4 + 3] > 0) { if (x < minX) minX = x; if (x > maxX) maxX = x; if (y < minY) minY = y; if (y > maxY) maxY = y; }
        }
        if (maxX < 0) return null;
        const pad = 8, out = document.createElement('canvas');
        out.width = maxX - minX + pad * 2; out.height = maxY - minY + pad * 2;
        out.getContext('2d').drawImage(canvas, minX - pad, minY - pad, out.width, out.height, 0, 0, out.width, out.height);
        return out.toDataURL('image/png');
    }

    document.getElementById('signature-form').addEventListener('submit', (e) => {
        if (file.files.length) { data.value = ''; return; }
        const url = drawn ? trimmed() : null;
        if (!url) { e.preventDefault(); appAlert('Draw your signature or choose an image first.'); return; }
        data.value = url;
    });

    window.addEventListener('resize', resize);
    resize();
})();
</script>
@endpush
@endsection
