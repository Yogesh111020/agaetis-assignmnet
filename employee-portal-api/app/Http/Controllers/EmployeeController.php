<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        return $this->employeeList($request);
    }

    public function active(Request $request)
    {
        return $this->employeeList($request, true);
    }

    private function employeeList(Request $request, bool $activeOnly = false)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);

        return Employee::with('role')
            ->when($activeOnly, fn ($query) => $query->where('status', 'active'))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('employee_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })->latest('id')->paginate($filters['per_page'] ?? 10)->withQueryString();
    }

    public function store(EmployeeRequest $request)
    {
        $employee = Employee::create($request->employeeData());

        return response()->json(['message' => 'Employee added successfully.', 'data' => $employee->load('role')], 201);
    }

    public function show(Employee $employee)
    {
        return response()->json(['data' => $employee->load('role')]);
    }

    public function update(EmployeeRequest $request, Employee $employee)
    {
        $data = $request->employeeData();
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $employee->update($data);

        return response()->json(['message' => 'Employee updated successfully.', 'data' => $employee->load('role')]);
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return response()->json(['message' => 'Employee deleted successfully.']);
    }

    public function status(Request $request, Employee $employee)
    {
        $data = $request->validate(['status' => ['required', 'in:active,inactive']]);
        $employee->update($data);

        return response()->json(['message' => 'Employee status updated successfully.', 'data' => $employee->load('role')]);
    }
}
