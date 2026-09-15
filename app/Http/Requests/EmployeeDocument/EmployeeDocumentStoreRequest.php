<?php

namespace App\Http\Requests\EmployeeDocument;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeDocumentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('employees', 'id')->whereNull('deleted_at'),
            ],
            'documents' => ['required', 'array', 'min:1', 'max:20'],
            'documents.*' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_ids.required' => __('employee_documents.validation_employees_required'),
            'employee_ids.min' => __('employee_documents.validation_employees_required'),
            'employee_ids.*.exists' => __('employee_documents.validation_employee_invalid'),
            'documents.required' => __('employee_documents.validation_documents_required'),
            'documents.min' => __('employee_documents.validation_documents_required'),
            'documents.max' => __('employee_documents.validation_documents_max'),
            'documents.*.max' => __('employee_documents.validation_document_size'),
            'documents.*.mimes' => __('employee_documents.validation_document_type'),
        ];
    }
}
