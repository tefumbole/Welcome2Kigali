@extends('beyond.layout')
@section('title', __('site.membership.id_scan_title'))
@section('hide_footer', '1')
@section('body_class', 'bg-black')

@section('content')
<section class="min-h-[80vh] bg-black text-white px-4 py-10">
    <div class="max-w-md mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold">Welcome 2 Kigali</p>
        <h1 class="font-serif text-3xl text-brand-gold mt-2">
            @if($idType === 'passport')
                {{ __('site.membership.id_scan_title_passport') }}
            @elseif($idType === 'national_id')
                {{ __('site.membership.id_scan_title_id') }}
            @else
                {{ __('site.membership.id_scan_title') }}
            @endif
        </h1>
        <p class="text-white/70 text-sm mt-2">{{ __('site.membership.id_scan_phone_hint') }}</p>

        <div class="relative mt-6 rounded-xl overflow-hidden bg-neutral-900">
            <video id="id-live-video" class="w-full max-h-80 object-cover bg-black" autoplay muted playsinline></video>
            <div class="pointer-events-none absolute inset-x-8 bottom-10 h-16 border-2 border-brand-gold/80 rounded-md"></div>
        </div>
        <p class="text-white/70 text-sm mt-3">{{ __('site.membership.id_live_overlay') }}</p>
        <p id="id-scan-status" class="mt-3 text-sm text-brand-gold"></p>
        <p id="id-scan-name" class="hidden mt-4 text-lg font-semibold text-brand-gold"></p>
    </div>
</section>
@endsection

@push('scripts')
<script src="{{ url('public/js/w2k-id-read.js') }}"></script>
<script>
(function () {
    var statusEl = document.getElementById('id-scan-status');
    var nameEl = document.getElementById('id-scan-name');
    var video = document.getElementById('id-live-video');
    var stream = null;
    var cancelled = false;

    function setStatus(t) { statusEl.textContent = t || ''; }

    function sendResult(result) {
        var body = new URLSearchParams();
        body.set('_token', @json(csrf_token()));
        body.set('full_name', result.full_name || '');
        body.set('id_number', result.id_number || '');
        body.set('id_type', result.id_type || @json($idType));
        body.set('expires_on', result.expires_on || '');
        body.set('nationality', result.nationality || '');
        return fetch(@json(url('/membership/id-scan/'.$token)), {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        });
    }

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.w2kIdRead) {
        setStatus(@json(__('site.membership.id_camera_needed')));
        return;
    }

    setStatus(@json(__('site.membership.id_reading')));
    navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false }).then(function (s) {
        stream = s;
        video.srcObject = s;
        return video.play();
    }).then(function () {
        return window.w2kIdRead.scanLive(video, setStatus, function () { return cancelled; });
    }).then(function (result) {
        var bits = [];
        if (result.full_name) bits.push(result.full_name);
        if (result.id_number) bits.push(result.id_number);
        if (result.nationality) bits.push(result.nationality);
        if (result.expires_on) bits.push(result.expires_on);
        if (bits.length) {
            nameEl.textContent = bits.join(' · ');
            nameEl.classList.remove('hidden');
        }
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        video.srcObject = null;
        return sendResult(result).then(function (res) {
            if (!res.ok) throw new Error('save');
            setStatus(@json(__('site.membership.id_scan_done')));
        });
    }).catch(function () {
        setStatus(@json(__('site.membership.id_read_fail')));
    });
})();
</script>
@endpush
