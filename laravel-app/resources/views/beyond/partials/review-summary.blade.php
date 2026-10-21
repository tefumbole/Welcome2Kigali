@php
    $guestReviews = \App\Support\GuestReviews::summary();
    $reviewTone = isset($tone) ? $tone : 'light';
    $scoreLabel = $guestReviews['average'] === null ? '0.0' : number_format($guestReviews['average'], 1);
@endphp
<style>
    .w2k-reviews { margin: 0; }
    .w2k-reviews summary { list-style: none; cursor: pointer; }
    .w2k-reviews summary::-webkit-details-marker { display: none; }
    .w2k-reviews-score {
        display: flex; align-items: baseline; gap: .35rem;
        font-family: Georgia, "Times New Roman", serif;
        font-size: clamp(40px, 6vw, 64px); line-height: .9; font-weight: 700; color: #0A0A0A;
    }
    .w2k-reviews--dark .w2k-reviews-score { color: #F7F1E8; text-shadow: 0 2px 16px rgba(0,0,0,.45); }
    .w2k-reviews-score span { font-size: .42em; letter-spacing: .08em; color: #C5A059; font-weight: 700; }
    .w2k-reviews-count {
        display: block; margin-top: .35rem;
        font-size: 12px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: #C5A059;
    }
    .w2k-reviews-list { margin-top: 14px; max-height: 52vh; overflow: auto; padding-right: 4px; }
    .w2k-eulogy {
        background: linear-gradient(180deg, rgba(32,24,16,.96), rgba(16,12,10,.94));
        border: 1px solid rgba(197,160,89,.28); border-radius: 20px;
        padding: 18px 18px 14px; margin: 0 0 14px; color: #f6edd8;
        box-shadow: 0 16px 40px rgba(0,0,0,.18);
    }
    .w2k-eulogy cite {
        display: block; font-family: Georgia, "Times New Roman", serif; font-style: normal;
        font-size: clamp(22px, 3vw, 28px); font-weight: 700; color: #fff6dc; line-height: 1.15;
    }
    .w2k-eulogy time {
        display: block; margin-top: 4px; color: #c4b498; font-size: 12px;
        letter-spacing: .08em; text-transform: uppercase; font-weight: 700;
    }
    .w2k-eulogy p { margin: 14px 0 0; font-size: 17px; line-height: 1.7; }
    .w2k-eulogy p:first-of-type::first-letter {
        font-family: Georgia, "Times New Roman", serif; font-size: 2.4em; float: left;
        line-height: .8; padding: 6px 8px 0 0; color: #f0d57a; font-weight: 700;
    }
    .w2k-eulogy footer {
        display: flex; justify-content: space-between; gap: 12px; align-items: end;
        border-top: 1px solid rgba(197,160,89,.18); margin-top: 14px; padding-top: 12px;
        color: #f0d57a; font-size: 13px;
    }
</style>
<details class="w2k-reviews w2k-reviews--{{ $reviewTone }}">
    <summary>
        <span class="w2k-reviews-score">{{ $scoreLabel }}<span>/ 5</span></span>
        <span class="w2k-reviews-count">{{ trans_choice('site.reviews.count', $guestReviews['count'], ['count' => $guestReviews['count']]) }}</span>
    </summary>
    <div class="w2k-reviews-list">
        @forelse($guestReviews['rows'] as $review)
            <article class="w2k-eulogy">
                <cite>{{ $review->guest_name ?: __('site.reviews.guest') }}</cite>
                <time>{{ \Carbon\Carbon::parse($review->created_at)->format('d M Y') }}</time>
                @if($review->comment)
                    <p>{{ $review->comment }}</p>
                @endif
                <footer>
                    <span>{{ number_format($review->score, 1) }} / 5</span>
                    <span>{{ __('site.reviews.cleanliness') }} {{ $review->cleanliness }} · {{ __('site.reviews.food') }} {{ $review->food_quality }}</span>
                </footer>
            </article>
        @empty
            <p class="w2k-reviews-count">{{ __('site.reviews.empty') }}</p>
        @endforelse
    </div>
</details>
