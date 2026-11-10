<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EmployeeApiTest extends TestCase
{
    use RefreshDatabase;

    private function payload(): array
    {
        return [
            'name' => 'Test Employee', 'employee_id' => 'EMP-001',
            'email' => 'employee@example.com', 'role_id' => Role::firstOrCreate(['name' => 'Employee'])->route_key,
            'area' => 'Area', 'region' => 'Region', 'zone' => 'Zone',
            'joining_date' => '2026-11-10', 'status' => 'active', 'password' => 'Password123',
        ];
    }

    public function test_guests_can_list_employees_and_roles(): void
    {
        $role = Role::create(['name' => 'Manager']);
        $this->getJson('/api/employees')->assertOk();
        $this->getJson('/api/roles')->assertOk()->assertJsonPath('data.0.route_key', $role->route_key)->assertJsonPath('data.0.id', $role->id);
    }

    public function test_guest_can_manage_employees_without_exposing_passwords(): void
    {
        $payload = $this->payload();
        $created = $this->postJson('/api/employees', $payload)->assertCreated()->assertJsonMissingPath('data.password');
        $id = $created->json('data.route_key');
        $employee = Employee::firstOrFail();
        $this->assertSame(5, strlen($id));
        $created->assertJsonPath('data.id', $employee->id)->assertJsonMissingPath('data.role_id')->assertJsonPath('data.role.id', $employee->role_id);
        $this->getJson('/api/employees/'.$employee->id)->assertNotFound();
        $this->getJson('/api/employees/invalid-key')->assertNotFound();
        $this->patchJson("/api/employees/{$id}/status", ['status' => 'inactive'])
            ->assertOk()->assertJsonPath('data.status', 'inactive')->assertJsonPath('message', 'Employee status updated successfully.');
        $this->assertSame('inactive', $employee->fresh()->status);
        $this->patchJson("/api/employees/{$id}/status", ['status' => 'pending'])->assertUnprocessable();
        $this->assertTrue(Hash::check($payload['password'], $employee->fresh()->password));
        $this->getJson('/api/employees')->assertOk()->assertJsonPath('total', 1)
            ->assertJsonPath('data.0.id', $employee->id)->assertJsonPath('data.0.route_key', $id)
            ->assertJsonMissingPath('data.0.password');
        $this->getJson("/api/employees/{$id}")->assertOk()
            ->assertJsonPath('data.id', $employee->id)->assertJsonPath('data.route_key', $id);
        $this->putJson("/api/employees/{$id}", [...$payload, 'name' => 'Updated', 'password' => ''])
            ->assertOk()->assertJsonPath('data.name', 'Updated')->assertJsonPath('message', 'Employee updated successfully.');
        $this->assertTrue(Hash::check($payload['password'], $employee->fresh()->password));
        $this->postJson('/api/employees', $payload)->assertUnprocessable()->assertJsonValidationErrors(['email', 'employee_id']);
        $this->deleteJson("/api/employees/{$id}")->assertOk()->assertJsonPath('message', 'Employee deleted successfully.');
        $this->assertSoftDeleted('employees', ['id' => $employee->id]);
        $this->assertDatabaseCount('employees', 1);
        $this->getJson("/api/employees/{$id}")->assertNotFound();
        $this->getJson('/api/employees')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/employees/active')->assertOk()->assertJsonPath('total', 0);
        $this->putJson("/api/employees/{$id}", $payload)->assertNotFound();
        $this->patchJson("/api/employees/{$id}/status", ['status' => 'active'])->assertNotFound();
        $this->deleteJson("/api/employees/{$id}")->assertNotFound();
        $this->postJson('/api/employees', $payload)->assertUnprocessable()->assertJsonValidationErrors(['employee_id', 'email']);
    }

    public function test_invalid_fields_are_rejected(): void
    {
        $this->postJson('/api/employees', [...$this->payload(), 'role_id' => 999, 'status' => 'other', 'password' => 'short', 'joining_date' => 'invalid'])
            ->assertUnprocessable()->assertJsonValidationErrors(['role_id', 'status', 'password', 'joining_date']);
    }

    public function test_api_validation_returns_json_even_without_accept_json_header(): void
    {
        $this->post('/api/employees', [...$this->payload(), 'role_id' => 3], ['Accept' => '*/*'])
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('errors.role_id.0', 'Select a role using its route_key from GET /api/roles, not its numeric id.');
        $this->assertDatabaseCount('employees', 0);

        $this->get('/api/employees/invalid-key', ['Accept' => 'text/html'])
            ->assertNotFound()->assertHeader('Content-Type', 'application/json');
    }

    public function test_employee_code_is_unique_on_create_and_update(): void
    {
        $first = $this->payload();
        $firstKey = $this->postJson('/api/employees', $first)->assertCreated()->json('data.route_key');

        // A different email and name must not allow a duplicate employee code.
        $this->postJson('/api/employees', [...$first, 'name' => 'Another Employee', 'email' => 'another@example.com', 'employee_id' => ' emp-001 '])
            ->assertUnprocessable()
            ->assertJsonPath('errors.employee_id.0', 'An employee with this employee code already exists.');
        $this->assertDatabaseCount('employees', 1);

        $second = [...$first, 'employee_id' => 'EMP-002', 'email' => 'second@example.com'];
        $secondKey = $this->postJson('/api/employees', $second)->assertCreated()->json('data.route_key');

        $this->putJson("/api/employees/{$secondKey}", [...$second, 'employee_id' => 'emp-001'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.employee_id.0', 'An employee with this employee code already exists.');
        $this->assertDatabaseHas('employees', ['email' => 'second@example.com', 'employee_id' => 'EMP-002']);

        $this->putJson("/api/employees/{$firstKey}", [...$first, 'name' => 'Renamed Employee'])
            ->assertOk()->assertJsonPath('data.employee_id', 'EMP-001');

        $this->putJson("/api/employees/{$secondKey}", [...$second, 'employee_id' => ' emp-003 '])
            ->assertOk()->assertJsonPath('data.employee_id', 'EMP-003');
    }

    public function test_browser_origin_does_not_require_csrf_or_session(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:5173', 'Referer' => 'http://localhost:5173/'])
            ->postJson('/api/employees', $this->payload())->assertCreated();
        $this->assertGuest();
    }

    public function test_active_employee_list_filters_status_and_supports_search_and_pagination(): void
    {
        $this->getJson('/api/employees/active')->assertOk()->assertJsonPath('total', 0);
        $payload = $this->payload();
        $this->postJson('/api/employees', $payload)->assertCreated();
        $this->postJson('/api/employees', [...$payload, 'employee_id' => 'EMP-002', 'email' => 'inactive@example.com', 'status' => 'inactive'])->assertCreated();
        $this->postJson('/api/employees', [...$payload, 'employee_id' => 'EMP-003', 'email' => 'third@example.com'])->assertCreated();

        $this->getJson('/api/employees/active?per_page=1')->assertOk()
            ->assertJsonPath('total', 2)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'active')->assertJsonPath('data.0.employee_id', 'EMP-003')
            ->assertJsonMissingPath('data.0.password');
        $this->getJson('/api/employees/active?per_page=1&page=2')->assertOk()->assertJsonPath('data.0.employee_id', 'EMP-001');
        $this->getJson('/api/employees/active?search=inactive@example.com')->assertOk()->assertJsonPath('total', 0);
        $this->getJson('/api/employees/active?search=EMP-001')->assertOk()->assertJsonPath('total', 1);
        $this->getJson('/api/employees/active?per_page=0')->assertUnprocessable()->assertJsonValidationErrors('per_page');
        $this->getJson('/api/employees')->assertOk()->assertJsonPath('total', 3);
    }
}
