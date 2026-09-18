<section class="bg-gradient-to-r from-brand-blue to-brand-dark py-8 md:py-10">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">{!! $title !!}</h1>
        @if (!empty($subtitle))
            <p class="text-base md:text-lg text-gray-200">{{ $subtitle }}</p>
        @endif
    </div>
</section>
