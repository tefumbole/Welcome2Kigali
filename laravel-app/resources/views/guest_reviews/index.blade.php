@extends('layout.main')
@section('content')
@if(session()->has('not_permitted'))
  <div class="alert alert-danger alert-dismissible text-center"><button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>{{ session()->get('not_permitted') }}</div>
@endif

<section class="forms">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex align-items-center">
                        <h4>Guest Reviews</h4>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Phone numbers are verified by WhatsApp OTP and shown here only (not on the public Reviews page).</p>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Country</th>
                                        <th>Phone</th>
                                        <th>Verified</th>
                                        <th>Scores</th>
                                        <th>Comment</th>
                                        <th>Signature</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($rows as $review)
                                        @php
                                            $avg = round(((int) $review->cleanliness + (int) $review->on_time + (int) $review->service + (int) $review->food_quality) / 4, 1);
                                        @endphp
                                        <tr>
                                            <td>{{ $review->id }}</td>
                                            <td>{{ $review->guest_name ?: '—' }}</td>
                                            <td>{{ $review->country ?? '—' }}</td>
                                            <td>{{ $review->phone ?? '—' }}</td>
                                            <td>
                                                @if(!empty($review->phone_verified_at))
                                                    <span class="badge badge-success">{{ \Carbon\Carbon::parse($review->phone_verified_at)->format('d M Y H:i') }}</span>
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>
                                                {{ $avg }} / 5<br>
                                                <small>C {{ $review->cleanliness }} · T {{ $review->on_time }} · S {{ $review->service }} · F {{ $review->food_quality }}</small>
                                            </td>
                                            <td style="max-width:220px;">{{ \Illuminate\Support\Str::limit($review->comment, 120) }}</td>
                                            <td>
                                                @if(!empty($review->signature))
                                                    <img src="{{ $review->signature }}" alt="Signature" style="max-height:48px;background:#fff;border:1px solid #ddd;">
                                                @else
                                                    —
                                                @endif
                                            </td>
                                            <td>{{ \Carbon\Carbon::parse($review->created_at)->format('d M Y H:i') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="9" class="text-center">No guest reviews yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if(method_exists($rows, 'links'))
                            <div class="mt-3">{{ $rows->links() }}</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
