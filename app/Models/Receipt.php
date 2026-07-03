<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\Tenantable;

class Receipt extends Model
{
    use Tenantable;

    protected $fillable = [
        'store_id',
        'company_id',
        'receipt_number',
        'image_path',
        'raw_text',
        'store_name',
        'transaction_date',
        'total_amount',
        'status',
        'type',
        'payment_status',
        'validated_by',
        'validated_at',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function items()
    {
        return $this->hasMany(ReceiptItem::class);
    }

    public function debt()
    {
        return $this->hasOne(Debt::class);
    }
}
