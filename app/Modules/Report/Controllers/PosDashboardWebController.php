<?php

namespace App\Modules\Report\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosDashboardWebController extends Controller
{
    public function report(Request $request): View
    {
        return view('management.reports.financial', [
            'activeReport' => in_array($request->query('tab'), ['branches', 'discounts'], true) ? $request->query('tab') : 'financial',
        ]);
    }

    public function branchReport(Request $request): RedirectResponse
    {
        return to_route('management.reports.financial', [
            'tab' => 'branches',
            ...$request->only(['from', 'to', 'branch_id']),
        ]);
    }

    public function salesReport(Request $request): View
    {
        return view('management.reports.sales');
    }

    public function purchasesReport(Request $request): View
    {
        return view('management.reports.purchases');
    }

    public function inventoryValuationReport(Request $request): View
    {
        return view('management.reports.inventory-valuation');
    }


    public function index(): View
    {
        return view('management.dashboard');
    }
}
