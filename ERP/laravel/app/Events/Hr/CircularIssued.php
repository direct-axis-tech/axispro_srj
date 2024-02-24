<?php

namespace App\Events\Hr;

use App\Models\Hr\Circular;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class CircularIssued
{
    use Dispatchable, InteractsWithSockets, SerializesModels;


    /**
     * circular
     *
     * @var Circular
     */
    public $circular;

    /**
     * Create a new event instance.
     *
     * @param Circular $circular
     * @return void
     */
    public function __construct(Circular $circular)
    {
        $this->circular = $circular;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
