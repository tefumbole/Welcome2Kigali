<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Redirect;
use App\Language;

class LanguageController extends Controller
{
    public function switchLanguage($locale)
    {
        if (! in_array($locale, ['en', 'fr'], true)) {
            $locale = 'en';
        }

        setcookie('language', $locale, time() + (86400 * 365), '/');
        if (function_exists('session')) {
            session(['language' => $locale]);
        }

        $back = url()->previous();
        if (! $back || $back === url()->current()) {
            return redirect('/');
        }

        return redirect($back);
    }
}
