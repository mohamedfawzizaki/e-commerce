<?php

namespace App\Http\Requests\Shipping;

use App\Http\Responses\ApiResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class UpdateShippingAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'city_id'           => 'sometimes|exists:cities,id',
            'address_line1'     => 'sometimes|string|max:255',
            'address_line2'     => 'nullable|string|max:255',
            'postal_code'       => 'sometimes|string|max:20',
            'recipient_name'    => 'sometimes|string|max:100',
            'recipient_phone'   => 'sometimes|string|max:20',
            'company_name'      => 'nullable|string|max:100',
            'is_default'        => 'sometimes|boolean',
        ];
    }

    protected function failedValidation(ValidatorContract $validator): void
    {
        Log::warning("Validation failed for record update.", [
            'errors' => $validator->errors(),
        ]);

        throw new HttpResponseException(
            ApiResponse::error('Validation errors', 422, $validator->errors())
        );
    }
}