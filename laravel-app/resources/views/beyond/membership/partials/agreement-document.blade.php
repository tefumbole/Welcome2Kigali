@php
    $clauses = $agreement->presentedClauses();
@endphp
<div class="bg-[#0A0A0A] text-white">
    <div class="px-5 md:px-8 pt-6 pb-4 text-center border-b border-brand-gold/30">
        <div class="flex justify-center mb-3">
            <i data-lucide="shield-check" class="w-10 h-10 text-brand-gold"></i>
        </div>
        <h2 class="text-xl md:text-2xl font-bold text-white m-0">{{ $agreement->title }}</h2>
        <p class="text-brand-gold text-sm mt-1 mb-0 tracking-wide">v{{ $agreement->version }}</p>
        <p class="text-gray-300 text-sm mt-2 mb-0">Please read the following terms carefully before proceeding.</p>
    </div>

    <div class="px-4 md:px-8 py-6 overflow-y-auto max-h-[52vh]">
        <div class="mb-6 p-5 bg-white/5 rounded-lg border-l-4 border-brand-gold">
            <h3 class="text-lg font-bold text-white mb-2">Membership License Agreement</h3>
            <p class="text-gray-300 text-sm md:text-base leading-relaxed m-0">
                This document is a binding understanding between <strong class="text-white">Welcome 2 Kigali Expats Club</strong> (the “Club”)
                and you (the “Member”). By clicking <span class="text-brand-gold font-semibold">I Agree</span>, you confirm that you have read, understood, and accepted these terms.
            </p>
        </div>

        @foreach ($clauses as $section)
            <div class="bg-white/5 border border-white/10 rounded-xl p-5 mb-4 hover:bg-white/[0.08] transition-colors">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-brand-gold/20 rounded-lg flex items-center justify-center text-brand-gold font-bold text-lg border border-brand-gold/30">
                        {{ $section['n'] }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-base md:text-lg font-bold text-brand-gold mb-2 flex items-center gap-2">
                            <i data-lucide="{{ $section['icon'] }}" class="w-5 h-5 text-gray-300 flex-shrink-0"></i>
                            {{ $section['title'] }}
                        </h4>
                        <p class="text-gray-300 leading-relaxed text-sm md:text-base m-0">{{ $section['body'] }}</p>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
