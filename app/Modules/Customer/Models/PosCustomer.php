<?php

namespace App\Modules\Customer\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'address', 'allow_credit', 'credit_limit', 'customer_level', 'created_by'])]
class PosCustomer extends Model
{
    use SoftDeletes;

    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function credits(): HasMany { return $this->hasMany(CustomerCreditRecord::class, 'customer_id'); }
    public function payments(): HasMany { return $this->hasMany(CustomerPayment::class, 'customer_id'); }

    protected function casts(): array
    {
        return [
            'allow_credit' => 'boolean',
            'credit_limit' => 'decimal:2',
            'customer_level' => 'integer',
        ];
    }

    public function outstandingDebt(): float
    {
        return (float) $this->credits()->whereNull('repayment_date')->sum('amount');
    }

    public function availableCredit(): float
    {
        if (! $this->allow_credit) {
            return 0.0;
        }
        $limit = (float) $this->credit_limit;
        if ($limit <= 0) {
            return 999999999.0;
        }
        return max(0.0, $limit - $this->outstandingDebt());
    }
}
