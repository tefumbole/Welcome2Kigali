@extends('beyond.layout')

@section('title', 'Contact Us')
@section('meta_description', 'Contact Welcome 2 Kigali Expats Club on WhatsApp.')

@section('content')

@php
    $contactPhone = \App\Support\SiteBrand::phone();
    $waPhone = \App\Support\SiteBrand::phoneWhatsAppDigits();
@endphp

<div class="min-h-screen bg-gray-50 py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="text-center mb-16">
            <h1 class="text-4xl md:text-5xl font-extrabold text-brand-blue mb-4">{{ \App\Support\SiteContent::text('contact.heading', 'Get in Touch') }}</h1>
            <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                {{ __('site.contact.intro') }}
            </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

            <div class="lg:col-span-4 space-y-6">
                <div class="bg-white rounded-xl border-t-4 border-t-brand-blue shadow-md hover:shadow-lg transition-all p-6">
                    <h3 class="text-xl font-bold text-brand-blue mb-4 flex items-center gap-2">
                        <i data-lucide="map-pin" class="w-5 h-5"></i> {{ __('site.contact.office') }}
                    </h3>
                    <div>
                        @include('beyond.partials.office_location')
                    </div>
                </div>

                <div class="bg-white rounded-xl border-t-4 border-t-brand-gold shadow-md hover:shadow-lg transition-all p-6">
                    <h3 class="text-xl font-bold text-brand-blue mb-4 flex items-center gap-2">
                        <i data-lucide="message-circle" class="w-5 h-5 text-brand-gold"></i> {{ __('site.contact.whatsapp') }}
                    </h3>
                    <div class="space-y-4 text-gray-600">
                        <a href="{{ \App\Support\SiteBrand::phoneWhatsAppUrl() }}" target="_blank" rel="noopener" class="flex items-center gap-3 pt-1 hover:text-brand-gold">
                            <div class="bg-green-100 p-2 rounded-full text-[#25D366]"><i data-lucide="message-circle" class="w-4 h-4"></i></div>
                            <div>
                                <p class="font-medium">{{ $contactPhone }}</p>
                                <p class="text-brand-gold text-xs font-semibold mt-0.5">{{ __('site.contact.whatsapp') }}</p>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="bg-brand-blue text-white shadow-md rounded-xl overflow-hidden relative p-6">
                    <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white/10 rounded-full blur-xl"></div>
                    <div class="relative z-10 flex items-start gap-4">
                        <div class="bg-white/20 p-3 rounded-full shrink-0"><i data-lucide="clock" class="w-6 h-6"></i></div>
                        <div>
                            <h3 class="font-bold text-lg mb-2 text-brand-gold">{{ __('site.contact.hours') }}</h3>
                            <div class="space-y-1 text-sm">
                                <div class="flex justify-between"><span class="text-blue-100">{{ __('site.contact.mon_fri') }}</span><span class="font-medium">{{ \App\Support\SiteContent::text('contact.hours_weekday', '9:00 AM - 6:00 PM') }}</span></div>
                                <div class="flex justify-between"><span class="text-blue-100">{{ __('site.contact.sat_sun') }}</span><span class="font-medium opacity-80">{{ \App\Support\SiteContent::text('contact.hours_weekend', __('site.contact.closed')) }}</span></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="shadow-xl border-0 h-full rounded-xl overflow-hidden bg-white">
                    <div class="bg-gradient-to-r from-brand-dark to-brand-blue p-6 text-white">
                        <h2 class="text-2xl font-bold flex items-center gap-2">
                            <i data-lucide="send" class="w-6 h-6 text-brand-gold"></i> {{ __('site.contact.send') }}
                        </h2>
                        <p class="text-blue-100 mt-1">{{ __('site.contact.send_sub') }}</p>
                    </div>
                    <div class="p-8 md:p-10">
                        <form id="contact-form" class="space-y-6" method="post" action="{{ route('beyond.contact.store') }}" onsubmit="return submitContact(event)">
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700">{{ __('site.contact.full_name') }} <span class="text-red-500">*</span></label>
                                    <input required name="name" type="text" placeholder="{{ __('site.contact.name_ph') }}"
                                           class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 focus:bg-white focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700">{{ __('site.contact.email') }} <span class="text-red-500">*</span></label>
                                    <input required name="email" type="email" placeholder="{{ __('site.contact.email_ph') }}"
                                           class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 focus:bg-white focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700">{{ __('site.contact.phone') }}</label>
                                    <input name="phone" type="tel" placeholder="{{ __('site.contact.phone_ph') }}"
                                           class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 focus:bg-white focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-sm font-semibold text-gray-700">{{ __('site.contact.subject') }} <span class="text-red-500">*</span></label>
                                    <input required name="subject" type="text" placeholder="{{ __('site.contact.subject_ph') }}"
                                           class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 focus:bg-white focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none">
                                </div>
                            </div>
                            <div class="space-y-2">
                                <label class="text-sm font-semibold text-gray-700">{{ __('site.contact.message') }} <span class="text-red-500">*</span></label>
                                <textarea required name="message" rows="6" placeholder="{{ __('site.contact.message_ph') }}"
                                          class="w-full rounded-md border border-gray-200 bg-gray-50 px-3 py-2 resize-none focus:bg-white focus:border-brand-blue focus:ring-2 focus:ring-brand-blue/20 outline-none"></textarea>
                            </div>
                            <div id="contact-success" class="hidden rounded-lg bg-green-50 border border-green-200 text-green-800 px-4 py-3 text-sm"></div>
                            <div class="pt-4 flex flex-col sm:flex-row gap-4">
                                <button type="submit"
                                        class="w-full sm:w-auto px-8 bg-brand-blue hover:bg-[#002a5a] text-white font-bold h-12 text-lg rounded-md shadow-md inline-flex items-center justify-center gap-2">
                                    <i data-lucide="send" class="w-5 h-5"></i> {{ __('site.contact.send_btn') }}
                                </button>
                                <a href="https://wa.me/{{ $waPhone }}?text={{ urlencode(__('site.contact.wa_prefill')) }}"
                                   target="_blank" rel="noopener"
                                   class="w-full sm:w-auto px-8 border-2 border-[#25D366] text-[#25D366] hover:bg-[#25D366] hover:text-white font-bold h-12 text-lg rounded-md inline-flex items-center justify-center gap-2 transition-colors">
                                    <i data-lucide="message-circle" class="w-5 h-5"></i> {{ __('site.contact.open_wa') }}
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
function submitContact(e) {
    e.preventDefault();
    const f = e.target;
    const el = document.getElementById('contact-success');
    const token = (document.querySelector('meta[name="csrf-token"]') || {}).content
        || (f.querySelector('input[name="_token"]') || {}).value;
    const fd = new FormData(f);
    fetch(f.action, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': token || ''
        },
        body: fd
    }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
      .then(function (res) {
          if (res.j && res.j.errors) {
              var first = Object.values(res.j.errors)[0];
              throw new Error(Array.isArray(first) ? first[0] : first);
          }
          if (!res.ok || !res.j.ok) {
              throw new Error((res.j && (res.j.message || res.j.error)) || 'Could not send');
          }
          el.textContent = res.j.message || @json(__('site.contact.success'));
          el.classList.remove('hidden');
          f.reset();
      }).catch(function () {
          el.textContent = 'Could not send just now. Please try WhatsApp or email.';
          el.classList.remove('hidden');
      });
    return false;
}
</script>
@endpush
