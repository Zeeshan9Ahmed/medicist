<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;


if (!function_exists('getProfile')) {

    function getProfile($id , $role)
    {
        return User::whereId($id)
        ->when( $role == "user", function($q){
            $q->with('health_condition.report_images','emergency_contacts');
        })
        ->when($role == "doctor", function($q){
            $q->with('schedule','certificates','doctor_profile');
        })
        ->when($role == "pharmacy" || $role == "labortory", function($q){
            $q->with('schedule','certificates','additional_data');
        })
        ->first();
    }
}
if (!function_exists('apiSuccessMessage')) {

    function apiSuccessMessage($message = "Record Updated", $data = [],$token = "", $responseCode = 200)
    {
        $response = '';
        if($token == ''){

            $response = ['status' => 1, 'message' => $message, "data" => (object) $data];
        }else{
            $response = ['status' => 1, 'message' => $message, "data" => (object) $data, 'bearer_token' => $token];

        }
        return response()->json($response, $responseCode);
    }
}
if (!function_exists('commonSuccessMessage')) {
    // change status
    
    function commonSuccessMessage($message = "Record Updated", $responeCode = 200)
    {
        $response = ['status' => 1, 'message' => $message];
        return response()->json($response, $responeCode);
    }
}
if (!function_exists('commonErrorMessage')) {

    function commonErrorMessage($message = "",  $responeCode = 203)
    {
        
        if ($message == "") {
            $message = config("constants.commonErrorMessage");
        }
        $response = ['status' => 0, 'message' => $message];
        // if ( $data != '') {
        //     $response = ['status' => 0,  'message' => $message ,"data" => (object) $data];
            
        // }
        return response()->json($response, $responeCode);
    }
}
if (!function_exists('generateInvoice')) {
    function generateInvoice($doctor_id , $user_id , $appointment_id , $amount , $reason , $payment_method = 'card')
    {
        Invoice::create([
            'doctor_id' => $doctor_id,
            'user_id' => $user_id,
            'appointment_id' => $appointment_id,
            'amount' => $amount,
            'reason' => $reason,
            'payment_method' => $payment_method,
        ]);
    }
}

if (!function_exists('calculatePercentage')) {
    function calculatePercentage($amount, $percentage) {
        // Check if the percentage is valid (between 0 and 100)
        if ($percentage < 0 || $percentage > 100) {
            return "Invalid percentage";
        }
    
        // Calculate the percentage
        $result = ($percentage / 100) * $amount;
        return $result;
    }
}

if(! function_exists('removeSpecialCharecter')){
    function removeSpecialCharecter($string = ""){
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
    }
}
if (!function_exists('utcToLocalTime')) {
    function utcToLocalTime($dateInUTC = "")
    {
        if($dateInUTC != ""){
            $dt = new DateTime($dateInUTC, new DateTimeZone('UTC'));

            // change the timezone of the object without changing it's time
            if(session()->has('timezone')){
                $dt->setTimezone(new DateTimeZone(session()->get('timezone')));
            }
            return $dt->format('M d, Y h:i a');
        }else{
            return $dateInUTC;
        }
    }
}
if (!function_exists('uploadImage')) {

    function uploadImage($image, $width = 128, $height = 128)
    {
        try{
            $thumbails_path = config("constants.THUMBNAIL_PATH");
            $images_path = config("constants.IMAGE_PATH");
            File::isDirectory($thumbails_path) or File::makeDirectory($thumbails_path, 0777, true, true);
            File::isDirectory($images_path) or File::makeDirectory($images_path, 0777, true, true);
            $rand = str_shuffle('coolrunning');
            $image_name = $rand . time() . '.' . $image->getClientOriginalExtension();
            $destination_path = $thumbails_path;

            $resize_image = Image::make($image->getRealPath());
            // $resize_image->resize($width, $height, function ($constraint) {
            //     $constraint->aspectRatio();
            // })->save($destination_path . '/' . $image_name);

            // $resize_image->resize($width, $height, function ($constraint) {
            //     //$constraint->aspectRatio();
            //     //$constraint->upsize();
            // })->save($destination_path . '/' . $image_name);

            $resize_image->fit($width, $height, function ($constraint) {
                //$constraint->upsize();
            })->save($destination_path . '/' . $image_name);

            //save orignal image in images folder
            $destination_path = $images_path;
            if($image->move($destination_path, $image_name)){
                return $image_name;
            }else{
                return "";
            }
        }catch(Exception $e){
            Log::error($e->getMessage());
        }
    }
}
if (!function_exists('uploadVideo')) {

    function uploadVideo($file)
    {
        $video_path = config("constants.VIDEO_PATH");
        File::isDirectory($video_path) or File::makeDirectory($video_path, 0777, true, true);
        $rand = str_shuffle('monster');
        $video_name = $rand.time().'.'.$file->getClientOriginalExtension();

        //save orignal video in video folder
        $destination_path = $video_path;
        if($file->move($destination_path, $video_name)){
            return $video_name;
        }else{
            return "";
        }
    }
}
if (!function_exists('getThumbnailUrl')) {

    function getThumbnailUrl($filename = "")
    {
        $path = config('constants.THUMBNAIL_URL');
        return url($path) . '/' . $filename;
    }
}
if (!function_exists('getImageUrl')) {

    function getImageUrl($filename = "")
    {
        $path = config('constants.IMAGE_URL');
        return url($path) . '/' . $filename;
    }
}

if (!function_exists('isSubscribed')) {

    function isSubscribed() 
    {
        return collect(DB::select(DB::raw('( SELECT IF ((SELECT expires_at from subscriptions where subscriptions.user_id = "'.auth()->id().'" order by id desc limit 1) >= CURDATE() , 1, 0 ) as is_subscribed ) ')))->first()->is_subscribed;
    }
}
if (!function_exists('getDummyImageUrl')) {

    function getDummyImageUrl($filename = "avatar.png")
    {
        $path = config('constants.DUMMY_IMAGE_URL');
        return url($path) . '/' . $filename;
    }
}
if (!function_exists('webapiSuccessMessage')) {

    function webapiSuccessMessage($message = "Record Updated", $data = [], $responseCode = 200)
    {
        $response = ['success' => 1, 'message' => $message, "data" => (object) $data];
        return response()->json($response, $responseCode);
    }
}
if (!function_exists('webcommonSuccessMessage')) {

    function webcommonSuccessMessage($message = "Record Updated", $reload = true, $redirect = '')
    {
        $response = ['success' => 1, 'message' => $message, "data" => [], 'reload' => $reload, 'redirect' => $redirect];
        return response()->json($response, 200);
    }
}
if (!function_exists('webcommonErrorMessage')) {

    function webcommonErrorMessage($message = "", $data = [], $responeCode = 203)
    {
        if ($message == "") {
            $message = config("constants.commonErrorMessage");
        }
        $response = ['success' => 0, 'message' => $message, "data" => (object) $data];
        return response()->json($response, $responeCode);
    }
}