<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;

class RatingReviewModel extends Model
{
    use HasFactory;

    protected $table = 'rating_review_models';

    public $incrementing = false; // Karena bukan auto-increment
    public $timestamps = false;   // Kalau tidak pakai created_at dan updated_at
    protected $primaryKey = null;
    
    protected $fillable = [
        'id_reviewer',
        'id_reviewed',
        'ulasan',
        'rating',
        'tanggal_rating',
        'role',
    ];

    protected $casts = [
        'tanggal_rating' => 'datetime',
    ];
    

    // Reviewer (User)
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'id_reviewer', 'id_user');
    }

    // Reviewed (User)
    public function reviewed()
    {
        return $this->belongsTo(User::class, 'id_reviewed', 'id_user');
    }
}
