<?php

namespace App\Events;

use App\Models\Suite\RoomBooking as Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewBookingReceived implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(public Booking $booking)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('bookings'),
            new PrivateChannel('bookings'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'NewBookingReceived';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'guest_name' => $this->booking->guest_name,
            'guest_email' => $this->booking->guest_email,
            'guest_phone' => $this->booking->guest_phone,
            'room_id' => $this->booking->room_id,
            'room_number' => $this->booking->room?->room_number,
            'room_name' => $this->booking->room?->name,
            'category_name' => $this->booking->room?->category?->name,
            'booking_type' => $this->booking->booking_type,
            'check_in' => $this->booking->check_in?->toIso8601String(),
            'check_out' => $this->booking->check_out?->toIso8601String(),
            'adults' => $this->booking->adults,
            'children' => $this->booking->children,
            'pets' => $this->booking->pets,
            'status' => $this->booking->status,
            'payment_status' => $this->booking->payment_status,
            'payment_method' => $this->booking->payment_method,
            'total_price' => (float) $this->booking->total_price,
            'is_walk_in' => (bool) $this->booking->is_walk_in,
            'created_at' => $this->booking->created_at?->toIso8601String(),
        ];
    }
}
