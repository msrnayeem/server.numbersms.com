<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'transactions';

    protected $fillable = [
        'user_id',
        'username',
        'getway',
        'amount',
        'shared_for',
        'shared_id',
        'pay_id',
        'status'
    ];
}
