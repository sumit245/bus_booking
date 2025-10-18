<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Assuming any authenticated admin can perform this action.
        // You can add more specific authorization logic here if needed.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'coupon_name' => 'required|string|max:255',
            'coupon_threshold' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percentage',
            'coupon_value' => [
                'required',
                'numeric',
                'min:0',
                // If discount_type is 'percentage', value cannot be more than 100.
                function ($attribute, $value, $fail) {
                    if ($this->input('discount_type') === 'percentage' && $value > 100) {
                        $fail('The ' . $attribute . ' cannot be more than 100% for a percentage discount.');
                    }
                },
            ],
            'expiry_date' => 'required|date|after_or_equal:today',
            'banner_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'sticker_image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }
}
