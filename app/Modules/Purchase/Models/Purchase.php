<?php

namespace App\Modules\Purchase\Models;

use App\Models\User;
use App\Modules\Inventory\Models\ProductUnit;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['supplier_id', 'supplier_name', 'total_quantity', 'total_price', 'paid_amount', 'payable_amount', 'payment_status', 'payment_due_date', 'purchase_date', 'purchase_by'])]
class Purchase extends Model
{
    public function supplier(): BelongsTo { return $this->belongsTo(PosSupplier::class, 'supplier_id'); }
    public function buyer(): BelongsTo { return $this->belongsTo(User::class, 'purchase_by'); }
    public function items(): HasMany { return $this->hasMany(PurchaseItem::class); }
    public function payments(): HasMany { return $this->hasMany(SupplierPayment::class, 'purchase_id'); }

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'payment_due_date' => 'date',
            'total_quantity' => 'integer',
            'total_price' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'payable_amount' => 'decimal:2',
        ];
    }
}
