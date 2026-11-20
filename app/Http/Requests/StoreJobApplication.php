<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreJobApplication extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'company_id' => ['required', 'exists:companies,id'],
            'role_title' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ApplicationStatus::class)],
            'salary_min' => ['nullable', 'integer', 'min:0'],
            'salary_max' => ['nullable', 'integer', 'gte:salary_min'],
            'applied_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    #[Override]
    public function messages()
    {
        return [
            'role_title.required' => 'The job title is a must.',
            'company_id.required' => 'The company is required.',
        ];
    }

    #[Override]
    public function attributes()
    {
        return [
            'role_title' => 'Job Title',
            'company_id' => 'Company',
            'status' => 'Status',
            'salary_min' => 'Minimum Salary',
            'salary_max' => 'Maximum Salary',
            'applied_on' => 'Applied On',
            'notes' => 'Notes',
        ];
    }
}
