<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\Tenantable;

class StockMovement extends Model
{
    use Tenantable;

    use HasFactory;

    protected $fillable = [
        'product_id',
        'receipt_id',
        'user_id',
        'type',
        'quantity',
        'balance',
        'notes',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function receipt()
    {
        return $this->belongsTo(Receipt::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
