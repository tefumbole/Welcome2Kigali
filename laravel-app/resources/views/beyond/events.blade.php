@extends('beyond.layout')

@section('title', __('site.events.title'))
@section('meta_description', __('site.events.none_sub'))

@section('content')

<section class="py-10 bg-gray-50 min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <form method="GET" action="{{ url('/events') }}" class="mb-8 flex flex-col md:flex-row gap-4 items-stretch md:items-end">
            <div class="flex-1">
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('site.events.search') }}</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('site.events.search_ph') }}"
                       class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-brand-blue focus:border-brand-blue">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('site.events.filter') }}</label>
                <select name="filter" class="rounded-lg border border-gray-300 px-4 py-2 focus:ring-2 focus:ring-brand-blue">
                    @foreach(['upcoming' => __('site.events.upcoming'), 'featured' => __('site.events.featured'), 'ongoing' => __('site.events.ongoing'), 'past' => __('site.events.past')] as $k => $label)
                        <option value="{{ $k }}" {{ $filter === $k ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">{{ __('site.events.type') }}</label>
                <select name="type" class="rounded-lg border border-gray-300 px-4 py-2">
                    <option value="">{{ __('site.events.all_types') }}</option>
                    @foreach(\App\Event::TYPES as $k => $label)
                        <option value="{{ $k }}" {{ request('type') === $k ? 'selected' : '' }}>{{ __('site.events.types.'.$k) }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="px-6 py-2 bg-brand-blue text-white font-semibold rounded-lg hover:bg-brand-dark transition">{{ __('site.events.search') }}</button>
        </form>

        @if ($events->isEmpty())
            <div class="text-center py-20">
                <i data-lucide="calendar" class="w-16 h-16 text-gray-300 mx-auto mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ __('site.events.none') }}</h2>
                <p class="text-gray-600">{{ __('site.events.none_sub') }}</p>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach ($events as $row)
                    @php
                        $ev = $row['event'];
                        $pub = $row['pub'];
                        $flyer = $row['flyer'];
                        $countdownAt = $row['countdown_at'] ?? null;
                        $status = $row['public_status'];
                        $statusColors = [
                            'coming_soon' => 'bg-blue-100 text-blue-800',
                            'setup_in_progress' => 'bg-amber-100 text-amber-800',
                            'happening_today' => 'bg-green-100 text-green-800',
                            'event_in_progress' => 'bg-yellow-100 text-yellow-900',
                            'completed' => 'bg-gray-200 text-gray-700',
                            'postponed' => 'bg-orange-100 text-orange-800',
                            'cancelled' => 'bg-red-100 text-red-800',
                        ];
                    @endphp
                    <div class="bg-white rounded-xl border border-gray-200 hover:border-brand-blue transition-all hover:shadow-xl overflow-hidden flex flex-col">
                        <a href="{{ url('/events/' . $ev->slug) }}" class="block relative">
                            <div class="relative aspect-[4/3] overflow-hidden bg-gray-200">
                                @if ($flyer)
                                    <img src="{{ $flyer }}" alt="{{ $pub->public_title ?: $ev->name }}" class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-brand-blue to-brand-light min-h-[220px]">
                                        <i data-lucide="calendar" class="w-16 h-16 text-white opacity-50"></i>
                                    </div>
                                @endif
                                @if($status)
                                    <span class="absolute bottom-3 left-3 text-xs font-semibold px-2 py-1 rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ __('site.events.statuses.'.$status) }}
                                    </span>
                                @endif
                            </div>
                        </a>
                        @if($countdownAt && optional($pub)->show_countdown)
                            <div class="p-3">
                                @include('beyond.partials.event_countdown', [
                                    'targetIso' => $countdownAt->toIso8601String(),
                                    'timezone' => $ev->timezone ?: 'Africa/Kigali',
                                    'completionMessage' => $pub->countdown_completion_message ?: __('site.events.here'),
                                    'hideAfter' => false,
                                    'compact' => true,
                                ])
                            </div>
                        @endif
                        <div class="px-4 pb-4">
                            <a href="{{ url('/events/' . $ev->slug) }}" class="inline-flex items-center gap-2 text-brand-blue font-semibold text-sm">
                                {{ __('site.events.view') }} <i data-lucide="arrow-right" class="w-4 h-4"></i>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

@endsection
