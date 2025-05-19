<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use App\Models\User;

class RecruiterModel extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $primaryKey = 'id_recruiter';

    public $incrementing = false; 

    protected $keyType = 'string';

    protected $table = 'recruiter_models';
    
    protected $fillable = [
        'id_user',
        'house_type',
        'family_size',
        'location_address',
        'desc',
        'profile_picture'
    ];

    protected static function booted()
    {
        parent::boot();

        static::creating(function ($recruiter) {
            if (empty($recruiter->id_recruiter)) { // Jika id_user kosong
                $recruiter->id_recruiter = Str::random(20); // Isi dengan string random sepanjang 20 karakter
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    public function jobs()
    {
        return $this->hasMany(Job_OfferModel::class, 'id_recruiter', 'id_recruiter');
    }

    public function matchings()
    {
        return $this->hasMany(MatchmakingModel::class, 'id_recruiter', 'id_recruiter');
    }

    public function contracts()
    {
        return $this->hasMany(ContractModel::class, 'id_recruiter', 'id_recruiter');
    }

}
