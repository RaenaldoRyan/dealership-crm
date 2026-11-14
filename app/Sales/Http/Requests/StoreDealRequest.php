<?php

namespace App\Sales\Http\Requests;

use App\Core\Models\User;
use App\Customers\Models\Contact;
use App\Inventory\Models\Vehicle;
use App\Sales\Enums\DealStage;
use App\Sales\Models\Deal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDealRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * A missing or unknown contact is left for validation. A contact the
     * user cannot manage is refused here.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user instanceof User || ! $user->can('create', Deal::class)) {
            return false;
        }

        $contactId = $this->input('contact_id');

        if (! $this->isContactId($contactId)) {
            return true;
        }

        $contact = Contact::query()->find((int) $contactId);

        if (! $contact instanceof Contact) {
            return true;
        }

        return $user->can('update', $contact);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contact_id' => ['required', 'integer', Rule::exists(Contact::class, 'id')],
            'vehicle_id' => ['nullable', 'integer', Rule::exists(Vehicle::class, 'id')],
            'value' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:99999999.99'],
            'stage' => ['required', Rule::enum(DealStage::class)],
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
            'contact_id' => 'contact',
            'vehicle_id' => 'vehicle',
        ];
    }

    /**
     * Require the chosen vehicle to belong to the selected contact.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['contact_id', 'vehicle_id']) || $this->input('vehicle_id') === null) {
                    return;
                }

                $contact = Contact::query()->find($this->integer('contact_id'));

                if (! $contact instanceof Contact) {
                    return;
                }

                $vehicleBelongsToContact = $contact->vehicles()
                    ->whereKey($this->integer('vehicle_id'))
                    ->exists();

                if (! $vehicleBelongsToContact) {
                    $validator->errors()->add('vehicle_id', 'The vehicle must belong to the selected contact.');
                }
            },
        ];
    }

    /**
     * A contact id is present when it can be looked up as an integer.
     */
    private function isContactId(mixed $contactId): bool
    {
        if (is_int($contactId)) {
            return true;
        }

        return is_string($contactId) && ctype_digit($contactId);
    }
}
