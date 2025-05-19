<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Job_OfferModel extends Model
{
    use HasFactory;

    protected $table = 'job__offer_models';

    protected $primaryKey = 'id_job';

    public $incrementing = true;  // Karena id_job auto increment integer

    protected $keyType = 'int';

    protected $fillable = [
        'id_recruiter',
        'job_title',
        'desc',
        'status',
    ];

     // Relasi ke recruiter (user yang posting job)
     public function recruiter()
     {
         return $this->belongsTo(RecruiterModel::class, 'id_recruiter', 'id_recruiter');
     }
 
     // Relasi ke matchmaking (job ditawarkan ke worker)
     public function matchmakings()
     {
         return $this->hasMany(MatchmakingModel::class, 'id_job', 'id_job');
     }
 
     // Relasi ke kontrak yang dibuat berdasarkan job ini
     public function contracts()
     {
         return $this->hasMany(ContractModel::class, 'id_job', 'id_job');
     }
}
