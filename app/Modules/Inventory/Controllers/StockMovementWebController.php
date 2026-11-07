<?php

namespace App\Modules\Inventory\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\View\View;

class StockMovementWebController extends Controller
{
    public function index(): View
    {
        $movements = StockMovement::query()->with(['unit.product', 'user'])->latest()->paginate(30)->withQueryString();
        return view('management.stock.movements', compact('movements'));
    }
}
