<?php

namespace App\Models;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;


        protected $primaryKey = 'user_id'; 
        protected $keyType = 'string'; 
        protected $table = 'users';
    
        // Define fillable attributes for mass assignment
        protected $fillable = [
            'user_id',
            'first_name',
            'last_name',
            'email',
            'password',
            'phone_num',
            'address',
            'lang_profile',
            'role_id',
        ];
    
        // Hidden attributes
        protected $hidden = [
            'password',
            'created_at',
            'updated_at',
        ];
    
        // Define any relationships if necessary
        public function role()
        {
            return $this->belongsTo(Role::class, 'role_id'); // Assuming you have a Role model
        }

    
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }

}