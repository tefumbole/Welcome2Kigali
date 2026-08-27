@extends('beyond.layout')

@section('title', __('site.membership.title'))
@section('meta_description', __('site.membership.sub'))

@section('content')
<div class="min-h-screen bg-gray-50 flex flex-col">
    <div class="relative h-[300px] w-full bg-brand-blue overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center opacity-40 mix-blend-overlay" style="background-image:url('https://images.unsplash.com/photo-1693045181224-9fc2f954f054');"></div>
        <div class="absolute inset-0 bg-gradient-to-t from-brand-blue via-transparent to-transparent"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex flex-col justify-center text-white z-10">
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-2">{{ __('site.membership.heading') }}</h1>
            <p class="text-lg md:text-xl text-blue-100 max-w-2xl">{{ __('site.membership.sub') }}</p>
        </div>
    </div>

    <main class="flex-1 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-20 z-20 pb-16 w-full">
        <div class="bg-white rounded-xl shadow-xl border p-6 md:p-8">
            @if ($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm">
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($promo)
                <div class="mb-6 rounded-xl border-2 border-brand-gold bg-amber-50 p-4">
                    <p class="font-bold text-brand-blue text-lg m-0">{{ $promo->name }}</p>
                    <p class="text-sm text-gray-700 mt-1 mb-0">{{ $promo->description ?: __('site.membership.promo_blurb', ['days' => $promo->free_days]) }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('membership.apply.store') }}" enctype="multipart/form-data" id="membership-apply-form" class="space-y-6">
                @csrf
                <div>
                    <h2 class="text-xl font-bold text-brand-blue">{{ __('site.membership.choose_plan') }}</h2>
                    <p class="text-sm text-gray-500">{{ __('site.membership.discount_note', ['pct' => $discount]) }}</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-3">
                        @foreach($plans as $plan)
                            <label class="flex items-start gap-3 p-4 rounded-xl border-2 cursor-pointer border-gray-100 hover:border-brand-gold">
                                <input type="radio" name="plan_id" value="{{ $plan->id }}" @if($loop->first || (string)old('plan_id')===(string)$plan->id) checked @endif class="mt-1">
                                <div>
                                    <p class="font-bold text-brand-blue m-0">{{ $plan->name }}</p>
                                    <p class="text-sm text-gray-600 m-0">{{ $plan->duration_months }} {{ __('site.membership.months') }} · {{ number_format($plan->fee) }} FRW</p>
                                    @if($promo)<p class="text-xs text-brand-gold font-semibold m-0">{{ __('site.membership.promo_now') }}</p>@endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700">{{ __('site.register.full_name') }}</label>
                        <input required name="full_name" value="{{ old('full_name') }}" type="text" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700">{{ __('site.register.email') }}</label>
                        <input required name="email" value="{{ old('email') }}" type="email" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700">{{ __('site.register.phone') }}</label>
                        <input required name="phone" value="{{ old('phone') }}" type="tel" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2">
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700">{{ __('site.register.company') }}</label>
                        <input name="company_name" value="{{ old('company_name') }}" type="text" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2">
                    </div>
                </div>

                <div>
                    <h2 class="text-xl font-bold text-brand-blue">{{ __('site.membership.id_heading') }}</h2>
                    <div class="flex gap-4 mt-2">
                        @foreach($idTypes as $type)
                            <label class="font-medium text-sm">
                                <input type="radio" name="id_type" value="{{ $type }}" @if(old('id_type', $idTypes[0] ?? 'national_id')===$type) checked @endif>
                                {{ $type === 'passport' ? __('site.membership.passport') : __('site.membership.national_id') }}
                            </label>
                        @endforeach
                    </div>
                    <input required type="file" name="id_document" accept=".jpg,.jpeg,.png,.pdf" class="mt-2 w-full text-sm">
                </div>

                @if($agreement)
                    <div>
                        <h2 class="text-xl font-bold text-brand-blue">{{ $agreement->title }} <span class="text-sm font-normal text-gray-500">v{{ $agreement->version }}</span></h2>
                        <div class="max-h-48 overflow-y-auto border rounded-md p-3 text-sm text-gray-700 whitespace-pre-wrap bg-gray-50">{{ $agreement->body }}</div>
                        <label class="flex items-start gap-2 mt-3 text-sm">
                            <input type="checkbox" name="accept_agreement" value="1" required class="mt-1">
                            <span>{{ __('site.membership.accept') }}</span>
                        </label>
                    </div>
                @endif

                <div>
                    <h2 class="text-xl font-bold text-brand-blue">{{ __('site.membership.sign') }}</h2>
                    <canvas id="membership-signature-pad" class="w-full mt-1 border-2 border-dashed border-brand-gold rounded-md bg-white" style="height:160px;touch-action:none;"></canvas>
                    <input type="hidden" name="signature" id="membership-signature">
                    <button type="button" id="membership-signature-clear" class="mt-2 text-sm text-gray-600 underline">{{ __('site.membership.clear_sign') }}</button>
                </div>

                <p class="text-sm text-gray-600">{{ __('site.membership.whatsapp_note') }}</p>
                <button type="submit" class="w-full bg-brand-blue hover:bg-brand-dark text-white font-bold py-3 rounded-md">{{ __('site.membership.submit') }}</button>
            </form>
        </div>
    </main>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
(function () {
    var canvas = document.getElementById('membership-signature-pad');
    if (!canvas || typeof SignaturePad === 'undefined') return;
    var pad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
    function resize() {
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var data = pad.toData();
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
        if (data.length) pad.fromData(data);
    }
    window.addEventListener('resize', resize);
    resize();
    document.getElementById('membership-signature-clear').addEventListener('click', function () { pad.clear(); });
    document.getElementById('membership-apply-form').addEventListener('submit', function (e) {
        if (pad.isEmpty()) {
            e.preventDefault();
            alert(@json(__('site.membership.sign_required')));
            return;
        }
        document.getElementById('membership-signature').value = pad.toDataURL('image/png');
    });
})();
</script>
@endpush
