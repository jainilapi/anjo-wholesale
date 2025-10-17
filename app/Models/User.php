<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $guarded = [];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function setPasswordAttribute($value)
    {
       $this->attributes['password'] = bcrypt($value);
    }   
    
    public function getUserProfileAttribute() {
        if (!empty(trim($this->profile)) && file_exists(public_path("storage/users/profiles/{$this->profile}")) && is_file(public_path("storage/users/profiles/{$this->profile}"))) {
            return asset("storage/users/profiles/{$this->profile}");
        }

        return asset('assets/images/profile.jpg');
    }

    public static function isAdmin() {
        return boolval(auth()->user()->roles->where('slug', 'admin')->count());
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function locations()
    {
        return $this->hasMany(Location::class, 'customer_id');
    }
}
