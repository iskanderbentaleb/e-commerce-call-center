<?php

use Illuminate\Support\Facades\Broadcast;

// Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
//     return (int) $user->id === (int) $id;
// });

// Broadcast::channel('chat', function ($user) {
//     return true; // Allow all users for testing
// });

// Broadcast::channel('messages.{userId}', function ($user, $userId) {
//     return (int) $user->id === (int) $userId;
// });

// Broadcast::channel('my-channel', function ($user, $userId) {
//     return (int) $user->id === (int) $userId;
// });


Broadcast::channel('private-messages.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
