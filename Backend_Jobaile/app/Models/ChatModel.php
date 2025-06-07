<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatModel extends Model
{
    protected $primaryKey = 'id_chat';

    public $timestamps = false;

    protected $fillable = [
        'id_sender',
        'id_receiver',
        'message',
        'send_at',
    ];

    protected $dates = ['send_at'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'id_sender', 'id_user');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'id_receiver', 'id_user');
    }
}
