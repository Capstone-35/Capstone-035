<?php

namespace App\Http\Controllers\Styleguide;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use Illuminate\View\View;

class StyleguideController extends Controller
{
    public function index(): View
    {
        if (app()->environment('production')) {
            abort(404);
        }

        return view('styleguide', [
            'statuses' => OrderStatus::cases(),
        ]);
    }
}
