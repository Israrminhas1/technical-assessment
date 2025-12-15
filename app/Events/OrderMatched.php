<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderMatched implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $buyerId,
        public int $sellerId,
        public array $tradeDetails
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->buyerId),
            new PrivateChannel('user.' . $this->sellerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.matched';
    }

    public function broadcastWith(): array
    {
        return [
            'trade' => $this->tradeDetails,
        ];
    }
}
