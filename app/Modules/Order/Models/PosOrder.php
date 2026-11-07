<?php

namespace App\Modules\Order\Models;

use App\Models\User;
use App\Modules\Customer\Models\PosCustomer;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_number', 'customer_id', 'address', 'phone', 'order_date', 'delivered_date', 'total_quantity', 'estimated_price', 'status', 'delivery_by'])]
class PosOrder extends Model
{
    use SoftDeletes;
    public function customer(): BelongsTo { return $this->belongsTo(PosCustomer::class); }
    public function deliveryBy(): BelongsTo { return $this->belongsTo(User::class, 'delivery_by'); }
    public function items(): HasMany { return $this->hasMany(PosOrderItem::class, 'order_id'); }
    protected function casts(): array { return ['order_date' => 'datetime', 'delivered_date' => 'datetime', 'estimated_price' => 'decimal:2', 'total_quantity' => 'integer']; }
}
