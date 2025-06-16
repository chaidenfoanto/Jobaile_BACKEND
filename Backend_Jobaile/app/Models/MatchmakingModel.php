<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class MatchmakingModel extends Model
{
    use HasFactory;

    protected $table = 'matchmaking_models';

    protected $primaryKey = 'id_match';

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id_worker',
        'id_recruiter',
        'id_job',
        'status',
        'matched_at',
    ];

    protected static function booted()
    {
        static::creating(function ($match) {
            if (empty($match->id_match)) {
                $match->id_match = Str::random(20);
            }
        });
    }

    // Relasi
    public function worker()
    {
        return $this->belongsTo(WorkerModel::class, 'id_worker', 'id_worker');
    }

    public function recruiter()
    {
        return $this->belongsTo(RecruiterModel::class, 'id_recruiter', 'id_recruiter');
    }

    public function job()
    {
        return $this->belongsTo(Job_OfferModel::class, 'id_job', 'id_job');
    }
}
