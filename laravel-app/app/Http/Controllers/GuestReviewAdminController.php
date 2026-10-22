<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class GuestReviewAdminController extends Controller
{
    public function index()
    {
        $rows = collect();
        if (Schema::hasTable('guest_reviews')) {
            $rows = DB::table('guest_reviews')->orderByDesc('id')->paginate(40);
        }

        return view('guest_reviews.index', compact('rows'));
    }
}
