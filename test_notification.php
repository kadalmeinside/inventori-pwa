<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = App\Models\User::first();
$notif = App\Models\AppNotification::where('user_id', $user->id)->first();
if ($notif) {
    echo "Found unread notif ID: {$notif->id}\n";
    $notif->markAsRead();
    echo "Marked as read. read_at is now: {$notif->read_at}\n";
    $notif->refresh();
    echo "After refresh, read_at is: {$notif->read_at}\n";
} else {
    echo "No unread notif found.\n";
}
