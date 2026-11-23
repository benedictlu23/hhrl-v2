<?php

declare(strict_types=1);

use App\Events\NewBookingReceived;
use App\Models\Suite\RoomBooking;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class);

$originalDefaultConnection = null;
$originalSqliteConnection = null;

beforeEach(function () use (&$originalDefaultConnection, &$originalSqliteConnection): void {
    $originalDefaultConnection = config('database.default');
    $originalSqliteConnection = config('database.connections.sqlite');

    config([
        'database.default' => 'sqlite',
        'database.connections.sqlite.database' => ':memory:',
    ]);

    DB::purge('sqlite');
    DB::setDefaultConnection('sqlite');

    Schema::create('room_bookings', function (Blueprint $table): void {
        $table->id();
        $table->string('booking_code')->unique();
        $table->unsignedBigInteger('room_id');
        $table->string('guest_name');
        $table->dateTime('check_in');
        $table->dateTime('check_out');
        $table->string('status')->default('pending');
        $table->timestamps();
        $table->softDeletes();
    });
});

afterEach(function () use (&$originalDefaultConnection, &$originalSqliteConnection): void {
    DB::purge('sqlite');
    config(['database.connections.sqlite' => $originalSqliteConnection]);
    DB::setDefaultConnection($originalDefaultConnection);
});

it('broadcasts newly created pending bookings after their transaction commits', function (): void {
    Event::fake([NewBookingReceived::class]);

    DB::transaction(function (): void {
        RoomBooking::query()->create([
            'room_id' => 1,
            'guest_name' => 'Test Guest',
            'check_in' => now(),
            'check_out' => now()->addDay(),
            'status' => 'pending',
        ]);

        Event::assertNotDispatched(NewBookingReceived::class);
    });

    Event::assertDispatched(NewBookingReceived::class, fn (NewBookingReceived $event): bool => $event->booking->status === 'pending');
});

it('does not broadcast newly created non-pending bookings', function (): void {
    Event::fake([NewBookingReceived::class]);

    RoomBooking::query()->create([
        'room_id' => 1,
        'guest_name' => 'Test Guest',
        'check_in' => now(),
        'check_out' => now()->addDay(),
        'status' => 'confirmed',
    ]);

    Event::assertNotDispatched(NewBookingReceived::class);
});

it('broadcasts room booking updates on a private bookings channel', function (): void {
    $event = new NewBookingReceived(new RoomBooking());

    $channel = $event->broadcastOn()[0];

    expect($channel)->toBeInstanceOf(PrivateChannel::class);
    expect($channel->name)->toBe('private-bookings');
});
