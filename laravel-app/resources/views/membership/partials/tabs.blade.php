@include('course_manager.partials.styles')
@php
    $mmTab = $mmTab ?? '';
    $tabs = [
        ['membership.admin.dashboard', 'Dashboard', 'dripicons-meter', 'tone-blue'],
        ['membership.admin.applications', 'Applications', 'dripicons-document-edit', 'tone-gold'],
        ['membership.admin.members', 'Members', 'dripicons-user-group', 'tone-purple'],
        ['membership.admin.plans', 'Plans', 'dripicons-card', 'tone-teal'],
        ['membership.admin.promotions', 'Promotions', 'dripicons-star', 'tone-orange'],
        ['membership.admin.benefits', 'Benefits', 'dripicons-gift', 'tone-green'],
        ['membership.admin.payments', 'Payments', 'dripicons-wallet', 'tone-blue'],
        ['membership.admin.agreements', 'Agreements', 'dripicons-document', 'tone-gold'],
        ['membership.admin.documents', 'Documents', 'dripicons-folder', 'tone-teal'],
        ['membership.admin.notifications', 'Notifications', 'dripicons-message', 'tone-purple'],
        ['membership.admin.reports', 'Reports', 'dripicons-graph-bar', 'tone-orange'],
        ['membership.admin.audit', 'Audit', 'dripicons-time-reverse', 'tone-red'],
        ['membership.admin.settings', 'Settings', 'dripicons-gear', 'tone-blue'],
    ];
@endphp
<nav class="cm-nav" aria-label="Membership">
    @foreach($tabs as $tab)
        <a href="{{ route($tab[0]) }}" class="{{ $tab[3] }} {{ $mmTab === $tab[0] ? 'is-active' : '' }}">
            <i class="{{ $tab[2] }}"></i> {{ $tab[1] }}
        </a>
    @endforeach
</nav>
