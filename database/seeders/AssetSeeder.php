<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\User;
use Illuminate\Database\Seeder;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $bob = User::where('email', 'bob@example.com')->first();

        if ($bob) {
            // Give Bob BTC and ETH to sell
            Asset::create([
                'user_id' => $bob->id,
                'symbol' => 'BTC',
                'amount' => '2.00000000', // 2 BTC
                'locked_amount' => '0.00000000',
            ]);

            Asset::create([
                'user_id' => $bob->id,
                'symbol' => 'ETH',
                'amount' => '50.00000000', // 50 ETH
                'locked_amount' => '0.00000000',
            ]);
        }

        $alice = User::where('email', 'alice@example.com')->first();

        if ($alice) {
            // Give Alice some ETH
            Asset::create([
                'user_id' => $alice->id,
                'symbol' => 'ETH',
                'amount' => '10.00000000', // 10 ETH
                'locked_amount' => '0.00000000',
            ]);
        }
    }
}
