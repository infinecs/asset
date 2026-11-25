<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EmployeeAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->employee = Employee::create([
            'name' => 'JANE DOE',
            'id_number' => 'INF001',
            'email' => 'jane@example.com',
            'status' => 'active',
        ]);
    }

    private function actingAsRole(string $role): self
    {
        return $this->actingAs(User::factory()->create(['role' => $role]));
    }

    private function employeeForm(array $overrides = []): array
    {
        return array_merge([
            'name' => 'JOHN SMITH',
            'id_number_suffix' => '002',
            'email' => 'john@example.com',
            'status' => 'active',
        ], $overrides);
    }

    public function test_non_admin_cannot_change_employees(): void
    {
        $document = EmployeeDocument::create([
            'employee_id' => $this->employee->id,
            'document_type' => 'contract',
            'file_name' => 'contract.pdf',
            'file_path' => 'employee-documents/contract.pdf',
        ]);

        $this->actingAsRole('staff');

        $this->get(route('employees.create'))->assertForbidden();
        $this->post(route('employees.store'), $this->employeeForm())->assertForbidden();
        $this->get(route('employees.edit', $this->employee))->assertForbidden();
        $this->put(route('employees.update', $this->employee), $this->employeeForm(['name' => 'HACKED']))->assertForbidden();
        $this->delete(route('employees.destroy', $this->employee))->assertForbidden();
        $this->delete(route('employees.delete-document', [$this->employee, $document]))->assertForbidden();
        $this->post(route('employees.import'), [
            'file' => UploadedFile::fake()->createWithContent('employees.csv', "name,id_number,email\n"),
        ])->assertForbidden();

        $this->assertDatabaseHas('employees', ['id' => $this->employee->id, 'name' => 'JANE DOE']);
        $this->assertDatabaseMissing('employees', ['email' => 'john@example.com']);
        $this->assertDatabaseHas('employee_documents', ['id' => $document->id]);
    }

    public function test_non_admin_can_still_view_employees(): void
    {
        $this->actingAsRole('staff');

        $this->get(route('employees.index'))->assertOk()->assertSee('JANE DOE');
        $this->get(route('employees.show', $this->employee))->assertOk();
    }

    public function test_admin_can_manage_employees(): void
    {
        $this->actingAsRole('admin');

        $this->get(route('employees.create'))->assertOk();
        $this->post(route('employees.store'), $this->employeeForm())->assertRedirect(route('employees.index'));
        $this->assertDatabaseHas('employees', ['email' => 'john@example.com', 'id_number' => 'INF002']);

        $this->get(route('employees.edit', $this->employee))->assertOk();
        $this->put(route('employees.update', $this->employee), $this->employeeForm([
            'id_number_suffix' => '001',
            'email' => 'jane@example.com',
            'name' => 'JANE RENAMED',
        ]))->assertRedirect();
        $this->assertDatabaseHas('employees', ['id' => $this->employee->id, 'name' => 'JANE RENAMED']);

        $this->delete(route('employees.destroy', $this->employee))->assertRedirect(route('employees.index'));
        $this->assertDatabaseMissing('employees', ['id' => $this->employee->id]);
    }
}
