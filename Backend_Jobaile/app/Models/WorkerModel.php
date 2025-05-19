<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class WorkerModel extends Model
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $primaryKey = 'id_worker';

    public $incrementing = false; 

    protected $keyType = 'string';

    protected $table = 'worker_models';
    
    protected $fillable = [
        'id_user',
        'bio',
        'skill',
        'experience_years',
        'location',
        'expected_salary',
        'availability',
        'profile_picture'
    ];

    protected $casts = [
        'location' => 'array',
    ];

    protected static function booted()
    {
        parent::boot();

        static::creating(function ($worker) {
            if (empty($worker->id_worker)) { // Jika id_user kosong
                $worker->id_worker = Str::random(20); // Isi dengan string random sepanjang 20 karakter
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
