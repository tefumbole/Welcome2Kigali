@extends('beyond.layout')
@section('title', __('site.membership.update_title'))
@section('body_class', 'bg-[#0A0A0A]')

@section('content')
<div class="bg-[#0A0A0A] text-white min-h-[70vh] py-10 px-4">
    <div class="max-w-xl mx-auto">
        <p class="text-[11px] tracking-[0.22em] uppercase text-brand-gold m-0">Welcome 2 Kigali</p>
        <h1 class="font-serif text-3xl text-white mt-2">{{ __('site.membership.update_title') }}</h1>
        <p class="text-white/70 text-sm mt-2">{{ __('site.membership.update_intro', ['reference' => $application->reference]) }}</p>

        @if ($saved)
            <div class="mt-8 rounded-2xl border border-brand-gold/40 bg-white/5 p-6 text-center">
                <p class="text-white/90 m-0">{{ __('site.membership.update_saved') }}</p>
                <a href="{{ url('/') }}" class="inline-block mt-5 text-sm font-semibold text-brand-gold underline">{{ __('site.register.return_home') }}</a>
            </div>
        @else
            @if ($application->admin_note)
                <div class="mt-6 rounded-xl border border-amber-400/40 bg-amber-950/30 p-4">
                    <p class="text-xs font-bold tracking-[0.16em] uppercase text-amber-300 m-0">{{ __('site.membership.update_reason') }}</p>
                    <p class="text-sm text-white/90 mt-2 mb-0 whitespace-pre-line">{{ $application->admin_note }}</p>
                </div>
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

            <div class="mt-6 rounded-xl border border-brand-gold/30 bg-white/[0.03] p-4">
                <p class="font-bold text-brand-gold m-0">{{ __('site.membership.update_scan') }}</p>
                <p class="text-sm text-white/70 mt-1">{{ __('site.membership.update_scan_help') }}</p>
                <div class="flex flex-wrap items-center gap-4 mt-3">
                    <a href="{{ $scanUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 bg-brand-gold text-black font-bold px-4 py-2 rounded-md">{{ __('site.membership.update_scan') }}</a>
                    <img src="{{ $scanQr }}" alt="" class="w-32 h-32 bg-white rounded-lg p-1 hidden md:block">
                </div>
                <p id="update-scan-status" class="hidden mt-3 text-sm font-bold text-green-400"></p>
            </div>

            <form method="POST" action="{{ route('membership.update.store', [$application->reference, $token]) }}" class="mt-6 space-y-4" id="membership-update-form">
                @csrf
                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.membership.whatsapp') }}</label>
                    <input required name="phone" value="{{ old('phone', $application->phone) }}" type="tel" class="w-full mt-1 rounded-md border border-white/20 bg-white text-black px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.membership.email_optional') }}</label>
                    <input name="email" value="{{ old('email', \Illuminate\Support\Str::endsWith($application->email, '.local') ? '' : $application->email) }}" type="email" class="w-full mt-1 rounded-md border border-white/20 bg-white text-black px-3 py-2">
                </div>
                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.membership.id_fallback_photo') }}</label>
                    <input id="update-photo-input" type="file" accept="image/*" capture="environment" class="block mt-2 text-sm">
                    <input type="hidden" name="id_photo_data" id="update-photo-data">
                    <img id="update-photo-preview" alt="" class="hidden mt-3 max-h-48 rounded-md border border-white/20">
                    <p class="text-xs text-white/60 mt-2 mb-0">{{ __('site.membership.id_fallback_note') }}</p>
                </div>
                <div>
                    <label class="text-sm font-semibold text-white/80">{{ __('site.membership.update_comment') }}</label>
                    <textarea name="comment" rows="3" class="w-full mt-1 rounded-md border border-white/20 bg-white text-black px-3 py-2">{{ old('comment') }}</textarea>
                </div>
                <button type="submit" class="w-full bg-brand-gold text-black font-bold py-3 rounded-md">{{ __('site.membership.update_submit') }}</button>
            </form>
        @endif
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var input = document.getElementById('update-photo-input');
    if (input) {
        input.addEventListener('change', function () {
            var file = input.files[0];
            if (!file || file.type.indexOf('image/') !== 0) return;
            var reader = new FileReader();
            reader.onload = function () {
                var img = new Image();
                img.onload = function () {
                    var scale = Math.min(1, 1600 / Math.max(img.width, img.height));
                    var c = document.createElement('canvas');
                    c.width = Math.round(img.width * scale);
                    c.height = Math.round(img.height * scale);
                    c.getContext('2d').drawImage(img, 0, 0, c.width, c.height);
                    var url = c.toDataURL('image/jpeg', 0.8);
                    document.getElementById('update-photo-data').value = url;
                    var prev = document.getElementById('update-photo-preview');
                    prev.src = url;
                    prev.classList.remove('hidden');
                };
                img.src = reader.result;
            };
            reader.readAsDataURL(file);
        });
    }
    var statusEl = document.getElementById('update-scan-status');
    if (!statusEl) return;
    var url = @json(url('/membership/id-scan/'.$scanToken.'/status'));
    (function tick() {
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (data && data.status === 'ready') {
                    var label = data.id_type === 'national_id' ? @json(__('site.membership.id_verified_id')) : @json(__('site.membership.id_verified_passport'));
                    statusEl.textContent = '✓ ' + label + ' · ' + (data.full_name || '') + ' · ' + @json(__('site.membership.update_scan_done'));
                    statusEl.classList.remove('hidden');
                    return;
                }
                setTimeout(tick, 3000);
            })
            .catch(function () { setTimeout(tick, 4000); });
    })();
})();
</script>
@endpush
