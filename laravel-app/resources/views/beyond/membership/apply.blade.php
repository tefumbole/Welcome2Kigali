@extends('beyond.layout')

@section('title', __('site.membership.title'))
@section('meta_description', __('site.membership.sub'))

@section('content')
@php
    $startStep = old('plan_choice') || $errors->any() ? 2 : 1;
    $oldPlan = old('plan_choice', '');
@endphp
<div class="min-h-screen bg-gray-50 flex flex-col"
     x-data="membershipApply({{ (int) $startStep }}, {{ json_encode($oldPlan) }})">
    <div class="relative h-[220px] md:h-[280px] w-full bg-black overflow-hidden">
        <div class="absolute inset-0 bg-cover bg-center opacity-40 mix-blend-overlay" style="background-image:url('https://images.unsplash.com/photo-1693045181224-9fc2f954f054');"></div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-full flex flex-col justify-center text-white z-10">
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight mb-2">{{ __('site.membership.heading') }}</h1>
            <p class="text-lg md:text-xl text-white/80 max-w-2xl">{{ __('site.membership.sub') }}</p>
        </div>
    </div>

    <main class="flex-1 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16 z-20 pb-16 w-full">
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

            <form method="POST" action="{{ route('membership.apply.store') }}" enctype="multipart/form-data" id="membership-apply-form" class="space-y-6">
                @csrf
                <input type="hidden" name="plan_choice" x-model="plan">
                <input type="hidden" name="selfie_data" id="selfie-data">
                <input type="hidden" name="signature" id="membership-signature">

                <div x-show="step === 1">
                    <h2 class="text-xl font-bold text-black">{{ __('site.membership.choose_plan') }}</h2>
                    <p class="mt-2 text-base text-gray-800 font-medium">{{ __('site.membership.discount_all', ['pct' => $discount]) }}</p>
                    <p class="text-sm text-gray-500 mt-1">{{ __('site.membership.pick_then_details') }}</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mt-5">
                        @if($promo)
                            <button type="button"
                                    @click="selectPlan('promo', {{ json_encode($promo->name) }})"
                                    class="text-left p-4 rounded-xl border-2 transition-colors"
                                    :class="plan === 'promo' ? 'border-brand-gold bg-amber-50' : 'border-gray-100 hover:border-brand-gold'">
                                <p class="text-xs font-bold tracking-[0.18em] uppercase text-brand-gold m-0">{{ __('site.membership.free_badge') }}</p>
                                <p class="font-bold text-black text-lg m-0 mt-1">{{ $promo->name }}</p>
                                <p class="text-sm text-gray-600 m-0">{{ $promo->free_days }} {{ __('site.membership.days') }} · 0 FRW</p>
                                <p class="text-xs text-gray-500 mt-2 mb-0">{{ $promo->description ?: __('site.membership.promo_blurb', ['days' => $promo->free_days]) }}</p>
                            </button>
                        @endif
                        @foreach($plans as $plan)
                            <button type="button"
                                    @click="selectPlan({{ json_encode((string) $plan->id) }}, {{ json_encode($plan->name) }})"
                                    class="text-left p-4 rounded-xl border-2 transition-colors"
                                    :class="plan === {{ json_encode((string) $plan->id) }} ? 'border-brand-gold bg-amber-50' : 'border-gray-100 hover:border-brand-gold'">
                                <p class="font-bold text-black text-lg m-0">{{ $plan->name }}</p>
                                <p class="text-sm text-gray-600 m-0">{{ $plan->duration_months }} {{ __('site.membership.months') }} · {{ number_format($plan->fee) }} FRW</p>
                                <p class="text-xs text-gray-500 mt-2 mb-0">{{ __('site.membership.discount_all', ['pct' => $discount]) }}</p>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div x-show="step === 2" x-cloak>
                    <button type="button" @click="step = 1" class="text-sm text-gray-600 underline mb-4">{{ __('site.membership.change_plan') }}</button>
                    <p class="text-sm font-semibold text-brand-gold mb-4" x-text="planLabel ? {{ json_encode(__('site.membership.selected')) }} + ' ' + planLabel : ''"></p>

                    <div>
                        <h2 class="text-xl font-bold text-black">{{ __('site.membership.id_heading') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('site.membership.id_scan_intro') }}</p>
                        <p class="text-sm font-semibold text-gray-700 mt-4 mb-2">{{ __('site.membership.id_choose_type') }}</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($idTypes as $type)
                                <label class="inline-flex items-center gap-2 font-medium text-sm border-2 rounded-lg px-4 py-2 cursor-pointer"
                                       :class="idType === {{ json_encode($type) }} ? 'border-brand-gold bg-amber-50' : 'border-gray-200'">
                                    <input type="radio" name="id_type" value="{{ $type }}" x-model="idType" class="sr-only">
                                    {{ $type === 'passport' ? __('site.membership.passport') : __('site.membership.national_id') }}
                                </label>
                            @endforeach
                        </div>

                        <div x-show="idType" x-cloak class="mt-4">
                            <p class="text-sm font-semibold text-gray-700 mb-2">{{ __('site.membership.id_choose_action') }}</p>
                            <div class="grid grid-cols-2 gap-3 max-w-md">
                                <button type="button" @click="idAction = 'upload'; if (window.__membershipStopLiveScan) window.__membershipStopLiveScan()"
                                        class="font-bold py-3 rounded-lg border-2"
                                        :class="idAction === 'upload' ? 'border-brand-gold bg-amber-50 text-black' : 'border-gray-200 text-gray-700'">
                                    {{ __('site.membership.id_action_upload') }}
                                </button>
                                <button type="button" @click="startScan()"
                                        class="font-bold py-3 rounded-lg border-2"
                                        :class="idAction === 'scan' ? 'border-brand-gold bg-amber-50 text-black' : 'border-gray-200 text-gray-700'">
                                    {{ __('site.membership.id_action_scan') }}
                                </button>
                            </div>

                            <div x-show="idAction === 'upload'" x-cloak id="id-paste-zone" tabindex="0" class="mt-4 rounded-xl border-2 border-dashed border-brand-gold/70 bg-gray-50 p-4 text-center cursor-pointer">
                                <p class="text-sm text-gray-600 m-0">{{ __('site.membership.id_paste_box') }}</p>
                                <input id="id-document-input" type="file" accept="image/*" class="mt-3 mx-auto text-sm">
                            </div>

                            <div x-show="idAction === 'scan' && isMobile" x-cloak class="mt-4 space-y-3">
                                <div class="relative rounded-xl overflow-hidden bg-black">
                                    <video id="id-live-video" class="w-full max-h-72 object-cover bg-black" autoplay muted playsinline></video>
                                    <div class="pointer-events-none absolute inset-x-8 bottom-8 h-16 border-2 border-brand-gold/80 rounded-md"></div>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <button type="button" id="id-live-start-btn" class="bg-black text-white font-bold px-4 py-2 rounded-md">{{ __('site.membership.id_start_scan') }}</button>
                                    <button type="button" id="id-live-stop-btn" class="border border-gray-300 text-gray-700 font-semibold px-4 py-2 rounded-md">{{ __('site.membership.id_stop_scan') }}</button>
                                </div>
                            </div>

                            <div x-show="idAction === 'scan' && !isMobile" x-cloak class="mt-4 flex justify-center">
                                <div class="bg-white rounded-xl border border-gray-200 p-3 shadow-sm">
                                    <img x-show="idType === 'national_id'" src="{{ $scanQrNational }}" alt="" class="w-52 h-52 object-contain">
                                    <img x-show="idType === 'passport'" src="{{ $scanQrPassport }}" alt="" class="w-52 h-52 object-contain">
                                </div>
                            </div>
                            <p id="id-scan-wait" class="hidden"></p>
                            <p id="id-scan-status" class="hidden text-sm text-brand-gold mt-3 mb-0"></p>
                            <p id="id-scan-got" class="hidden text-sm text-green-700 mt-3 mb-0"></p>

                            <div x-show="idRead" x-cloak class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="text-sm font-semibold text-gray-800 m-0">{{ __('site.membership.id_from_document') }}</p>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-3">
                                    <div class="md:col-span-2">
                                        <label class="text-sm font-semibold text-gray-700">{{ __('site.membership.full_name') }}</label>
                                        <input required name="full_name" id="full-name-field" value="{{ old('full_name') }}" type="text" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2 bg-white" autocomplete="name">
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-700">{{ __('site.membership.id_number_label') }}</label>
                                        <input required name="id_number" id="id-number-field" value="{{ old('id_number') }}" type="text" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2 bg-white" autocomplete="off">
                                    </div>
                                    <div>
                                        <label class="text-sm font-semibold text-gray-700">{{ __('site.membership.nationality') }}</label>
                                        <select required name="nationality" id="nationality-field" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2 bg-white">
                                            <option value="">{{ __('site.membership.select_country') }}</option>
                                            <optgroup label="{{ __('site.membership.priority_countries') }}">
                                                @foreach($priorityCountries as $country)
                                                    <option value="{{ $country }}" @if(old('nationality')===$country) selected @endif>{{ $country }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="{{ __('site.membership.other_countries') }}">
                                                @foreach($otherCountries as $country)
                                                    <option value="{{ $country }}" @if(old('nationality')===$country) selected @endif>{{ $country }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="text-sm font-semibold text-gray-700">{{ __('site.membership.id_expires_on') }}</label>
                                        <input name="id_expires_on" id="id-expires-field" value="{{ old('id_expires_on') }}" type="date" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2 bg-white">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div x-show="idRead" x-cloak class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="text-sm font-semibold text-gray-700">{{ __('site.membership.whatsapp') }}</label>
                            <input required name="phone" value="{{ old('phone') }}" type="tel" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2" placeholder="+250 7…" autocomplete="tel">
                        </div>
                        @unless($beyond)
                            <div>
                                <label class="text-sm font-semibold text-gray-700">{{ __('site.membership.password') }}</label>
                                <input required name="password" type="password" minlength="6" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2" autocomplete="new-password">
                            </div>
                        @endunless
                        <div class="{{ $beyond ? 'md:col-span-2' : '' }}">
                            <label class="text-sm font-semibold text-gray-700">{{ __('site.membership.email_optional') }}</label>
                            <input name="email" value="{{ old('email') }}" type="email" class="w-full mt-1 rounded-md border border-gray-200 px-3 py-2" autocomplete="email">
                        </div>
                    </div>

                    <div x-show="idRead" x-cloak class="mt-6">
                        <h2 class="text-xl font-bold text-black">{{ __('site.membership.selfie_heading') }}</h2>
                        <p class="text-sm text-gray-500">{{ __('site.membership.selfie_hint') }}</p>
                        <div class="mt-3 flex flex-wrap items-center gap-3">
                            <button type="button" id="selfie-capture-btn" class="inline-flex items-center gap-2 bg-black text-white font-semibold px-4 py-2 rounded-md">{{ __('site.membership.take_selfie') }}</button>
                            <input id="selfie-input" type="file" name="selfie" accept="image/*" capture="user" class="text-sm">
                        </div>
                        <video id="selfie-video" class="hidden mt-3 w-full max-w-sm rounded-md bg-black" autoplay playsinline></video>
                        <button type="button" id="selfie-snap-btn" class="hidden mt-2 border border-brand-gold text-brand-gold font-semibold px-4 py-2 rounded-md">{{ __('site.membership.snap') }}</button>
                        <img id="selfie-preview" alt="" class="hidden max-h-40 mt-3 rounded-md">
                    </div>

                    @if($agreement)
                        <div x-show="idRead" x-cloak class="mt-6">
                            <button type="button" @click="openAgreement()" class="w-full md:w-auto border-2 border-black text-black font-bold px-5 py-3 rounded-md hover:bg-black hover:text-white transition-colors">
                                {{ __('site.membership.view_agreement') }}
                            </button>
                            <label class="flex items-start gap-2 mt-3 text-sm">
                                <input type="checkbox" name="accept_agreement" value="1" x-model="agreementAccepted" @if(old('accept_agreement')) checked @endif required class="mt-1">
                                <span>{{ __('site.membership.accept') }}</span>
                            </label>
                        </div>
                    @endif

                    <div x-show="idRead" x-cloak class="mt-6">
                        <button type="button" @click="openSign()" class="w-full md:w-auto bg-brand-gold text-black font-bold px-5 py-3 rounded-md">
                            <span x-show="!signed">{{ __('site.membership.add_signature') }}</span>
                            <span x-show="signed" x-cloak>{{ __('site.membership.change_signature') }}</span>
                        </button>
                        <p x-show="signed" x-cloak class="text-sm text-green-700 mt-2 mb-0">{{ __('site.membership.signature_added') }}</p>
                        <img id="signature-preview" alt="" class="hidden max-h-24 mt-2 border rounded bg-white">
                    </div>

                    <div x-show="idRead" x-cloak>
                    <p class="text-sm text-gray-600 mt-6">{{ __('site.membership.whatsapp_note') }}</p>
                    <button type="submit" class="w-full bg-black hover:bg-neutral-800 text-white font-bold py-3 rounded-md">{{ __('site.membership.submit') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </main>

    @if($agreement)
    <div x-show="agreementOpen" x-cloak class="fixed inset-0 z-[80] flex items-end sm:items-center justify-center p-0 sm:p-4 bg-black/80" @keydown.escape.window="agreementOpen = false">
        <div class="bg-[#0A0A0A] rounded-t-2xl sm:rounded-2xl max-w-4xl w-full max-h-[92vh] overflow-hidden flex flex-col border border-brand-gold/25 shadow-2xl" @click.away="agreementOpen = false">
            @include('beyond.membership.partials.agreement-document', ['agreement' => $agreement])
            <div class="bg-[#111] border-t border-brand-gold/30 py-4 px-4 md:px-6 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-sm text-gray-400 text-center sm:text-left m-0">Do you accept the terms outlined in this Membership License Agreement?</p>
                <div class="flex gap-3 w-full sm:w-auto">
                    <button type="button" @click="agreementOpen = false" class="flex-1 sm:flex-none px-6 py-2.5 rounded-md border border-red-500/50 text-red-400 hover:bg-red-950/30 font-medium">I Disagree</button>
                    <button type="button" @click="agreementAccepted = true; agreementOpen = false" class="flex-1 sm:flex-none bg-brand-gold text-black hover:bg-[#b5952f] font-bold px-8 py-2.5 rounded-md shadow-lg flex items-center justify-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> I Agree
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div x-show="signOpen" x-cloak class="fixed inset-0 z-[80] flex items-center justify-center p-4 bg-black/70">
        <div class="bg-white rounded-xl max-w-lg w-full p-5">
            <h3 class="font-bold text-lg mb-3">{{ __('site.membership.sign') }}</h3>
            <canvas id="membership-signature-pad" class="w-full border-2 border-dashed border-brand-gold rounded-md bg-white" style="height:180px;touch-action:none;"></canvas>
            <div class="mt-3 flex flex-wrap gap-2 justify-end">
                <button type="button" id="membership-signature-clear" class="text-sm text-gray-600 underline px-3 py-2">{{ __('site.membership.clear_sign') }}</button>
                <button type="button" @click="signOpen = false" class="px-4 py-2 text-sm">{{ __('site.membership.close') }}</button>
                <button type="button" id="membership-signature-add" class="bg-black text-white font-semibold px-4 py-2 rounded-md text-sm">{{ __('site.membership.add_sign_btn') }}</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script src="{{ url('public/js/w2k-id-read.js') }}"></script>
<script>
function membershipApply(startStep, oldPlan) {
    return {
        step: startStep || 1,
        plan: oldPlan || '',
        planLabel: '',
        agreementOpen: false,
        agreementAccepted: {{ old('accept_agreement') ? 'true' : 'false' }},
        signOpen: false,
        signed: false,
        idType: @json(old('id_type', '')),
        idAction: @json(old('id_number') ? 'upload' : ''),
        idRead: {{ old('full_name') || old('id_number') ? 'true' : 'false' }},
        isMobile: false,
        init() {
            var self = this;
            this.isMobile = /Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent)
                || (window.matchMedia && window.matchMedia('(max-width: 767px)').matches);
            window.__membershipMarkSigned = function () {
                self.signed = true;
                self.signOpen = false;
            };
            window.__membershipSetIdType = function (t) {
                if (t) self.idType = t;
            };
            window.__membershipShowIdRead = function () {
                self.idRead = true;
            };
        },
        startScan() {
            this.idAction = 'scan';
            var self = this;
            this.$nextTick(function () {
                if (self.isMobile && window.__membershipStartLiveScan) window.__membershipStartLiveScan();
            });
        },
        selectPlan(value, label) {
            this.plan = value;
            this.planLabel = label;
            this.step = 2;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },
        openAgreement() {
            this.agreementOpen = true;
            this.$nextTick(function () {
                if (window.lucide) window.lucide.createIcons();
            });
        },
        openSign() {
            this.signOpen = true;
            this.$nextTick(function () {
                if (window.__membershipResizePad) window.__membershipResizePad();
            });
        }
    };
}

(function () {
    function assignFile(input, file, preview, hidden) {
        if (!input || !file) return;
        try {
            var dt = new DataTransfer();
            dt.items.add(file);
            input.files = dt.files;
        } catch (e) {}
        if (hidden && file.type.indexOf('image/') === 0) {
            var reader = new FileReader();
            reader.onload = function (ev) { hidden.value = ev.target.result; };
            reader.readAsDataURL(file);
        }
        if (preview && file.type.indexOf('image/') === 0) {
            var r = new FileReader();
            r.onload = function (ev) {
                preview.src = ev.target.result;
                preview.classList.remove('hidden');
            };
            r.readAsDataURL(file);
        }
    }

    var idInput = document.getElementById('id-document-input');
    var idZone = document.getElementById('id-paste-zone');
    if (idZone) {
        idZone.addEventListener('click', function (e) {
            if (e.target === idInput) return;
            idZone.focus();
        });
        idZone.addEventListener('paste', function (e) {
            var items = e.clipboardData && e.clipboardData.items;
            if (!items) return;
            for (var i = 0; i < items.length; i++) {
                if (items[i].type.indexOf('image') === 0) {
                    e.preventDefault();
                    readIdFile(items[i].getAsFile());
                    break;
                }
            }
        });
    }

    var selfieInput = document.getElementById('selfie-input');
    var selfiePreview = document.getElementById('selfie-preview');
    var selfieHidden = document.getElementById('selfie-data');
    var selfieBtn = document.getElementById('selfie-capture-btn');
    var selfieVideo = document.getElementById('selfie-video');
    var selfieSnap = document.getElementById('selfie-snap-btn');
    var stream = null;
    if (selfieInput) {
        selfieInput.addEventListener('change', function () {
            if (selfieInput.files[0]) assignFile(selfieInput, selfieInput.files[0], selfiePreview, selfieHidden);
        });
    }
    if (selfieBtn) {
        selfieBtn.addEventListener('click', function () {
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                selfieInput && selfieInput.click();
                return;
            }
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' }, audio: false }).then(function (s) {
                stream = s;
                selfieVideo.srcObject = s;
                selfieVideo.classList.remove('hidden');
                selfieSnap.classList.remove('hidden');
            }).catch(function () {
                selfieInput && selfieInput.click();
            });
        });
    }
    if (selfieSnap) {
        selfieSnap.addEventListener('click', function () {
            if (!selfieVideo || selfieVideo.readyState < 2) return;
            var canvas = document.createElement('canvas');
            canvas.width = selfieVideo.videoWidth || 640;
            canvas.height = selfieVideo.videoHeight || 480;
            canvas.getContext('2d').drawImage(selfieVideo, 0, 0);
            canvas.toBlob(function (blob) {
                if (!blob) return;
                var file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
                assignFile(selfieInput, file, selfiePreview, selfieHidden);
                if (stream) {
                    stream.getTracks().forEach(function (t) { t.stop(); });
                    stream = null;
                }
                selfieVideo.classList.add('hidden');
                selfieSnap.classList.add('hidden');
            }, 'image/jpeg', 0.9);
        });
    }

    var canvas = document.getElementById('membership-signature-pad');
    var pad = null;
    function resizePad() {
        if (!canvas || typeof SignaturePad === 'undefined') return;
        if (!pad) pad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
        var ratio = Math.max(window.devicePixelRatio || 1, 1);
        var data = pad.toData();
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = canvas.offsetHeight * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
        if (data.length) pad.fromData(data);
    }
    window.__membershipResizePad = resizePad;
    window.addEventListener('resize', resizePad);
    var clearBtn = document.getElementById('membership-signature-clear');
    if (clearBtn) clearBtn.addEventListener('click', function () { if (pad) pad.clear(); });
    var addBtn = document.getElementById('membership-signature-add');
    if (addBtn) {
        addBtn.addEventListener('click', function () {
            if (!pad || pad.isEmpty()) {
                alert(@json(__('site.membership.sign_required')));
                return;
            }
            var dataUrl = pad.toDataURL('image/png');
            document.getElementById('membership-signature').value = dataUrl;
            var preview = document.getElementById('signature-preview');
            preview.src = dataUrl;
            preview.classList.remove('hidden');
            if (typeof window.__membershipMarkSigned === 'function') {
                window.__membershipMarkSigned();
            }
        });
    }

    document.getElementById('membership-apply-form').addEventListener('submit', function (e) {
        var sig = document.getElementById('membership-signature').value;
        if (!sig) {
            e.preventDefault();
            alert(@json(__('site.membership.sign_required')));
        }
    });

    function setNationality(select, raw) {
        var aliases = {
            'rwa': 'Rwanda', rwandan: 'Rwanda', rwanda: 'Rwanda',
            cmr: 'Cameroon', cameroon: 'Cameroon', cameroun: 'Cameroon',
            gbr: 'United Kingdom', uk: 'United Kingdom', 'united kingdom': 'United Kingdom',
            'great britain': 'United Kingdom', britain: 'United Kingdom', british: 'United Kingdom',
            usa: 'United States', 'united states': 'United States', america: 'United States',
            american: 'United States', 'united states of america': 'United States',
            uga: 'Uganda', uganda: 'Uganda', ugandan: 'Uganda',
            tza: 'Tanzania', tanzania: 'Tanzania', tanzanian: 'Tanzania',
            bdi: 'Burundi', burundi: 'Burundi',
            cod: 'DR Congo', 'dr congo': 'DR Congo', drc: 'DR Congo', congo: 'DR Congo',
            ken: 'Kenya', kenya: 'Kenya', kenyan: 'Kenya'
        };
        var value = String(raw || '').trim();
        var mapped = aliases[value.toLowerCase()] || value;
        var options = select.options;
        for (var i = 0; i < options.length; i++) {
            if (options[i].value === mapped) {
                select.value = mapped;
                return;
            }
        }
        var extra = document.createElement('option');
        extra.value = mapped;
        extra.textContent = mapped;
        extra.selected = true;
        select.appendChild(extra);
    }

    function applyIdRead(result) {
        if (!result) return;
        var nameInput = document.getElementById('full-name-field');
        var idNum = document.getElementById('id-number-field');
        var exp = document.getElementById('id-expires-field');
        var nat = document.getElementById('nationality-field');
        var got = document.getElementById('id-scan-got');
        var wait = document.getElementById('id-scan-wait');
        var status = document.getElementById('id-scan-status');
        if (result.full_name && nameInput) nameInput.value = result.full_name;
        if (result.id_number && idNum) idNum.value = result.id_number;
        if (result.expires_on && exp) exp.value = result.expires_on;
        if (result.nationality && nat) setNationality(nat, result.nationality);
        if (typeof window.__membershipShowIdRead === 'function') window.__membershipShowIdRead();
        if (result.id_type && typeof window.__membershipSetIdType === 'function') {
            window.__membershipSetIdType(result.id_type);
        }
        var bits = [];
        if (result.full_name) bits.push(result.full_name);
        if (result.id_number) bits.push(result.id_number);
        if (result.nationality) bits.push(result.nationality);
        if (result.expires_on) bits.push(result.expires_on);
        if (got) {
            got.textContent = bits.length ? bits.join(' · ') : @json(__('site.membership.id_scan_photo_ok'));
            got.classList.remove('hidden');
        }
        if (wait) wait.classList.add('hidden');
        if (status) {
            status.classList.remove('hidden');
            status.textContent = @json(__('site.membership.id_no_photo_saved'));
        }
        if (idInput) idInput.value = '';
    }

    function readIdFile(file) {
        if (!file || !window.w2kIdRead || file.type.indexOf('image/') !== 0) return;
        var status = document.getElementById('id-scan-status');
        if (status) {
            status.classList.remove('hidden');
            status.textContent = @json(__('site.membership.id_reading'));
        }
        window.w2kIdRead.readDocument(file, function (msg) {
            if (status) status.textContent = msg;
        }).then(function (result) {
            applyIdRead(result);
            if (idInput) idInput.value = '';
        }).catch(function () {
            if (status) {
                status.classList.remove('hidden');
                status.textContent = @json(__('site.membership.id_read_fail'));
            }
            if (idInput) idInput.value = '';
        });
    }

    if (idInput && window.w2kIdRead) {
        idInput.addEventListener('change', function () {
            if (idInput.files[0]) readIdFile(idInput.files[0]);
        });
    }

    var liveVideo = document.getElementById('id-live-video');
    var liveStart = document.getElementById('id-live-start-btn');
    var liveStop = document.getElementById('id-live-stop-btn');
    var liveStream = null;
    var liveCancel = false;

    function stopLiveScan() {
        liveCancel = true;
        if (liveStream) {
            liveStream.getTracks().forEach(function (t) { t.stop(); });
            liveStream = null;
        }
        if (liveVideo) liveVideo.srcObject = null;
    }

    function startLiveScan() {
        if (!liveVideo || !window.w2kIdRead || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            var status = document.getElementById('id-scan-status');
            if (status) {
                status.classList.remove('hidden');
                status.textContent = @json(__('site.membership.id_camera_needed'));
            }
            return;
        }
        stopLiveScan();
        liveCancel = false;
        var status = document.getElementById('id-scan-status');
        if (status) {
            status.classList.remove('hidden');
            status.textContent = @json(__('site.membership.id_reading'));
        }
        navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: 'environment' } }, audio: false }).then(function (s) {
            liveStream = s;
            liveVideo.srcObject = s;
            return liveVideo.play();
        }).then(function () {
            return window.w2kIdRead.scanLive(liveVideo, function (msg) {
                if (status) status.textContent = msg;
            }, function () { return liveCancel; });
        }).then(function (result) {
            applyIdRead(result);
            stopLiveScan();
        }).catch(function (err) {
            if (liveCancel || (err && err.message === 'stopped')) return;
            if (status) {
                status.classList.remove('hidden');
                status.textContent = @json(__('site.membership.id_read_fail'));
            }
        });
    }

    window.__membershipStartLiveScan = startLiveScan;
    window.__membershipStopLiveScan = stopLiveScan;
    if (liveStart) liveStart.addEventListener('click', startLiveScan);
    if (liveStop) liveStop.addEventListener('click', stopLiveScan);

    (function pollIdScan() {
        var url = @json(url('/membership/id-scan/'.$scanToken.'/status'));
        function tick() {
            fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (data) {
                    if (data && data.status === 'ready') {
                        applyIdRead(data);
                        return;
                    }
                    setTimeout(tick, 3000);
                })
                .catch(function () { setTimeout(tick, 4000); });
        }
        setTimeout(tick, 2500);
    })();
})();
</script>
@endpush
