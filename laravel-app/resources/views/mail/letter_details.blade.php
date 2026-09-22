<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $general_setting->site_title }}</title>
    <style type="text/css">
        body { margin: 0; padding: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #1f2a44; }
        .letter-mail { max-width: 720px; margin: 0 auto; }
        .letter-mail-header img,
        .letter-mail-footer img { width: 100%; max-height: 110px; display: block; }
        .letter-mail-title { text-align: center; font-weight: 700; font-size: 18px; color: #0A0A0A; margin: 10px 0 16px; }
        .letter-mail-body { padding: 0 16px 16px; }
    </style>
</head>
<body>
@php
    $letterhead = \App\Support\Letterhead::resolve($general_setting ?? null);
    $siteTitle = trim((string) ($general_setting->site_title ?? ''));
@endphp
<div class="letter-mail">
    @if(! empty($letterhead['header_url']))
        <div class="letter-mail-header">
            <img src="{{ $letterhead['header_url'] }}" alt="{{ $siteTitle }}">
        </div>
    @elseif(! empty($general_setting->site_logo))
        <div style="text-align:center;padding:10px 0;">
            <img src="{{ url('public/logo/'.$general_setting->site_logo) }}" height="64" alt="{{ $siteTitle }}">
        </div>
    @endif
    @if($siteTitle !== '')
        <div class="letter-mail-title">{{ $siteTitle }}</div>
    @endif
    <div class="letter-mail-body">
        <h6>Ref: {{ $data->reference }} <br>
            {{ date('M d, Y') }}</h6>

        {!! isset($rendered_header) ? $rendered_header : $data->header !!}
        <h2>{{ __('mail.dear') }}:
            @php
                if ($data->people_type == "customer") {
                    $user = \App\Customer::class;
                } else {
                    $user = \App\Employee::class;
                }
                echo $user::find($to) ? $user::find($to)->name .  ', ' : '';
            @endphp
        </h2>
        {!! isset($rendered_body) ? $rendered_body : $data->body !!}
        <br><br>
        @if($data->is_sign == 1)
            @php $signer = \App\User::find($data->signed_by); @endphp
            @if($signer && $signer->sign)
                <img src="{{ url('public/images/user/'.$signer->sign) }}" height="80" alt="">
            @endif
        @endif
        <br>
        {!! isset($rendered_footer) ? $rendered_footer : $data->footer !!}
    </div>
    @if(! empty($letterhead['footer_url']))
        <div class="letter-mail-footer">
            <img src="{{ $letterhead['footer_url'] }}" alt="">
        </div>
    @endif
</div>
</body>
</html>
