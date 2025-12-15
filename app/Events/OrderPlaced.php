<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $symbol
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('orderbook.' . $this->symbol),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.placed';
    }
}
