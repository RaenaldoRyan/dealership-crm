<?php

namespace App\Inventory\Http\Requests;

use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $contact instanceof Contact
            && ($this->user()?->can('create', [Vehicle::class, $contact]) ?? false);
    }

    protected function prepareForValidation(): void
    {
        VehicleRules::prepare($this);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return VehicleRules::rules();
    }
}
