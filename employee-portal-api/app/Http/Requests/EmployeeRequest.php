<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->employee_id)) {
            $this->merge(['employee_id' => strtoupper(trim($this->employee_id))]);
        }
        if (is_string($this->email)) {
            $this->merge(['email' => strtolower(trim($this->email))]);
        }
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return [
            'name' => ['required', 'string', 'max:100'],
            'employee_id' => ['bail', 'required', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/', Rule::unique('employees', 'employee_id')->ignore($employee)],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee)],
            'role_id' => ['bail', 'required', 'string', function ($attribute, $value, $fail) {
                if (! (new Role)->resolveRouteBinding($value)) {
                    $fail('Please select a valid role.');
                }
            }],
            'area' => ['required', 'string', 'max:100'],
            'region' => ['required', 'string', 'max:100'],
            'zone' => ['required', 'string', 'max:100'],
            'joining_date' => ['required', 'date_format:Y-m-d'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => [$employee ? 'nullable' : 'required', 'string', 'max:255', Password::min(8)->letters()->numbers()],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required' => 'Employee code is required.',
            'employee_id.unique' => 'An employee with this employee code already exists.',
            'employee_id.max' => 'Employee code must not exceed 50 characters.',
            'employee_id.regex' => 'Employee code may contain only letters, numbers, hyphens and underscores.',
            'email.unique' => 'An employee with this email address already exists.',
            'role_id.string' => 'Select a role using its route_key from GET /api/roles, not its numeric id.',
        ];
    }

    public function employeeData(): array
    {
        $data = $this->validated();
        $data['role_id'] = (new Role)->resolveRouteBinding($data['role_id'])->getKey();

        return $data;
    }
}
