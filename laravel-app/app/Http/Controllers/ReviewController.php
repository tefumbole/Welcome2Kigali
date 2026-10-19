<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReviewController extends Controller
{
    public function show(Request $request)
    {
        return view('beyond.reviews', [
            'reference' => $this->reference($request->query('ref')),
            'saved' => (bool) session('review_saved'),
        ]);
    }

    public function store(Request $request)
    {
        if (! Schema::hasTable('guest_reviews')) {
            return back()->withInput()->withErrors(['review' => __('site.reviews.unavailable')]);
        }

        $data = $request->validate([
            'cleanliness' => 'required|integer|min:1|max:5',
            'on_time' => 'required|integer|min:1|max:5',
            'service' => 'required|integer|min:1|max:5',
            'food_quality' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
            'guest_name' => 'nullable|string|max:80',
            'reference' => 'nullable|string|max:80',
        ]);

        DB::table('guest_reviews')->insert([
            'reference' => $this->reference($data['reference'] ?? ''),
            'guest_name' => trim((string) ($data['guest_name'] ?? '')) ?: null,
            'cleanliness' => (int) $data['cleanliness'],
            'on_time' => (int) $data['on_time'],
            'service' => (int) $data['service'],
            'food_quality' => (int) $data['food_quality'],
            'comment' => trim((string) ($data['comment'] ?? '')) ?: null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('beyond.reviews')->with('review_saved', true);
    }

    protected function reference($value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return substr(preg_replace('/[^\w\-\.\/]/', '', $value), 0, 80) ?: null;
    }
}
