<?php

namespace App\Sales\Http\Requests;

use App\Sales\Models\Deal;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    /**
     * Sales reports are for managers.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('viewReports', Deal::class) ?? false;
    }

    /**
     * An unknown range is ignored and the page uses the last 30 days.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'range' => ['nullable', 'string', 'max:8'],
        ];
    }

    /**
     * 30 days, 90 days, or everything.
     */
    public function range(): string
    {
        $range = $this->validated('range');

        return in_array($range, ['30', '90', 'all'], true) ? $range : '30';
    }
}
