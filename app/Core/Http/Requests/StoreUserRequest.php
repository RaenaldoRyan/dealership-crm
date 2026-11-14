<?php

namespace App\Core\Http\Requests;

use App\Core\Enums\Role;
use App\Core\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The validated staff account, with each field in the type the action stores.
     *
     * A missing active flag stays active, matching the action.
     *
     * @return array{name: string, email: string, role: string, is_active: bool}
     */
    public function staff(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'email' => $this->string('email')->toString(),
            'role' => $this->string('role')->toString(),
            'is_active' => $this->exists('is_active') ? $this->boolean('is_active') : true,
        ];
    }
}
