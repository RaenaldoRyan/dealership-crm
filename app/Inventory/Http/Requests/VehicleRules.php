<?php

namespace App\Inventory\Http\Requests;

use App\Customers\Models\Contact;
use App\Inventory\Enums\FuelType;
use App\Inventory\Enums\Transmission;
use App\Inventory\Models\Vehicle;
use App\Inventory\Support\Registration;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleRules
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function rules(?Vehicle $ignore = null, bool $includeContact = false): array
    {
        $unique = Rule::unique(Vehicle::class, 'registration');

        if ($ignore instanceof Vehicle) {
            $unique->ignore($ignore);
        }

        $rules = [
            'make' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:1900', 'max:'.(now()->year + 1)],
            'registration' => ['bail', 'required', 'string', 'max:32', self::plate(), $unique],
            'price' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'colour' => ['nullable', 'string', 'max:255'],
            'fuel_type' => ['nullable', Rule::enum(FuelType::class)],
            'transmission' => ['nullable', Rule::enum(Transmission::class)],
            'mileage' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'vin' => ['nullable', 'string', 'size:17', 'alpha_num'],
        ];

        if ($includeContact) {
            $rules['contact_id'] = ['nullable', 'integer', Rule::exists(Contact::class, 'id')];
        }

        return $rules;
    }

    public static function prepare(FormRequest $request): void
    {
        $merged = [];

        if ($request->exists('registration') && is_string($request->input('registration'))) {
            $merged['registration'] = Registration::normalize($request->string('registration')->toString());
        }

        foreach (['colour', 'fuel_type', 'transmission', 'mileage', 'vin', 'contact_id'] as $field) {
            if ($request->exists($field) && $request->input($field) === '') {
                $merged[$field] = null;
            }
        }

        if (is_string($request->input('mileage')) && $request->input('mileage') !== '') {
            $merged['mileage'] = str_replace([',', ' '], '', $request->string('mileage')->toString());
        }

        if (is_string($request->input('price')) && $request->input('price') !== '') {
            $merged['price'] = str_replace(['£', ',', ' '], '', $request->string('price')->toString());
        }

        if (is_string($request->input('vin')) && $request->input('vin') !== '') {
            $merged['vin'] = strtoupper((string) preg_replace('/\s+/', '', $request->string('vin')->toString()));
        }

        if ($merged !== []) {
            $request->merge($merged);
        }
    }

    private static function plate(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            if (! is_string($value) || ! Registration::isValid($value)) {
                $fail('Enter a UK registration, such as AB22 CDE.');
            }
        };
    }
}
