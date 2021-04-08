<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Ecommerces extends Model
{
    protected $table = 'ecommerces';
    protected $primaryKey = 'id';
    protected $fillable = ['order_id', 'market', 'customer_id', 'status', 'bayar'];
}
