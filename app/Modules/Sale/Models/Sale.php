<?php

namespace App\Modules\Sale\Models;

use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Inventory\Models\InventoryBranch;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['voucher_code', 'customer_id', 'sale_by', 'branch_id', 'total_quantity', 'branch_name_snapshot', 'branch_phone_snapshot', 'branch_address_snapshot', 'currency_code_snapshot', 'receipt_header_snapshot', 'receipt_footer_snapshot', 'total_price', 'subtotal', 'discount_amount', 'paid_amount', 'credit_amount', 'repayment_date', 'sale_date', 'status', 'client_transaction_id', 'is_offline_sync', 'offline_created_at'])]
class Sale extends Model
{
    use SoftDeletes;

    public function customer(): BelongsTo
    {
        return $this->belongsTo(PosCustomer::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sale_by');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(InventoryBranch::class, 'branch_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    protected function casts(): array
    {
        return [
            'sale_date' => 'date',
            'repayment_date' => 'date',
            'offline_created_at' => 'datetime',
            'is_offline_sync' => 'boolean',
            'total_quantity' => 'integer',
            'total_price' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
        ];
    }
}
