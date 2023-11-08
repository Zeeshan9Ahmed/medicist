<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmergencyContact;
use Illuminate\Http\Request;

class EmergencyContactsController extends Controller
{
    public function emergencyContacts (Request $request) {
        $contacts = json_decode($request->emergency_contacts);
        // return $contacts;

        foreach ($contacts as $contact) {
            $data = [
                'first_name' => $contact->first_name,
                'last_name' => $contact->last_name,
                'contact_number' => $contact->contact_number,
                'relation' => $contact->relation,

            ];
            if (isset($contact->id)) {
                EmergencyContact::whereId($contact->id)->update($data);
            }else {
                $data['user_id'] = auth()->id();
                EmergencyContact::create($data);
            }
        }

        return apiSuccessMessage("Success", ['step' => 2]);
        
    }

    public function deleteEmergencyContacts (Request $request) {

        $this->validate($request, ['contact_id' => 'required|exists:emergency_contacts,id']);
        EmergencyContact::whereId($request->contact_id)->delete();
        return commonSuccessMessage("Contact removed successfully.");
    }
}
