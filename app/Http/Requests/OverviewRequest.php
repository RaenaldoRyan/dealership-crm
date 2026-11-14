<?php

namespace App\Http\Requests;

use App\Core\Enums\Role;
use App\Core\Models\User;
use App\Sales\Models\Deal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OverviewRequest extends FormRequest
{
    /**
     * Any signed-in rep or manager can open the overview.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewAny', Deal::class) ?? false;
    }

    /**
     * Managers may narrow the page to one rep. The value is ignored for reps.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rep' => [
                'nullable',
                'integer',
                Rule::exists(User::class, 'id')->where('role', Role::Rep->value),
            ],
        ];
    }

    /**
     * The rep a manager chose, or null for everyone.
     */
    public function repId(): ?int
    {
        $rep = $this->validated('rep');

        if (! is_int($rep) && ! is_string($rep)) {
            return null;
        }

        return (int) $rep;
    }
}
