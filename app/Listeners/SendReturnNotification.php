<?php

namespace App\Listeners;

use App\Events\BookReturned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class SendReturnNotification
{
    public function __construct()
    {
        //
    }

    public function handle(BookReturned $event): void
    {
        Log::channel('stack')->info("User {$event->borrowing->user->name} returned book: {$event->borrowing->book->title}");
    }
}