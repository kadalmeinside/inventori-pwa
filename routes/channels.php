<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('warehouse.{id}', function ($user, $id) {
    return $user->role->value === 'super_admin' || $user->warehouse_id == $id;
});

Broadcast::channel('superadmin', function ($user) {
    return $user->role->value === 'super_admin';
});
