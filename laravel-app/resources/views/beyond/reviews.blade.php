@extends('beyond.layout')
@section('title', __('site.reviews.title'))
@section('meta_description', __('site.reviews.meta'))
@section('body_class', 'bg-[#0A0A0A]')

@push('head')
<style>
    .review-score input { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .review-score span {
        display: inline-flex; align-items: center; justify-content: center;
        width: 2.75rem; height: 2.75rem; border-radius: 999px;
        border: 1px solid rgba(197,160,89,.55); color: #C5A059; font-weight: 700;
    }
    .review-score input:checked + span,
    .review-score input:focus + span { background: #C5A059; color: #0A0A0A; }
    details.add-review > summary::-webkit-details-marker,
    details > summary::-webkit-details-marker { display: none; }
</style>
@endpush

@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0">Welcome 2 Kigali</p>
        <h1 class="font-serif text-3xl text-white mt-2">{{ \App\Support\SiteContent::text('reviews.heading', __('site.reviews.heading')) }}</h1>
        <div class="mt-6">
            @include('beyond.partials.review-summary', ['tone' => 'dark'])
        </div>

        @if ($saved)
            <div class="mt-8 rounded-2xl border border-brand-gold/40 bg-white/5 p-6 text-center">
                <h2 class="text-xl font-bold text-brand-gold m-0">{{ \App\Support\SiteContent::text('reviews.thanks_title', __('site.reviews.thanks_title')) }}</h2>
                <p class="text-white/80 mt-2 mb-0">{{ \App\Support\SiteContent::text('reviews.thanks_body', __('site.reviews.thanks_body')) }}</p>
                <a href="{{ url('/') }}" class="inline-block mt-5 text-sm font-semibold text-brand-gold underline">{{ __('site.register.return_home') }}</a>
            </div>
        @else
            @if (session('success'))
                <div class="mt-6 bg-green-950/40 border border-green-400/40 text-green-200 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
            @endif
            @if ($errors->any())
                <div class="mt-6 bg-red-950/40 border border-red-400/40 text-red-200 rounded-xl px-4 py-3 text-sm">
                    <ul class="list-disc pl-5 mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if (!empty($verifyStep))
                <div class="mt-8 rounded-2xl border border-brand-gold/40 bg-white/5 p-6">
                    <h2 class="text-lg font-bold text-brand-gold m-0">{{ __('site.reviews.verify_title') }}</h2>
                    <p class="text-sm text-white/70 mt-2">{{ __('site.reviews.verify_body', ['phone' => $maskedPhone]) }}</p>
                    <form method="POST" action="{{ route('beyond.reviews.verify') }}" class="mt-4 space-y-3">
                        @csrf
                        <input type="text" name="otp" maxlength="6" required inputmode="numeric" autocomplete="one-time-code"
                               class="w-full rounded-md bg-black border border-white/15 px-3 py-3 text-center text-2xl tracking-widest font-bold text-white"
                               placeholder="••••••">
                        <button type="submit" class="w-full bg-brand-gold text-black font-bold py-3 rounded-md">{{ __('site.reviews.verify_submit') }}</button>
                    </form>
                    <form method="POST" action="{{ route('beyond.reviews.resend') }}" class="mt-3 text-center">
                        @csrf
                        <button type="submit" class="text-sm text-brand-gold underline">{{ __('site.reviews.otp_resend') }}</button>
                    </form>
                </div>
            @endif

            <details class="mt-8" @if($errors->any() && empty($verifyStep)) open @endif>
                <summary class="list-none cursor-pointer inline-flex items-center justify-center bg-brand-gold text-black font-bold px-6 py-3 rounded-full">{{ __('site.reviews.add') }}</summary>
            <form method="POST" action="{{ route('beyond.reviews.store') }}" id="guest-review-form" class="mt-6 space-y-5">
                @csrf
                <input type="hidden" name="reference" value="{{ old('reference', $reference) }}">
                <input type="hidden" name="signature" id="review-signature" value="{{ old('signature') }}">

                @foreach ([
                    'cleanliness' => __('site.reviews.cleanliness'),
                    'on_time' => __('site.reviews.on_time'),
                    'service' => __('site.reviews.service'),
                    'food_quality' => __('site.reviews.food'),
                ] as $field => $label)
                    <fieldset class="rounded-xl border border-white/10 bg-white/5 p-4">
                        <legend class="px-1 text-sm font-semibold text-brand-gold">{{ $label }}</legend>
                        <div class="flex flex-wrap gap-2 mt-2">
                            @for ($score = 1; $score <= 5; $score++)
                                <label class="review-score cursor-pointer">
                                    <input type="radio" name="{{ $field }}" value="{{ $score }}" @if((int) old($field) === $score) checked @endif required>
                                    <span>{{ $score }}</span>
                                </label>
                            @endfor
                        </div>
                        <p class="text-[11px] text-white/45 mt-2 mb-0">1 {{ __('site.reviews.low') }} · 5 {{ __('site.reviews.high') }}</p>
                    </fieldset>
                @endforeach

                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.reviews.name') }}</label>
                    <input name="guest_name" value="{{ old('guest_name') }}" maxlength="80" class="w-full mt-1 rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                </div>

                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.reviews.country') }}</label>
                    <select name="country" required class="w-full mt-1 rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                        <option value="">{{ __('site.reviews.select_country') }}</option>
                        <optgroup label="{{ __('site.reviews.priority_countries') }}">
                            @foreach($priorityCountries as $country)
                                <option value="{{ $country }}" @if(old('country') === $country) selected @endif>{{ $country }}</option>
                            @endforeach
                        </optgroup>
                        <optgroup label="{{ __('site.reviews.other_countries') }}">
                            @foreach($otherCountries as $country)
                                <option value="{{ $country }}" @if(old('country') === $country) selected @endif>{{ $country }}</option>
                            @endforeach
                        </optgroup>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.reviews.phone') }}</label>
                    <div class="mt-1 grid grid-cols-1 sm:grid-cols-[10rem_1fr] gap-2">
                        <select name="country_code" required class="rounded-md bg-black border border-white/15 px-3 py-2 text-white">
                            @foreach($countryCodes as $code => $label)
                                <option value="{{ $code }}" @if(old('country_code', '+250') === $code) selected @endif>{{ $label }}</option>
                            @endforeach
                        </select>
                        <input type="tel" name="phone" value="{{ old('phone') }}" required pattern="[0-9]{6,15}" autocomplete="tel-national"
                               class="rounded-md bg-black border border-white/15 px-3 py-2 text-white" placeholder="78…">
                    </div>
                    <p class="text-[11px] text-white/45 mt-2 mb-0">{{ __('site.reviews.phone_hint') }}</p>
                </div>

                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.reviews.comment') }}</label>
                    <textarea name="comment" rows="3" maxlength="1000" class="w-full mt-1 rounded-md bg-black border border-white/15 px-3 py-2 text-white">{{ old('comment') }}</textarea>
                </div>

                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.reviews.signature') }}</label>
                    <button type="button" id="review-signature-open" class="mt-2 inline-flex items-center justify-center bg-white/10 border border-brand-gold/50 text-brand-gold font-semibold px-5 py-3 rounded-md">
                        {{ __('site.reviews.draw_signature') }}
                    </button>
                    <p id="review-signature-status" class="text-sm text-green-400 mt-2 mb-0 hidden">{{ __('site.reviews.signature_added') }}</p>
                    <img id="review-signature-preview" alt="" class="hidden max-h-20 mt-3 rounded bg-white border border-white/10">
                </div>

                <button type="submit" class="w-full bg-brand-gold text-black font-bold py-3 rounded-md">{{ __('site.reviews.submit') }}</button>
            </form>
            </details>
        @endif
    </div>
</div>

<div id="review-signature-modal" class="hidden fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/80">
    <div class="bg-[#121212] border border-brand-gold/40 rounded-xl max-w-lg w-full p-5 text-white">
        <h3 class="font-bold text-lg mb-2 text-brand-gold">{{ __('site.reviews.signature') }}</h3>
        <p class="text-sm text-white/60 mb-3">{{ __('site.reviews.signature_hint') }}</p>
        <canvas id="review-signature-pad" class="w-full rounded-md bg-white border border-brand-gold/40" style="height:180px;touch-action:none;"></canvas>
        <div class="mt-3 flex flex-wrap gap-2 justify-end">
            <button type="button" id="review-signature-clear" class="text-sm text-white/70 underline px-3 py-2">{{ __('site.reviews.clear_signature') }}</button>
            <button type="button" id="review-signature-close" class="px-4 py-2 text-sm text-white/80">{{ __('site.reviews.close') }}</button>
            <button type="button" id="review-signature-save" class="bg-brand-gold text-black font-semibold px-4 py-2 rounded-md text-sm">{{ __('site.reviews.save_signature') }}</button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
(function () {
    var canvas = document.getElementById('review-signature-pad');
    var modal = document.getElementById('review-signature-modal');
    var openBtn = document.getElementById('review-signature-open');
    var closeBtn = document.getElementById('review-signature-close');
    var clearBtn = document.getElementById('review-signature-clear');
    var saveBtn = document.getElementById('review-signature-save');
    var input = document.getElementById('review-signature');
    var preview = document.getElementById('review-signature-preview');
    var status = document.getElementById('review-signature-status');
    var form = document.getElementById('guest-review-form');
    if (!canvas || !modal || typeof SignaturePad === 'undefined') return;

    var pad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)', penColor: 'rgb(10,10,10)' });
    function resize() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var data = pad.isEmpty() ? null : pad.toData();
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = 180 * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
        if (data) pad.fromData(data);
    }

    function showSigned(dataUrl) {
        input.value = dataUrl || '';
        if (dataUrl) {
            preview.src = dataUrl;
            preview.classList.remove('hidden');
            status.classList.remove('hidden');
            openBtn.textContent = @json(__('site.reviews.change_signature'));
        } else {
            preview.classList.add('hidden');
            preview.removeAttribute('src');
            status.classList.add('hidden');
            openBtn.textContent = @json(__('site.reviews.draw_signature'));
        }
    }

    function openModal() {
        modal.classList.remove('hidden');
        setTimeout(resize, 30);
    }
    function closeModal() {
        modal.classList.add('hidden');
    }

    if (openBtn) openBtn.addEventListener('click', openModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (clearBtn) clearBtn.addEventListener('click', function () { pad.clear(); });
    if (saveBtn) saveBtn.addEventListener('click', function () {
        if (pad.isEmpty()) {
            alert(@json(__('site.reviews.signature_required')));
            return;
        }
        showSigned(pad.toDataURL('image/png'));
        closeModal();
    });
    modal.addEventListener('click', function (e) {
        if (e.target === modal) closeModal();
    });
    if (form) form.addEventListener('submit', function (e) {
        if (!input.value) {
            e.preventDefault();
            alert(@json(__('site.reviews.signature_required')));
            openModal();
        }
    });
    if (input.value) showSigned(input.value);
})();
</script>
@endpush
