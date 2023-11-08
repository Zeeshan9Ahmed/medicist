<?php


namespace App\Services\Notifications;



use App\Models\Notification;
use App\Models\User;

class CreateDBNotification
{


    

    public function execute($data){
        Notification::create([
            'from_user_id' => $data['from_user_id'],
            'to_user_id' => $data['to_user_id'],
            'title' => $data['title'],
            'description' => $data['description'],
            'notification_type' => $data['notification_type'],
            'redirection_id'=> $data['redirection_id'],
            'notification_is_read' => "0"
        ]);
    //    $user = User::find($data['to_user_id']);
    //    if($user){
    //        $user->unread_notifications+=1;
    //        $user->save();
    //    }
    }

}
