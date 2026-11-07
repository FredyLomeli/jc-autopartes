<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function faqs(): View
    {
        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('public.pages.faqs', compact('faqs'));
    }

    public function contact(): View
    {
        return view('public.pages.contact');
    }

    public function warranty(): View
    {
        return view('public.pages.warranty');
    }

    public function privacy(): View
    {
        return view('public.pages.privacy');
    }

    public function terms(): View
    {
        return view('public.pages.terms');
    }
}
