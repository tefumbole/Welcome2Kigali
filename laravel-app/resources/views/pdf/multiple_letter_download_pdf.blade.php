<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $general_setting->site_title }}</title>
    @php
        $letterhead_flow = true;
        $use_system_letterhead = true;
        $letterhead = \App\Support\Letterhead::ensureSynced();
    @endphp
    @include('pdf.partials._letter_branded_styles')
    <style type="text/css">
        .letter-recipient-break { page-break-after: always; }
    </style>
</head>
<body>
@php $printed = 0; @endphp
@foreach ($ids as $id)
    @php
        $data = \App\Letter::find($id);
        if (! $data) {
            continue;
        }
        $people_type = $data->people_type;
        $copies = [];
        if ($people_type === 'directory') {
            foreach (\App\Support\LetterRecipients::decodePeopleJson($data->recipients_json) as $person) {
                $copies[] = ['to' => $person['id'] ?? null, 'user_to' => \App\Support\LetterRecipients::toSendObject($person)];
            }
        } else {
            foreach (array_filter(explode(',', (string) $data->to)) as $toId) {
                $copies[] = ['to' => trim($toId), 'user_to' => null];
            }
        }
        if (! count($copies)) {
            $copies[] = ['to' => null, 'user_to' => null];
        }
    @endphp
    @foreach ($copies as $copy)
        @if($printed > 0)
            <div class="letter-recipient-break"></div>
        @endif
        @php
            $to = $copy['to'];
            $user_to = $copy['user_to'];
            $printed++;
        @endphp
        @include('pdf.partials._letter_branded_open')
        @include('pdf.partials._letter_branded_inner')
        @include('pdf.partials._letter_branded_close')
    @endforeach
@endforeach
</body>
</html>
