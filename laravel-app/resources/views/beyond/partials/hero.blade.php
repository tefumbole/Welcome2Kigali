<section class="bg-gradient-to-r from-brand-blue to-brand-dark py-6 sm:py-8 md:py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-white mb-2 leading-tight">{!! $title !!}</h1>
        @if (!empty($subtitle))
            <p class="text-sm sm:text-base md:text-lg text-gray-200 max-w-3xl mx-auto">{{ $subtitle }}</p>
        @endif
    </div>
</section>
