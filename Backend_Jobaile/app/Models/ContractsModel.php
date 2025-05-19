<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContractsModel extends Model
{
    use HasFactory;

    protected $table = 'contracts_models';

    protected $primaryKey = 'id_contract';

    public $incrementing = true;  // Karena id_contract auto increment integer

    protected $keyType = 'int';

    protected $fillable = [
        'id_worker',
        'id_recruiter',
        'id_job',
        'start_date',
        'end_date',
        'terms',
        'sign_at',
    ];

    protected $dates = ['start_date', 'end_date', 'sign_at'];

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
