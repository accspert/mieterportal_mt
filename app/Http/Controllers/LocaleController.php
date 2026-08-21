<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        if (in_array($locale, ['de', 'en'])) {
            $request->session()->put('locale', $locale);
            cookie()->queue('locale', $locale, 60 * 24 * 365); // 1 year
            App::setLocale($locale);
        }

        return back();
    }
}
