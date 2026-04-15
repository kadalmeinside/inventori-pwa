<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Policy handled in controller
    }

    public function rules(): array
    {
        return [
            'source_warehouse_id'      => ['required', 'integer', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:source_warehouse_id'],
            'product_id'               => ['required', 'integer', 'exists:products,id'],
            'quantity'                 => ['required', 'integer', 'min:1'],
            'notes'                    => ['nullable', 'string', 'max:500'],
        ];
    }
}
