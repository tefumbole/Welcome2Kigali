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
</head>
<body>
@include('pdf.partials._letter_branded_open')
@php
    if ($data->people_type == "customer") {
        $user_class = \App\Customer::class;
    } else {
        $user_class = \App\Employee::class;
    }
@endphp

@php
    $data->rendered_header = isset($data->rendered_header) ? $data->rendered_header : $data->header;
    $data->rendered_body = isset($data->rendered_body) ? $data->rendered_body : $data->body;
    $data->rendered_footer = isset($data->rendered_footer) ? $data->rendered_footer : $data->footer;
    $user_to = null;
    $to = null;
    $people = array_filter(explode(',', (string) $data->to));
    if (count($people)) {
        $to = trim($people[0]);
        $user_to = $user_class::find($to);
    }
@endphp
@include('pdf.partials._letter_branded_inner')

@include('pdf.partials._letter_branded_close')
</body>
</html>
