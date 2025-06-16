<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DanaModel extends Model
{
    use HasFactory;

    protected $table = 'dana_models';

    protected $primaryKey = 'id_payments';

    public $incrementing = true;  // Karena id_job auto increment integer

    protected $keyType = 'int';

    protected $fillable = [
        'id_contract',
        'merchant_trans_id',
        'acquirement_id',
        'status'
    ];

    public function contract()
    {
        return $this->belongsTo(ContractsModel::class, 'id_contract');
    }
}
