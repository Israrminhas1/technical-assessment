<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $assets = $user->assets()->get(['symbol', 'amount', 'locked_amount']);

        return response()->json([
            'balance' => $user->balance,
            'assets' => $assets->map(function ($asset) {
                return [
                    'symbol' => $asset->symbol,
                    'amount' => $asset->amount,
                    'locked_amount' => $asset->locked_amount,
                    'available' => $asset->available_amount,
                ];
            }),
        ]);
    }
}
