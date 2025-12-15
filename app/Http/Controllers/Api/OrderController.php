<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Models\Order;
use App\Models\Trade;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function __construct(
        private OrderService $orderService
    ) {}

    public function index(Request $request)
    {
        $request->validate([
            'symbol' => 'nullable|string|max:10',
        ]);

        $query = Order::query()->open();

        if ($request->has('symbol')) {
            $query->where('symbol', strtoupper($request->symbol));
        }

        $buyOrders = (clone $query)->buy()
            ->orderByDesc('price')
            ->get(['id', 'user_id', 'price', 'amount', 'created_at']);

        $sellOrders = (clone $query)->sell()
            ->orderBy('price')
            ->get(['id', 'user_id', 'price', 'amount', 'created_at']);

        return response()->json([
            'buy_orders' => $buyOrders,
            'sell_orders' => $sellOrders,
        ]);
    }

    public function myOrders(Request $request)
    {
        $orders = $request->user()
            ->orders()
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['orders' => $orders]);
    }

    public function myTrades(Request $request)
    {
        $userId = $request->user()->id;

        $trades = Trade::where('buyer_id', $userId)
            ->orWhere('seller_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($trade) use ($userId) {
                return [
                    'id' => $trade->id,
                    'symbol' => $trade->symbol,
                    'side' => $trade->buyer_id === $userId ? 'buy' : 'sell',
                    'price' => $trade->price,
                    'amount' => $trade->amount,
                    'total' => $trade->total,
                    'commission' => $trade->buyer_id === $userId ? $trade->commission : '0',
                    'created_at' => $trade->created_at,
                ];
            });

        return response()->json(['trades' => $trades]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'symbol' => 'required|string|max:10|in:BTC,ETH',
            'side' => 'required|in:buy,sell',
            'price' => 'required|numeric|gt:0',
            'amount' => 'required|numeric|gt:0',
        ]);

        $validated['symbol'] = strtoupper($validated['symbol']);

        $result = $this->orderService->createOrder(
            $request->user(),
            $validated['symbol'],
            $validated['side'],
            $validated['price'],
            $validated['amount']
        );

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        $response = [
            'message' => $result['matched'] ? 'Order matched successfully' : 'Order created successfully',
            'order' => $result['order']->fresh(),
            'matched' => $result['matched'],
        ];

        if ($result['matched']) {
            $response['match_details'] = $result['match_details'];
        }

        return response()->json($response, 201);
    }

    public function cancel(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($order->status !== Order::STATUS_OPEN) {
            return response()->json(['message' => 'Order cannot be cancelled'], 400);
        }

        $result = $this->orderService->cancelOrder($order);

        if (!$result['success']) {
            return response()->json(['message' => $result['message']], 400);
        }

        return response()->json(['message' => 'Order cancelled successfully']);
    }
}
