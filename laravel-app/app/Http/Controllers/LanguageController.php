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
        if (! in_array($locale, ['en', 'fr', 'rw'], true)) {
            $locale = 'en';
        }

        \App\Support\VisitorLocale::persistChoice($locale);

        $back = url()->previous();
        if (! $back || $back === url()->current()) {
            return redirect('/');
        }

        return redirect($back);
    }
}
