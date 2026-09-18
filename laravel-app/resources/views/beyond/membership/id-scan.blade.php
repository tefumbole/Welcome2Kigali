@extends('beyond.layout')
@section('title', __('site.membership.id_scan_title'))
@section('hide_footer', '1')
@section('hide_nav', '1')
@section('body_class', 'bg-black')

@section('content')
<section class="min-h-[100dvh] bg-black text-white px-4 pt-8 pb-36">
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

        <div class="relative mt-6 rounded-xl overflow-hidden bg-neutral-900 min-h-[220px]">
            <video id="id-live-video" class="w-full max-h-[50vh] object-cover bg-black" autoplay muted playsinline webkit-playsinline></video>
            <div class="pointer-events-none absolute inset-x-8 bottom-10 h-16 border-2 border-brand-gold/80 rounded-md"></div>
        </div>
        <p class="text-white/70 text-sm mt-3">{{ __('site.membership.id_live_overlay') }}</p>
        <p id="id-scan-status" class="mt-3 text-sm text-brand-gold">{{ __('site.membership.id_camera_needed') }}</p>
        <p id="id-scan-name" class="hidden mt-4 text-lg font-semibold text-brand-gold"></p>
    </div>
</section>

<div class="fixed bottom-0 inset-x-0 z-[60] bg-black/95 border-t border-brand-gold/30 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
    <div class="max-w-md mx-auto space-y-3">
        <button type="button" id="id-live-start-btn"
                class="w-full bg-brand-gold hover:bg-[#b08d45] text-black font-bold py-4 rounded-full text-lg">
            {{ __('site.membership.id_action_scan') }}
        </button>
        <button type="button" id="id-live-stop-btn"
                class="hidden w-full border border-white/30 text-white font-semibold py-3 rounded-full">
            {{ __('site.membership.id_stop_scan') }}
        </button>
        <label class="block w-full text-center text-sm font-semibold text-brand-gold py-2 cursor-pointer">
            {{ __('site.membership.id_action_upload') }}
            <input id="id-phone-file" type="file" accept="image/*" capture="environment" class="sr-only">
        </label>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ url('public/js/w2k-id-read.js') }}"></script>
<script>
(function () {
    var statusEl = document.getElementById('id-scan-status');
    var nameEl = document.getElementById('id-scan-name');
    var video = document.getElementById('id-live-video');
    var startBtn = document.getElementById('id-live-start-btn');
    var stopBtn = document.getElementById('id-live-stop-btn');
    var fileInput = document.getElementById('id-phone-file');
    var stream = null;
    var cancelled = false;
    var busy = false;

    function setStatus(t) { statusEl.textContent = t || ''; }

    function showStop(on) {
        startBtn.classList.toggle('hidden', !!on);
        stopBtn.classList.toggle('hidden', !on);
    }

    function stopCamera() {
        cancelled = true;
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
        showStop(false);
        busy = false;
    }

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

    function finish(result) {
        var bits = [];
        if (result.full_name) bits.push(result.full_name);
        if (result.id_number) bits.push(result.id_number);
        if (result.nationality) bits.push(result.nationality);
        if (result.expires_on) bits.push(result.expires_on);
        if (bits.length) {
            nameEl.textContent = bits.join(' · ');
            nameEl.classList.remove('hidden');
        }
        stopCamera();
        return sendResult(result).then(function (res) {
            if (!res.ok) throw new Error('save');
            setStatus(@json(__('site.membership.id_scan_done')));
        });
    }

    function openCamera() {
        if (busy) return;
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.w2kIdRead) {
            setStatus(@json(__('site.membership.id_camera_needed')));
            return;
        }
        busy = true;
        cancelled = false;
        showStop(true);
        setStatus(@json(__('site.membership.id_reading')));
        var tryCam = function (constraints) {
            return navigator.mediaDevices.getUserMedia(constraints);
        };
        tryCam({ video: { facingMode: { ideal: 'environment' } }, audio: false })
            .catch(function () { return tryCam({ video: true, audio: false }); })
            .then(function (s) {
                stream = s;
                video.srcObject = s;
                video.setAttribute('playsinline', 'true');
                video.setAttribute('webkit-playsinline', 'true');
                var play = video.play();
                return play && play.catch ? play.catch(function () {}) : play;
            }).then(function () {
                return window.w2kIdRead.scanLive(video, setStatus, function () { return cancelled; });
            }).then(function (result) {
                return finish(result);
            }).catch(function () {
                if (cancelled) return;
                setStatus(@json(__('site.membership.id_read_fail')));
                stopCamera();
            });
    }

    startBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openCamera();
    });
    stopBtn.addEventListener('click', function (e) {
        e.preventDefault();
        stopCamera();
        setStatus(@json(__('site.membership.id_camera_needed')));
    });

    if (fileInput && window.w2kIdRead) {
        fileInput.addEventListener('change', function () {
            var file = fileInput.files && fileInput.files[0];
            if (!file) return;
            stopCamera();
            busy = true;
            setStatus(@json(__('site.membership.id_reading')));
            window.w2kIdRead.readDocument(file, setStatus).then(function (result) {
                return finish(result);
            }).catch(function () {
                setStatus(@json(__('site.membership.id_read_fail')));
                busy = false;
            });
        });
    }
})();
</script>
@endpush
