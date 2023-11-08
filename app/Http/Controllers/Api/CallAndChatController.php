<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ElephantIO\Client;

class CallAndChatController extends Controller
{
    public function callNotifcation(Request  $request)
    {
        $this->validate($request, [
            'type' => 'required|in:reject,end',
            'receiver_id' => 'required'
        ]);

        $user = User::whereId($request->receiver_id)->select('id', 'device_token')->first();
        $type = $request->type;
        $data = [
            'to_user_id'        =>  $user->id,
            'from_user_id'      =>  auth()->id(),
            'notification_type' =>  "CALL_" . strtoupper($type),
            'title'             =>  "call has been " . $type . "ed",
            'description'        => "call has been " . $type . "ed",
            'redirection_id'    =>   $user->id
        ];


        $token = $user->device_token;
        if ($token) {
            app(PushNotificationService::class)->execute($data, [$token]);
        }

        return commonSuccessMessage("Sucess");
    }


    public function uploadAttachments(Request $request)
    {
        try {
            $this->validate($request, [
                'group_id' => 'required',
                'attachments' => 'required',
                'group_type' => 'required',
            ]);
            //code...
            $group_id = $request->group_id;
            $group_type = $request->group_type;
            $url = 'https://server1.appsstaging.com:3003/';
            $options = [
                'context' => [
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]
            ];
            $client = new Client(Client::engine(Client::CLIENT_4X, $url, $options));
            $client->initialize();

            foreach ($request->file('attachments') as  $file) {

                $profile_image = $file->store('public/images');
                $path = Storage::url($profile_image);
                $data = [
                    'group_id' => $group_id,
                    'sender_id' => auth()->id(),
                    'message' => $path,
                    'group_type' => $group_type,
                    'chat_type' => 'image',
                ];
                $client->emit('group_send_message', $data);
            }

            return commonSuccessMessage("Success");
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
