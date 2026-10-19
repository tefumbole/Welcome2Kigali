@extends('beyond.layout')
@section('title', __('site.membership.selfie_heading'))
@section('hide_footer', '1')
@section('hide_nav', '1')
@section('body_class', 'bg-black')

@section('content')
<section class="min-h-[100dvh] bg-black text-white px-4 pt-8 pb-36">
    <div class="max-w-md mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold">Welcome 2 Kigali</p>
        <h1 class="font-serif text-3xl text-brand-gold mt-2">{{ __('site.membership.selfie_heading') }}</h1>
        <p class="text-white/70 text-sm mt-2">{{ __('site.membership.selfie_live_hint') }}</p>
        <div class="relative mt-6 rounded-xl overflow-hidden bg-neutral-900 min-h-[280px]">
            <video id="face-video" class="w-full max-h-[60vh] object-cover bg-black" style="transform: scaleX(-1);" autoplay muted playsinline webkit-playsinline></video>
            <p id="face-status" class="absolute inset-x-0 bottom-0 m-0 bg-black/70 text-brand-gold text-xl font-bold text-center px-3 py-3">{{ __('site.membership.selfie_turn_center') }}</p>
        </div>
    </div>
</section>
<div class="fixed bottom-0 inset-x-0 z-[60] bg-black/95 border-t border-brand-gold/30 p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
    <div class="max-w-md mx-auto">
        <button type="button" id="face-start" class="w-full bg-brand-gold text-black font-bold py-4 rounded-full text-lg">{{ __('site.membership.take_selfie') }}</button>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ url('public/js/w2k-face-live.js') }}"></script>
<script>
(function () {
    var video = document.getElementById('face-video');
    var statusEl = document.getElementById('face-status');
    var startBtn = document.getElementById('face-start');
    var stream = null;
    var cancelled = false;

    function setStatus(t) { statusEl.textContent = t || ''; }

    function stop() {
        cancelled = true;
        if (stream) {
            stream.getTracks().forEach(function (t) { t.stop(); });
            stream = null;
        }
        if (video) video.srcObject = null;
    }

    startBtn.addEventListener('click', function () {
        if (!navigator.mediaDevices || !window.w2kFaceLive) {
            setStatus(@json(__('site.membership.selfie_live_fail')));
            return;
        }
        cancelled = false;
        startBtn.disabled = true;
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false }).then(function (s) {
            stream = s;
            video.srcObject = s;
            video.setAttribute('playsinline', 'true');
            return video.play();
        }).then(function () {
            return window.w2kFaceLive.start(video, setStatus, function () { return cancelled; });
        }).then(function (result) {
            stop();
            var body = new URLSearchParams();
            body.set('_token', @json(csrf_token()));
            body.set('image', result.image || '');
            return fetch(@json(url('/membership/face-scan/'.$token)), {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: body
            });
        }).then(function (res) {
            if (!res || !res.ok) throw new Error('save');
            setStatus(@json(__('site.membership.selfie_live_done')));
        }).catch(function (err) {
            if (cancelled || (err && err.message === 'stopped')) return;
            setStatus(@json(__('site.membership.selfie_live_fail')));
            startBtn.disabled = false;
            stop();
        });
    });
})();
</script>
@endpush
