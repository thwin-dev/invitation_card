<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Sale\Models\Sale;
use App\Modules\Customer\Models\CustomerCreditRecord;
use App\Modules\Customer\Models\CustomerPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosCustomerWebController extends Controller
{
    public function index(Request $request): View
    {
        $customers = PosCustomer::query()->when($request->string('search')->trim()->value(), fn ($query, string $search) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))->orderBy('name')->paginate(15)->withQueryString();
        $editCustomer = $request->filled('edit') ? PosCustomer::query()->find($request->integer('edit')) : null;
        return view('management.customers.index', compact('customers', 'editCustomer'));
    }

    public function show(PosCustomer $customer): View
    {
        $canViewSales = request()->user('web')?->hasPermissionTo('sales', 'view') ?? false;
        $sales = $canViewSales
            ? Sale::query()->where('customer_id', $customer->id)->latest('sale_date')->limit(20)->get()
            : collect();
        $credits = CustomerCreditRecord::query()->with('sale')->where('customer_id', $customer->id)->latest()->get();
        $creditTotal = (float) $credits->whereNull('repayment_date')->sum('amount');
        $creditLimit = (float) $customer->credit_limit;
        $availableCredit = $customer->availableCredit();
        $payments = CustomerPayment::query()->with('credit')->where('customer_id', $customer->id)->latest('payment_date')->get();
        return view('management.customers.show', compact('customer', 'sales', 'credits', 'creditTotal', 'creditLimit', 'availableCredit', 'payments', 'canViewSales'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'allow_credit' => ['sometimes', 'boolean'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'customer_level' => ['required', 'integer', 'in:1,2,3'],
        ]);
        $data['allow_credit'] = $request->boolean('allow_credit');
        $data['credit_limit'] = $data['allow_credit'] ? (float) ($data['credit_limit'] ?? 0) : 0;
        PosCustomer::query()->create([...$data, 'created_by' => $request->user()->id]);
        return to_route('management.customers.index')->with('status', 'Customer created.');
    }

    public function update(Request $request, PosCustomer $customer): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'allow_credit' => ['sometimes', 'boolean'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'customer_level' => ['required', 'integer', 'in:1,2,3'],
        ]);
        $data['allow_credit'] = $request->boolean('allow_credit');
        $data['credit_limit'] = $data['allow_credit'] ? (float) ($data['credit_limit'] ?? 0) : 0;
        $customer->update($data);
        return to_route('management.customers.index')->with('status', 'Customer updated.');
    }

    public function updateLevel(Request $request, PosCustomer $customer): RedirectResponse
    {
        $customer->update($request->validate(['customer_level' => ['required', 'integer', 'in:1,2,3']]));
        return back()->with('status', 'Customer level updated.');
    }

    public function repayCredit(Request $request, CustomerCreditRecord $credit): RedirectResponse
    {
        $data = $request->validate(['repayment_date' => ['required', 'date']]);
        $credit->update(['repayment_date' => $data['repayment_date']]);
        return back()->with('status', 'Credit repayment recorded.');
    }

    public function storePayment(Request $request, PosCustomer $customer): RedirectResponse
    {
        $data = $request->validate(['credit_record_id' => ['nullable', 'exists:customer_credit_records,id'], 'amount' => ['required', 'numeric', 'gt:0'], 'payment_date' => ['required', 'date'], 'payment_method' => ['required', 'in:cash,bank,mobile'], 'note' => ['nullable', 'string', 'max:255']]);
        $credit = ! empty($data['credit_record_id']) ? CustomerCreditRecord::query()->where('customer_id', $customer->id)->whereNull('repayment_date')->findOrFail($data['credit_record_id']) : null;
        if ($credit && (float) $data['amount'] > (float) $credit->amount) {
            return back()->withErrors(['amount' => 'Payment cannot be greater than the selected credit balance.'])->withInput();
        }
        $payment = CustomerPayment::query()->create([...$data, 'customer_id' => $customer->id, 'received_by' => $request->user()->id]);
        if ($credit) {
            $remaining = (float) $credit->amount - (float) $data['amount'];
            $credit->update(['amount' => $remaining, 'repayment_date' => $remaining <= 0 ? $data['payment_date'] : null]);
            if ($credit->sale) $credit->sale->update(['credit_amount' => $remaining, 'paid_amount' => (float) $credit->sale->total_price - $remaining, 'repayment_date' => $remaining <= 0 ? $data['payment_date'] : null]);
        }
        return to_route('management.customer-payments.receipt', [$customer, $payment])->with('status', 'Customer payment recorded.');
    }

    public function paymentReceipt(PosCustomer $customer, CustomerPayment $payment): View
    {
        abort_unless($payment->customer_id === $customer->id, 404);
        return view('management.customers.payment-receipt', compact('customer', 'payment'));
    }

    public function statement(Request $request, PosCustomer $customer): View
    {
        $from = $request->date('from')?->startOfDay(); $to = $request->date('to')?->endOfDay();
        $sales = Sale::query()->where('customer_id', $customer->id)->when($from, fn ($q) => $q->whereDate('sale_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('sale_date', '<=', $to))->latest('sale_date')->get();
        $payments = CustomerPayment::query()->where('customer_id', $customer->id)->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))->latest('payment_date')->get();
        $creditTotal = (float) $sales->where('status', 'completed')->sum('credit_amount'); $paymentTotal = (float) $payments->sum('amount'); $remaining = max(0, $creditTotal - $paymentTotal);
        return view('management.customers.statement', compact('customer', 'sales', 'payments', 'creditTotal', 'paymentTotal', 'remaining'));
    }

    public function destroy(PosCustomer $customer): RedirectResponse { $customer->delete(); return to_route('management.customers.index')->with('status', 'Customer deleted.'); }
}
