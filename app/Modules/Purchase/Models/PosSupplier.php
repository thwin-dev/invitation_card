<?php

namespace App\Modules\Purchase\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'company_name', 'phone', 'address', 'created_by'])]
class PosSupplier extends Model
{
    use SoftDeletes;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'supplier_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_id');
    }

    public function totalPurchased(): float
    {
        return (float) $this->purchases()->sum('total_price');
    }

    public function totalPaid(): float
    {
        return (float) $this->purchases()->sum('paid_amount');
    }

    public function outstandingPayable(): float
    {
        return (float) $this->purchases()->sum('payable_amount');
    }
}
