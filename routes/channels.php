<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('hhrl-channel', function ($user) {
    return $user !== null;
});

Broadcast::channel('bookings', function (User $user): bool {
    return true;
});
