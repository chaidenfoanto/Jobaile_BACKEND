<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use App\Models\WorkerModel;
use App\Models\RecruiterModel;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */

    protected $primaryKey = 'id_user';

    

    public $incrementing = false; 
 
    protected $keyType = 'string';
    protected $fillable = [
        'fullname',
        'email',
        'password',
        'phone',
        'gender',
        'birthdate',
        'ktp_card_path',
        'role'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->id_user)) { // Jika id_user kosong
                $user->id_user = Str::random(20); // Isi dengan string random sepanjang 20 karakter
            }
        });
    }

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

    public function workerProfile()
    {
        return $this->hasOne(WorkerProfile::class, 'id_user', 'id_user');
    }

    /**
     * Get the recruiter profile associated with the user.
     */
    public function recruiterProfile()
    {
        return $this->hasOne(RecruiterProfile::class, 'id_user', 'id_user');
    }

    /**
     * Get the ratings/reviews given by the user.
     */
    public function givenReviews()
    {
        return $this->hasMany(RatingReviewModel::class, 'id_reviewer', 'id_user');
    }

    /**
     * Get the ratings/reviews received by the user.
     */
    public function receivedReviews()
    {
        return $this->hasMany(RatingReviewModel::class, 'id_reviewed', 'id_user');
    }

    /**
     * Get the chats sent by the user.
     */
    public function sentChats()
    {
        return $this->hasMany(ChatModel::class, 'id_sender', 'id_user');
    }

    /**
     * Get the chats received by the user.
     */
    public function receivedChats()
    {
        return $this->hasMany(ChatModel::class, 'id_receiver', 'id_user');
    }
}
