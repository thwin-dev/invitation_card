<?php

namespace App\Modules\Customer\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Customer\Models\PosCustomer;
use App\Modules\Customer\Services\CustomerExcelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PosCustomerWebController extends Controller
{
    public function index(Request $request): View
    {
        $customers = PosCustomer::query()
            ->when($request->string('search')->trim()->value(), fn ($query, string $search) =>
                $query->where(fn ($query) =>
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                )
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $editCustomer = $request->filled('edit') ? PosCustomer::query()->find($request->integer('edit')) : null;

        return view('management.customers.index', compact('customers', 'editCustomer'));
    }

    public function sampleExcel(CustomerExcelService $service): StreamedResponse
    {
        return $service->downloadSample();
    }

    public function import(Request $request, CustomerExcelService $service): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $result = $service->import($request->file('file'), $request->user()->id);

        $msg = "Imported {$result['imported']} exhibitor(s).";
        if ($result['skipped'] > 0) {
            $msg .= " Skipped {$result['skipped']} invalid row(s).";
        }

        $redirectRoute = $request->routeIs('management.customers.*') ? 'management.customers.index' : 'management.exhibitors.index';
        return to_route($redirectRoute)->with('status', $msg);
    }

    public function show(PosCustomer $customer): View
    {
        return view('management.customers.show', compact('customer'));
    }

    public function invitationCard(PosCustomer $customer): View
    {
        return view('management.customers.invitation-card', compact('customer'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'customer_level' => ['required', 'integer', 'in:1,2,3'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'qr_code' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('customers/logos', 'public');
        }
        if ($request->hasFile('qr_code')) {
            $data['qr_code_path'] = $request->file('qr_code')->store('customers/qr_codes', 'public');
        }

        unset($data['logo'], $data['qr_code']);

        PosCustomer::query()->create([...$data, 'created_by' => $request->user()->id]);

        $redirectRoute = $request->routeIs('management.customers.*') ? 'management.customers.index' : 'management.exhibitors.index';
        return to_route($redirectRoute)->with('status', 'Exhibitor created.');
    }

    public function update(Request $request, PosCustomer $customer): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'customer_level' => ['required', 'integer', 'in:1,2,3'],
            'logo' => ['nullable', 'image', 'max:5120'],
            'qr_code' => ['nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('logo')) {
            if ($customer->logo_path && Storage::disk('public')->exists($customer->logo_path)) {
                Storage::disk('public')->delete($customer->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('customers/logos', 'public');
        }

        if ($request->hasFile('qr_code')) {
            if ($customer->qr_code_path && Storage::disk('public')->exists($customer->qr_code_path)) {
                Storage::disk('public')->delete($customer->qr_code_path);
            }
            $data['qr_code_path'] = $request->file('qr_code')->store('customers/qr_codes', 'public');
        }

        unset($data['logo'], $data['qr_code']);

        $customer->update($data);

        $redirectRoute = $request->routeIs('management.customers.*') ? 'management.customers.index' : 'management.exhibitors.index';
        return to_route($redirectRoute)->with('status', 'Exhibitor updated.');
    }

    public function updateLevel(Request $request, PosCustomer $customer): RedirectResponse
    {
        $customer->update($request->validate(['customer_level' => ['required', 'integer', 'in:1,2,3']]));

        return back()->with('status', 'Exhibitor level updated.');
    }

    public function destroy(PosCustomer $customer): RedirectResponse
    {
        if ($customer->logo_path && Storage::disk('public')->exists($customer->logo_path)) {
            Storage::disk('public')->delete($customer->logo_path);
        }
        if ($customer->qr_code_path && Storage::disk('public')->exists($customer->qr_code_path)) {
            Storage::disk('public')->delete($customer->qr_code_path);
        }

        $customer->delete();

        $redirectRoute = request()->routeIs('management.customers.*') ? 'management.customers.index' : 'management.exhibitors.index';
        return to_route($redirectRoute)->with('status', 'Exhibitor deleted.');
    }
}
