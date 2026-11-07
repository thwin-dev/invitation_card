<?php

namespace App\Modules\Customer\Models;

use App\Modules\Sale\Models\Sale;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'sale_id', 'amount', 'repayment_date'])]
class CustomerCreditRecord extends Model
{
    public function customer(): BelongsTo { return $this->belongsTo(PosCustomer::class); }
    public function sale(): BelongsTo { return $this->belongsTo(Sale::class); }
    protected function casts(): array { return ['amount' => 'decimal:2', 'repayment_date' => 'date']; }
}
