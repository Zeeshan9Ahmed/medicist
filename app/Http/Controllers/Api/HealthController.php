<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LoggedInUser;
use App\Models\HealthCondition;
use App\Models\Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HealthController extends Controller
{
    public function healthCondition (Request $request) {
        
        $this->validate($request, [
            'ellergies' => 'required',
            'diseases' => 'required',
            'symptoms' => 'required',
            'advice' => 'required',
            'type' => 'required|in:past,present',
            
        ]);

        $id = HealthCondition::updateOrCreate(
            ['user_id' => auth()->id(), 'type' => $request->type], 
            $request->only(['ellergies','diseases','symptoms','advice'])
        )->id;

        if ($request->hasFile('reports')) {
            foreach($request->file('reports') as  $file) {
                $profile_image = $file->store('public/images');
                $path = Storage::url($profile_image);
               
                Image::create([
                    'table_id' => $id,
                    'table_name' => 'health_conditions',
                    'image_type' => 'report',
                    'image_path' => $path,
                ]);
            }
        }
        
        if ($request->type == 'present' && auth()->user()->profile_completed == 0) {
            auth()->user()->profile_completed = 1;
            auth()->user()->save();
        }

        if ($request->type == "present") {
            return apiSuccessMessage("Success", new LoggedInUser(getProfile(auth()->id(), auth()->user()->role)));
        }
        return apiSuccessMessage("Success", ['step' => 3]);
    } 
}
