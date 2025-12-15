<?php

namespace App\Services;

use App\Events\OrderMatched;
use App\Events\OrderPlaced;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    const COMMISSION_RATE = '0.015'; // 1.5%

    public function createOrder(User $user, string $symbol, string $side, string $price, string $amount): array
    {
        return DB::transaction(function () use ($user, $symbol, $side, $price, $amount) {
            $total = bcmul($price, $amount, 8);

            if ($side === 'buy') {
                $result = $this->createBuyOrder($user, $symbol, $price, $amount, $total);
            } else {
                $result = $this->createSellOrder($user, $symbol, $price, $amount);
            }

            if (!$result['success']) {
                return $result;
            }

            $matchResult = $this->tryMatchOrder($result['order']);
            $result['matched'] = $matchResult['matched'];

            if ($matchResult['matched']) {
                $result['match_details'] = $matchResult['details'];
            }

            // Broadcast orderbook update
            event(new OrderPlaced($symbol));

            return $result;
        });
    }

    private function createBuyOrder(User $user, string $symbol, string $price, string $amount, string $total): array
    {
        $user = User::where('id', $user->id)->lockForUpdate()->first();

        if (bccomp($user->balance, $total, 8) < 0) {
            return ['success' => false, 'message' => 'Insufficient balance'];
        }

        $user->balance = bcsub($user->balance, $total, 8);
        $user->save();

        $order = Order::create([
            'user_id' => $user->id,
            'symbol' => $symbol,
            'side' => 'buy',
            'price' => $price,
            'amount' => $amount,
            'status' => Order::STATUS_OPEN,
        ]);

        return ['success' => true, 'order' => $order];
    }

    private function createSellOrder(User $user, string $symbol, string $price, string $amount): array
    {
        $asset = Asset::where('user_id', $user->id)
            ->where('symbol', $symbol)
            ->lockForUpdate()
            ->first();

        if (!$asset) {
            return ['success' => false, 'message' => 'Asset not found'];
        }

        $available = bcsub($asset->amount, $asset->locked_amount, 8);

        if (bccomp($available, $amount, 8) < 0) {
            return ['success' => false, 'message' => 'Insufficient asset balance'];
        }

        $asset->locked_amount = bcadd($asset->locked_amount, $amount, 8);
        $asset->save();

        $order = Order::create([
            'user_id' => $user->id,
            'symbol' => $symbol,
            'side' => 'sell',
            'price' => $price,
            'amount' => $amount,
            'status' => Order::STATUS_OPEN,
        ]);

        return ['success' => true, 'order' => $order];
    }

    private function tryMatchOrder(Order $order): array
    {
        if ($order->side === 'buy') {
            return $this->matchBuyOrder($order);
        }

        return $this->matchSellOrder($order);
    }

    private function matchBuyOrder(Order $buyOrder): array
    {
        // Find first sell order where sell.price <= buy.price (same amount, full match only)
        $sellOrder = Order::where('symbol', $buyOrder->symbol)
            ->where('side', 'sell')
            ->where('status', Order::STATUS_OPEN)
            ->where('user_id', '!=', $buyOrder->user_id)
            ->where('price', '<=', $buyOrder->price)
            ->where('amount', $buyOrder->amount)
            ->orderBy('price', 'asc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->first();

        if (!$sellOrder) {
            return ['matched' => false];
        }

        return $this->executeMatch($buyOrder, $sellOrder);
    }

    private function matchSellOrder(Order $sellOrder): array
    {
        // Find first buy order where buy.price >= sell.price (same amount, full match only)
        $buyOrder = Order::where('symbol', $sellOrder->symbol)
            ->where('side', 'buy')
            ->where('status', Order::STATUS_OPEN)
            ->where('user_id', '!=', $sellOrder->user_id)
            ->where('price', '>=', $sellOrder->price)
            ->where('amount', $sellOrder->amount)
            ->orderBy('price', 'desc')
            ->orderBy('created_at', 'asc')
            ->lockForUpdate()
            ->first();

        if (!$buyOrder) {
            return ['matched' => false];
        }

        return $this->executeMatch($buyOrder, $sellOrder);
    }

    private function executeMatch(Order $buyOrder, Order $sellOrder): array
    {
        // Lock both orders
        $buyOrder = Order::where('id', $buyOrder->id)->lockForUpdate()->first();
        $sellOrder = Order::where('id', $sellOrder->id)->lockForUpdate()->first();

        // Use sell order price as execution price (maker price)
        $executionPrice = $sellOrder->price;
        $amount = $buyOrder->amount;
        $tradeValue = bcmul($executionPrice, $amount, 8);

        // Calculate commission (1.5% from buyer)
        $commission = bcmul($tradeValue, self::COMMISSION_RATE, 8);

        // Lock users for balance updates
        $buyer = User::where('id', $buyOrder->user_id)->lockForUpdate()->first();
        $seller = User::where('id', $sellOrder->user_id)->lockForUpdate()->first();

        // Calculate refund if buy price > execution price
        $buyerLockedAmount = bcmul($buyOrder->price, $amount, 8);
        $refund = bcsub($buyerLockedAmount, $tradeValue, 8);

        // Refund excess to buyer (difference between locked and actual trade value)
        if (bccomp($refund, '0', 8) > 0) {
            $buyer->balance = bcadd($buyer->balance, $refund, 8);
        }

        // Deduct commission from buyer's balance
        $buyer->balance = bcsub($buyer->balance, $commission, 8);
        $buyer->save();

        // Credit seller with trade value
        $seller->balance = bcadd($seller->balance, $tradeValue, 8);
        $seller->save();

        // Transfer asset from seller to buyer
        $sellerAsset = Asset::where('user_id', $seller->id)
            ->where('symbol', $buyOrder->symbol)
            ->lockForUpdate()
            ->first();

        $sellerAsset->amount = bcsub($sellerAsset->amount, $amount, 8);
        $sellerAsset->locked_amount = bcsub($sellerAsset->locked_amount, $amount, 8);
        $sellerAsset->save();

        // Credit buyer with asset
        $buyerAsset = Asset::firstOrCreate(
            ['user_id' => $buyer->id, 'symbol' => $buyOrder->symbol],
            ['amount' => '0', 'locked_amount' => '0']
        );
        $buyerAsset = Asset::where('id', $buyerAsset->id)->lockForUpdate()->first();
        $buyerAsset->amount = bcadd($buyerAsset->amount, $amount, 8);
        $buyerAsset->save();

        // Mark orders as filled
        $buyOrder->status = Order::STATUS_FILLED;
        $buyOrder->save();

        $sellOrder->status = Order::STATUS_FILLED;
        $sellOrder->save();

        $tradeDetails = [
            'buy_order_id' => $buyOrder->id,
            'sell_order_id' => $sellOrder->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'symbol' => $buyOrder->symbol,
            'price' => $executionPrice,
            'amount' => $amount,
            'total' => $tradeValue,
            'commission' => $commission,
        ];

        // Record trade in database
        Trade::create($tradeDetails);

        // Broadcast to both parties
        event(new OrderMatched($buyer->id, $seller->id, $tradeDetails));

        return [
            'matched' => true,
            'details' => $tradeDetails,
        ];
    }

    public function cancelOrder(Order $order): array
    {
        return DB::transaction(function () use ($order) {
            $order = Order::where('id', $order->id)->lockForUpdate()->first();

            if ($order->status !== Order::STATUS_OPEN) {
                return ['success' => false, 'message' => 'Order is not open'];
            }

            if ($order->side === 'buy') {
                $user = User::where('id', $order->user_id)->lockForUpdate()->first();
                $total = bcmul($order->price, $order->amount, 8);
                $user->balance = bcadd($user->balance, $total, 8);
                $user->save();
            } else {
                $asset = Asset::where('user_id', $order->user_id)
                    ->where('symbol', $order->symbol)
                    ->lockForUpdate()
                    ->first();

                $asset->locked_amount = bcsub($asset->locked_amount, $order->amount, 8);
                $asset->save();
            }

            $order->status = Order::STATUS_CANCELLED;
            $order->save();

            return ['success' => true];
        });
    }
}
