<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $warehouseId = $this->input('warehouse_id');

        // Branch Admin can only stock out from their own warehouse
        if ($user->role->value === 'branch_admin' && $user->warehouse_id != $warehouseId) {
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_id'   => ['required', 'integer', 'exists:products,id'],
            'quantity'     => ['required', 'integer', 'min:1'],
            'category'     => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\StockOutCategory::class)],
            'reason'       => ['nullable', 'string', 'max:500'],
        ];
    }
}
