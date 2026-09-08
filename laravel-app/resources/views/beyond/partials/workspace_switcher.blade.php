@php
    $wsUser = $wsUser ?? auth('web')->user();
    $wsBeyond = $wsBeyond ?? auth('beyond')->user();
    $wsKeys = \App\Support\UserWorkspaces::keys($wsUser, $wsBeyond);
    $wsCurrent = \App\Support\UserWorkspaces::current($wsUser);
    $wsLabels = \App\Support\UserWorkspaces::labels();
@endphp
@if (count($wsKeys) > 1)
    <div class="px-4 py-2 text-xs font-bold uppercase tracking-wide text-gray-500">{{ __('site.workspace.switch') }}</div>
    @foreach ($wsKeys as $wsKey)
        <form method="POST" action="{{ route('workspace.switch', ['workspace' => $wsKey]) }}">
            @csrf
            <button type="submit"
                    class="w-full flex items-center justify-between px-4 py-2.5 text-sm {{ $wsCurrent === $wsKey ? 'bg-sky-50 text-brand-blue font-semibold' : 'text-gray-800 hover:bg-gray-50' }}">
                <span>{{ $wsLabels[$wsKey]['title'] ?? ucfirst($wsKey) }}</span>
                @if ($wsCurrent === $wsKey)
                    <span class="text-xs">✓</span>
                @endif
            </button>
        </form>
    @endforeach
    <div class="border-t border-gray-100 my-1"></div>
@endif
