<?php

namespace App\Modules\Customer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['customer_id', 'credit_record_id', 'received_by', 'amount', 'payment_date', 'payment_method', 'note'])]
class CustomerPayment extends Model
{
    public function customer(): BelongsTo { return $this->belongsTo(PosCustomer::class); }
    public function credit(): BelongsTo { return $this->belongsTo(CustomerCreditRecord::class, 'credit_record_id'); }
    public function receiver(): BelongsTo { return $this->belongsTo(User::class, 'received_by'); }
    protected function casts(): array { return ['amount' => 'decimal:2', 'payment_date' => 'date']; }
}
