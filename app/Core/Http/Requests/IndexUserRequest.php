<?php

namespace App\Core\Http\Requests;

use App\Core\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexUserRequest extends FormRequest
{
    /**
     * Only managers can open the staff list.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', User::class) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * "new" opens the add panel. A numeric id opens that account. Anything else stays closed.
     */
    public function panel(): ?string
    {
        $panel = $this->validated('user');

        if (! is_string($panel) || $panel === '') {
            return null;
        }

        if ($panel === 'new' || ctype_digit($panel)) {
            return $panel;
        }

        return null;
    }
}
