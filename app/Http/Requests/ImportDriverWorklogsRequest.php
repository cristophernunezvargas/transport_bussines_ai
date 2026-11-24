<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Payload of the n8n driver-hours import: one entry per day worked, as
 * recorded in n8n's sheet. The importer totals them per driver and
 * Thursday→Wednesday week. Access is controlled by the integration token
 * middleware, not a user Policy.
 */
class ImportDriverWorklogsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'entries' => ['required', 'array', 'min:1', 'max:500'],
            'entries.*.driver_name' => ['required', 'string', 'max:100'],
            'entries.*.work_date' => ['required', 'date_format:Y-m-d'],
            'entries.*.hours' => ['required', 'numeric', 'min:0', 'max:24'],
        ];
    }
}
