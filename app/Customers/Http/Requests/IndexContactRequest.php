<?php

namespace App\Customers\Http\Requests;

use App\Customers\Models\Contact;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Contact::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', 'string', 'max:32'],
        ];
    }

    public function search(): ?string
    {
        $search = trim($this->string('search')->toString());

        return $search === '' ? null : $search;
    }

    /**
     * All, the viewer's own contacts, or contacts with an open deal.
     * Anything else is treated as all.
     */
    public function filter(): string
    {
        $filter = $this->string('filter')->toString();

        return in_array($filter, ['mine', 'open'], true) ? $filter : 'all';
    }
}
