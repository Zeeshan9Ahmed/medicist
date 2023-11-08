<?php


namespace App\Services\Notifications;


use Carbon\Carbon;

class PushNotificationService 
{


    

    public function execute($data,$token)
    {
        
        $message = $data['title'];
        $date = Carbon::now();
        $header = [
            'Authorization: key= AAAAQn8vSX4:APA91bETrBTfRFu7obreUQ89FnRhMwXvHX2q_EmQBFlEsU3PtL-wvWQYbKWDmDedhVKgeNFPKUbLgc0qUkkklXyuVNJ-PXY8JKjH9E4twnlVYodWczocT6PviJNh1_2A2PhbwYCMowyW',
            'Content-Type: Application/json'
        ];
        $notification = [
            'title' => 'Medicist',
            'body' =>  $message,
            'icon' => '',
            'image' => '',
            'sound' => 'default',
            'date' => $date->diffForHumans(),
            'content_available' => true,
            "priority" => "high",
            'badge' =>0
        ];
        if (gettype($token) == 'array') {
            $payload = [
                'registration_ids' => $token,
                'data' => (object)$data,
                'notification' => (object)$notification
            ];
        } else {
            $payload = [
                'to' => $token,
                'data' => (object)$data,
                'notification' => (object)$notification
            ];
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://fcm.googleapis.com/fcm/send",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $header
        ));
        // return true;
        $response = curl_exec($curl);
        $d  =[ 'res'=>$response,'data'=>$data];
 
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

}
