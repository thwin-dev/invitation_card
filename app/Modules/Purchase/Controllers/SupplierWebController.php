<?php

namespace App\Modules\Purchase\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchase\Models\PosSupplier;
use App\Modules\Purchase\Models\Purchase;
use App\Modules\Purchase\Models\SupplierPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierWebController extends Controller
{
    public function index(): View
    {
        return view('management.suppliers.index');
    }

    public function show(PosSupplier $supplier): View
    {
        $purchases = Purchase::query()
            ->where('supplier_id', $supplier->id)
            ->latest('purchase_date')
            ->get();

        $payments = SupplierPayment::query()
            ->with(['purchase', 'payer'])
            ->where('supplier_id', $supplier->id)
            ->latest('payment_date')
            ->get();

        $totalPurchased = (float) $purchases->sum('total_price');
        $totalPaid = (float) $purchases->sum('paid_amount');
        $payableBalance = (float) $purchases->sum('payable_amount');
        $unpaidPurchases = $purchases->where('payable_amount', '>', 0);

        return view('management.suppliers.show', compact(
            'supplier',
            'purchases',
            'payments',
            'totalPurchased',
            'totalPaid',
            'payableBalance',
            'unpaidPurchases'
        ));
    }

    public function storePayment(Request $request, PosSupplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'purchase_id' => ['nullable', 'exists:purchases,id'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'in:cash,bank,mobile'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $supplier, $request): void {
            $purchase = null;
            $remainingToAllocate = (float) $data['amount'];

            if (! empty($data['purchase_id'])) {
                $purchase = Purchase::query()
                    ->where('supplier_id', $supplier->id)
                    ->lockForUpdate()
                    ->findOrFail($data['purchase_id']);

                abort_if(
                    $remainingToAllocate > (float) $purchase->payable_amount,
                    422,
                    'Payment amount cannot exceed the purchase payable balance of ' . number_format((float) $purchase->payable_amount, 2)
                );

                $newPayable = max(0, (float) $purchase->payable_amount - $remainingToAllocate);
                $newPaid = (float) $purchase->paid_amount + $remainingToAllocate;
                $purchase->update([
                    'paid_amount' => $newPaid,
                    'payable_amount' => $newPayable,
                    'payment_status' => $newPayable <= 0 ? 'paid' : 'partial',
                ]);
            } else {
                $openPurchases = Purchase::query()
                    ->where('supplier_id', $supplier->id)
                    ->where('payable_amount', '>', 0)
                    ->orderBy('purchase_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->lockForUpdate()
                    ->get();

                $totalPayable = (float) $openPurchases->sum('payable_amount');
                if ($totalPayable > 0) {
                    abort_if(
                        $remainingToAllocate > $totalPayable,
                        422,
                        'Payment amount cannot exceed the total outstanding payable balance of ' . number_format($totalPayable, 2)
                    );

                    foreach ($openPurchases as $openPurchase) {
                        if ($remainingToAllocate <= 0) {
                            break;
                        }

                        $payable = (float) $openPurchase->payable_amount;
                        $alloc = min($remainingToAllocate, $payable);

                        $newPayable = max(0, $payable - $alloc);
                        $newPaid = (float) $openPurchase->paid_amount + $alloc;

                        $openPurchase->update([
                            'paid_amount' => $newPaid,
                            'payable_amount' => $newPayable,
                            'payment_status' => $newPayable <= 0 ? 'paid' : 'partial',
                        ]);

                        $remainingToAllocate -= $alloc;
                    }
                }
            }

            SupplierPayment::query()->create([
                'supplier_id' => $supplier->id,
                'purchase_id' => $purchase?->id,
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'note' => $data['note'] ?? null,
                'paid_by' => $request->user()->id,
            ]);
        });

        return back()->with('status', 'Payment to supplier recorded successfully.');
    }

    public function destroy(PosSupplier $supplier): RedirectResponse
    {
        $supplier->delete();
        return to_route('management.suppliers.index')->with('status', 'Supplier deleted.');
    }
}
