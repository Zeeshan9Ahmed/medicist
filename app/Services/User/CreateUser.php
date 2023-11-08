<?php


namespace App\Services\User;


use App\Models\User;
use App\Services\BaseService;

class CreateUser
{



    
    /**
     * Create user.
     *
     * @param  array  $data
     * @return User
     */
    public function execute(array $data): User
    {
        // dd($data);
        $user = User::UpdateOrcreate($data);
//        app(UpdateProfileImage::class)->execute(['user'=>$user,'image'=>$this->nullOrValue($data,'image')]);
        return $user;
    }
}
