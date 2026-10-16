<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class MyAccountController extends Controller
{
    public function purchases(Request $request): View
    {
        $user = $request->user();

        return view('account.purchases', [
            'purchases' => $user->purchases()->active()->with('gadget')->latest('purchase_date')->get(),
            'reviewedGadgetIds' => $user->reviews()->pluck('gadget_id')->all(),
        ]);
    }

    public function reviews(Request $request): View
    {
        return view('account.reviews', [
            'reviews' => $request->user()->reviews()->with('gadget')->latest('id')->get(),
        ]);
    }
}
