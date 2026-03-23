<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('icon_slug');
            $table->string('color');
            $table->string('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('discount_partner', 10, 2)->nullable()->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('service_id')->constrained('services')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('path');
            $table->uuidMorphs('fileable', 'fil_idx');
            $table->timestamps();
        });

        Schema::create('budget_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->longText('content');
            $table->timestamps();
        });

        Schema::create('contract_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->longText('content');
            $table->timestamps();
        });

        Schema::create('characteristics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('characteristic_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('characteristic_id')->constrained('characteristics')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->date('birth_date')->nullable();
            $table->integer('gender_id');
            $table->string('cif_nif');
            $table->string('email');
            $table->string('phone');
            $table->string('phone_2')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('zip_code');
            $table->string('country_id');
            $table->string('bank_name')->nullable();
            $table->string('bank_account')->nullable();
            $table->boolean('tax_included')->default(true);
            $table->boolean('is_partner')->default(false);
            $table->boolean('automatic_invoice')->default(true);
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->string('nif')->unique();
            $table->date('birth_date')->nullable();
            $table->integer('gender_id');
            $table->date('hire_date')->nullable();
            $table->string('phone');
            $table->string('phone_2')->nullable();
            $table->string('address');
            $table->string('city');
            $table->string('zip_code');
            $table->string('country_id');
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('address');
            $table->string('city');
            $table->integer('zip_code');
            $table->string('country_id');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('invoice_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('invoice_id')->constrained('invoices')->cascadeOnDelete();
            $table->string('concept');
            $table->decimal('price', 10, 2);
            $table->decimal('quantity', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->string('tax_type');
            $table->decimal('tax', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();
        });

        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->nullable()->constrained('clients')->restrictOnDelete();
            $table->string('client_name')->nullable();
            $table->date('due_date')->nullable();
            $table->integer('status');
            $table->longText('content');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('budget_lines', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('budget_id')->constrained('budgets')->cascadeOnDelete();
            $table->string('concept');
            $table->decimal('price', 10, 2);
            $table->decimal('quantity', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('tax_type');
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->date('date_start');
            $table->date('date_end')->nullable();
            $table->integer('status');
            $table->longText('content');
            $table->timestamps();
        });

        Schema::create('notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('noteable', 'note_idx');
            $table->text('content');
            $table->foreignUuid('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('assigned_services', function (Blueprint $table) {
            $table->uuidMorphs('serviceable', 'serv_idx');
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();
        });

        Schema::create('assigned_characteristics', function (Blueprint $table) {
            $table->foreignUuid('characteristic_option_id')->constrained('characteristic_options')->cascadeOnDelete();
            $table->uuidMorphs('characterizable', 'charact_idx');
            $table->timestamps();
        });

        Schema::create('available_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuidMorphs('hourable', 'hour_idx');
            $table->string('day_of_week');
            $table->time('time_start');
            $table->time('time_end');
            $table->timestamps();
        });

        Schema::create('assigned_hours_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->json('days_of_week')->nullable();
            $table->date('date')->nullable();
            $table->integer('recurrency');
            $table->time('time_start');
            $table->time('time_end');
            $table->date('date_start')->nullable();
            $table->date('date_end')->nullable();
            $table->foreignUuid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('assigned_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('date');
            $table->time('time_start');
            $table->time('time_end');
            $table->foreignUuid('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignUuid('client_id')->constrained('clients')->restrictOnDelete();
            $table->foreignUuid('service_id')->constrained('services')->restrictOnDelete();
            $table->foreignUuid('assigned_hours_template_id')->constrained('assigned_hours_templates')->restrictOnDelete();
            $table->foreignUuid('employee_substitute_id')->nullable()->constrained('employees')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create('action_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuidMorphs('actionable', 'actev_idx');
            $table->mediumText('original');
            $table->mediumText('changes');
            $table->timestamps();
        });

        Schema::create('employee_time_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignUuid('assigned_hour_id')->constrained('assigned_hours')->cascadeOnDelete();
            $table->date('date_in');
            $table->date('date_out')->nullable();
            $table->time('time_in');
            $table->time('time_out')->nullable();
            $table->timestamps();
        });

        Schema::create('configurations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('company_name');
            $table->string('company_cif_nif');
            $table->string('company_email');
            $table->string('company_phone');
            $table->string('company_address');
            $table->string('company_city');
            $table->string('company_zip_code');
            $table->string('company_country_id');
            $table->string('company_image');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('configurations');
        Schema::dropIfExists('employee_time_records');
        Schema::dropIfExists('action_events');
        Schema::dropIfExists('assigned_hours');
        Schema::dropIfExists('assigned_hours_templates');
        Schema::dropIfExists('available_hours');
        Schema::dropIfExists('assigned_characteristics');
        Schema::dropIfExists('assigned_services');
        Schema::dropIfExists('notes');
        Schema::dropIfExists('contracts');
        Schema::dropIfExists('invoice_lines');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('characteristic_options');
        Schema::dropIfExists('characteristics');
        Schema::dropIfExists('contract_templates');
        Schema::dropIfExists('budget_templates');
        Schema::dropIfExists('files');
        Schema::dropIfExists('service_tasks');
        Schema::dropIfExists('services');
    }
};
