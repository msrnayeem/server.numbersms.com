<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Services extends Model
{
    // table
    protected $table = 'services';

    protected $fillable = [
        'service',
        'price',
        'selling_price',
        'capacity',
        'image'
    ];
}
