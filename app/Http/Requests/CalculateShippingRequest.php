<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * CalculateShippingRequest
 * 
 * Form Request for validating shipping calculation input.
 * Validates product dimensions and weight for shipping charge calculation.
 */
class CalculateShippingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true; // Public API endpoint
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'length' => [
                'required',
                'numeric',
                'min:0.01',
                'max:1000', // 10 meters max in cm
            ],
            'width' => [
                'required',
                'numeric',
                'min:0.01',
                'max:1000',
            ],
            'height' => [
                'required',
                'numeric',
                'min:0.01',
                'max:1000',
            ],
            'actual_weight' => [
                'required',
                'numeric',
                'min:0.01',
                'max:1000', // 1000 kg max
            ],
            'shipping_method_id' => [
                'nullable',
                'integer',
                'exists:shipping_methods,id',
            ],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'length.required' => 'Product length is required.',
            'length.numeric' => 'Length must be a valid number.',
            'length.min' => 'Length must be at least 0.01 cm.',
            'length.max' => 'Length cannot exceed 1000 cm.',

            'width.required' => 'Product width is required.',
            'width.numeric' => 'Width must be a valid number.',
            'width.min' => 'Width must be at least 0.01 cm.',
            'width.max' => 'Width cannot exceed 1000 cm.',

            'height.required' => 'Product height is required.',
            'height.numeric' => 'Height must be a valid number.',
            'height.min' => 'Height must be at least 0.01 cm.',
            'height.max' => 'Height cannot exceed 1000 cm.',

            'actual_weight.required' => 'Actual weight is required.',
            'actual_weight.numeric' => 'Actual weight must be a valid number.',
            'actual_weight.min' => 'Actual weight must be at least 0.01 kg.',
            'actual_weight.max' => 'Actual weight cannot exceed 1000 kg.',

            'shipping_method_id.integer' => 'Invalid shipping method selected.',
            'shipping_method_id.exists' => 'Selected shipping method does not exist.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'length' => 'product length',
            'width' => 'product width',
            'height' => 'product height',
            'actual_weight' => 'actual weight',
            'shipping_method_id' => 'shipping method',
        ];
    }

    /**
     * Handle a failed validation attempt.
     * Returns JSON response for AJAX requests.
     *
     * @param Validator $validator
     * @throws HttpResponseException
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422)
        );
    }
}
