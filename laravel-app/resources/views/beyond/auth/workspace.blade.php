@extends('beyond.auth.layout')

@section('title', __('site.workspace.title'))

@section('auth_body')
<div class="space-y-3">
    @foreach ($workspaces as $key)
        @php $meta = $labels[$key] ?? ['title' => ucfirst($key), 'desc' => '']; @endphp
        <form method="POST" action="{{ route('workspace.switch', ['workspace' => $key]) }}">
            @csrf
            <button type="submit"
                    class="w-full text-left rounded-xl border {{ $current === $key ? 'border-brand-blue bg-sky-50' : 'border-gray-200 bg-white hover:border-brand-blue' }} px-4 py-3 transition-colors">
                <div class="font-bold text-brand-blue">{{ $meta['title'] }}</div>
                <div class="text-sm text-gray-600 mt-0.5">{{ $meta['desc'] }}</div>
            </button>
        </form>
    @endforeach
</div>
@endsection
