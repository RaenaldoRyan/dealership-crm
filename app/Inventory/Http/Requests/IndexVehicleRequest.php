<?php

namespace App\Inventory\Http\Requests;

use App\Inventory\Enums\VehicleStatus;
use App\Inventory\Models\Vehicle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexVehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Vehicle::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function search(): ?string
    {
        $search = trim($this->string('search')->toString());

        return $search === '' ? null : $search;
    }

    public function status(): ?VehicleStatus
    {
        return VehicleStatus::tryFrom($this->string('status')->toString());
    }
}
