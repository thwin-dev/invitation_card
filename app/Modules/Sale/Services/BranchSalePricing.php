<?php

namespace App\Modules\Sale\Services;

use App\Modules\Inventory\Models\InventoryBranch;
use App\Modules\Inventory\Models\ProductUnit;
use App\Modules\Inventory\Models\BranchUnitPrice;

class BranchSalePricing
{
    public function price(InventoryBranch $branch, ProductUnit $unit, string $priceType = 'normal_sale_price'): float
    {
        $priceType = $priceType === 'whole_sale_price' ? $priceType : 'normal_sale_price';
        $override = BranchUnitPrice::query()
            ->where('branch_id', $branch->id)
            ->where('product_unit_id', $unit->id)
            ->value($priceType);

        return (float) ($override ?? $unit->{$priceType});
    }

    public function receiptSnapshot(InventoryBranch $branch): array
    {
        return [
            'branch_name_snapshot' => $branch->name,
            'branch_phone_snapshot' => $branch->phone,
            'branch_address_snapshot' => $branch->address,
            'currency_code_snapshot' => $branch->currency_code ?: 'MMK',
            'receipt_header_snapshot' => $branch->receipt_header,
            'receipt_footer_snapshot' => $branch->receipt_footer,
        ];
    }
}
