<?php

namespace App\Modules\Expense\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'period', 'expense_date', 'title', 'description', 'amount', 'is_profit', 'created_by'])]
class PosExpense extends Model
{
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    protected function casts(): array { return ['expense_date' => 'date', 'amount' => 'decimal:2', 'is_profit' => 'boolean']; }
}
