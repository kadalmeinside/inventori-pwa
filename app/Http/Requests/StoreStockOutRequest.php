<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy handled in controller
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
