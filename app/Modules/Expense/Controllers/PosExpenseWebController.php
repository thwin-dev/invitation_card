<?php

namespace App\Modules\Expense\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Expense\Models\PosExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosExpenseWebController extends Controller
{
    public function index(Request $request): View
    {
        $total = PosExpense::query()->where('is_profit', false)->sum('amount');
        return view('management.expenses.index', compact('total'));
    }

    public function store(Request $request): RedirectResponse
    {
        PosExpense::query()->create([...$request->validate(['type' => ['required', 'string', 'max:255'], 'period' => ['nullable', 'string', 'max:255'], 'expense_date' => ['required', 'date'], 'title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string'], 'amount' => ['required', 'numeric', 'min:0'], 'is_profit' => ['sometimes', 'boolean']]), 'created_by' => $request->user()->id]);
        return to_route('management.expenses.index')->with('status', 'Expense recorded.');
    }
}
