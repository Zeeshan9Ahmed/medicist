<?php

namespace App\Listeners;

use App\Events\AppointmentBookedEvent;
use App\Services\Notifications\CreateDBNotification;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class AppointmentBookedListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(AppointmentBookedEvent $event): void
    {
        $data = [
            'to_user_id'        =>  $event->receiver_id,
            'from_user_id'      =>  $event->sender_id,
            'notification_type' =>  'BOOK_APPOINTMENT',
            'title'             =>  $event->message,
            'description'             =>  $event->message,
            'redirection_id'    =>   $event->sender_id
        ];
        
        app(CreateDBNotification::class)->execute($data);
        app(PushNotificationService::class)->execute($data,[$event->receiver_token]);
    }
}
