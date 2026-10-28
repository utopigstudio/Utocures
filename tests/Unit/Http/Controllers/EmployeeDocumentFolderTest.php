<?php

use App\Http\Middleware\SetupCheck;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeDocumentFolder;
use App\Models\File;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->withoutMiddleware(SetupCheck::class);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
});

test('folders can be created renamed and deleted only when empty', function () {
    $this->post(route('employee-document-folders.store'), ['name' => 'Nueva'])->assertSessionHasNoErrors();
    $folder = EmployeeDocumentFolder::where('name', 'Nueva')->firstOrFail();
    $this->put(route('employee-document-folders.update', $folder), ['name' => 'Renombrada'])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('employee_document_folders', ['id' => $folder->id, 'name' => 'Renombrada']);
    $file = File::create([
        'name' => 'test.pdf', 'path' => 'test.pdf',
        'fileable_type' => (new Employee)->getMorphClass(), 'fileable_id' => (string) Str::uuid(),
        'employee_document_folder_id' => $folder->id,
    ]);
    $this->delete(route('employee-document-folders.destroy', $folder))->assertSessionHasErrors('folder');
    expect($folder->fresh())->not->toBeNull();
    $file->delete();
    $this->delete(route('employee-document-folders.destroy', $folder))->assertSessionHasNoErrors();
    expect($folder->fresh())->toBeNull();
});

test('migration assigns all existing employee files to Otras and leaves other resources unchanged', function () {
    $migration = require database_path('migrations/2026_10_21_000001_create_employee_document_folders.php');
    $migration->down();
    $employeeFile = File::create([
        'name' => 'old.pdf', 'path' => 'uploads/files/old.pdf',
        'fileable_type' => (new Employee)->getMorphClass(), 'fileable_id' => (string) Str::uuid(),
    ]);
    $clientFile = File::create([
        'name' => 'client.pdf', 'path' => 'uploads/files/client.pdf',
        'fileable_type' => (new Client)->getMorphClass(), 'fileable_id' => (string) Str::uuid(),
    ]);
    $migration->up();
    expect(EmployeeDocumentFolder::count())->toBe(5);
    expect($employeeFile->fresh()->employee_document_folder_id)->toBe(EmployeeDocumentFolder::where('name', 'Otras')->firstOrFail()->id);
    expect($clientFile->fresh()->employee_document_folder_id)->toBeNull();
});

test('non administrators cannot manage folders', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::EMPLOYEE]));
    $this->post(route('employee-document-folders.store'), ['name' => 'Forbidden'])->assertForbidden();
});

test('distribution and individual uploads keep their folder and moving checks ownership', function () {
    Storage::fake('public');
    $employee = Employee::create([
        'user_id' => User::factory()->create(['role' => UserRole::EMPLOYEE])->id,
        'nif' => '12345678Z', 'gender_id' => 1, 'phone' => '600000000',
        'address' => 'Test', 'city' => 'Test', 'zip_code' => '08001', 'country_id' => 'ES',
    ]);
    $folders = EmployeeDocumentFolder::orderBy('name')->get();
    $this->post(route('employee-documents.store'), [
        'employee_ids' => [$employee->id], 'employee_document_folder_id' => $folders[0]->id,
        'documents' => [UploadedFile::fake()->create('shared.pdf', 10, 'application/pdf')],
    ])->assertSessionHasNoErrors();
    $shared = $employee->files()->firstOrFail();
    Storage::disk('public')->assertExists($shared->path);
    expect($shared->employee_document_folder_id)->toBe($folders[0]->id);
    $this->post(route('files.store', ['resource' => 'employee', 'id' => $employee->id]), [
        'employee_document_folder_id' => $folders[1]->id,
        'file' => UploadedFile::fake()->create('individual.pdf', 10, 'application/pdf'),
    ])->assertSessionHasNoErrors();
    expect($employee->files()->where('name', 'individual.pdf')->firstOrFail()->employee_document_folder_id)->toBe($folders[1]->id);
    Storage::disk('public')->assertExists($employee->files()->where('name', 'individual.pdf')->firstOrFail()->path);
    $otherEmployee = $employee->replicate();
    $otherEmployee->user_id = User::factory()->create(['role' => UserRole::EMPLOYEE])->id;
    $otherEmployee->nif = '87654321X';
    $otherEmployee->save();
    $this->patch(route('files.move', ['file' => $shared->id, 'resource' => 'employee', 'id' => $otherEmployee->id]), [
        'employee_document_folder_id' => $folders[1]->id,
    ])->assertForbidden();
    $this->assertDatabaseHas('files', ['id' => $shared->id, 'employee_document_folder_id' => $folders[0]->id]);
    $this->patch(route('files.move', ['file' => $shared->id, 'resource' => 'employee', 'id' => $employee->id]), [
        'employee_document_folder_id' => $folders[1]->id,
    ])->assertSessionHasNoErrors();
    expect($shared->fresh()->employee_document_folder_id)->toBe($folders[1]->id);
    $this->patch(route('files.move', ['file' => $shared->id, 'resource' => 'employee', 'id' => $employee->id]), [
        'employee_document_folder_id' => (string) Str::uuid(),
    ])->assertSessionHasErrors('employee_document_folder_id');
    $this->assertDatabaseHas('files', ['id' => $shared->id, 'employee_document_folder_id' => $folders[1]->id]);
});

test('deleting a distribution only removes copies in its folder and preserves the shared file until the last copy', function () {
    Storage::fake('public');
    $folders = EmployeeDocumentFolder::orderBy('name')->get();
    $path = 'uploads/employee-documents/shared.pdf';
    Storage::disk('public')->put($path, 'shared document');
    $attributes = [
        'name' => 'shared.pdf',
        'path' => $path,
        'fileable_type' => (new Employee)->getMorphClass(),
        'employee_document_folder_id' => $folders[0]->id,
    ];
    $first = File::create([...$attributes, 'fileable_id' => (string) Str::uuid()]);
    $second = File::create([...$attributes, 'fileable_id' => (string) Str::uuid()]);
    $moved = File::create([
        ...$attributes,
        'fileable_id' => (string) Str::uuid(),
        'employee_document_folder_id' => $folders[1]->id,
    ]);

    $this->delete(route('employee-documents.destroy', $first))->assertRedirect()->assertSessionHasNoErrors();

    $this->assertModelMissing($first);
    $this->assertModelMissing($second);
    $this->assertModelExists($moved);
    Storage::disk('public')->assertExists($path);

    $this->delete(route('employee-documents.destroy', $moved))->assertRedirect()->assertSessionHasNoErrors();

    $this->assertModelMissing($moved);
    Storage::disk('public')->assertMissing($path);
});
