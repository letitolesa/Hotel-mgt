<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================
        // PART 1: CORE STRUCTURE
        // =====================================================
        
        // Departments table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('name');
        });

        // Positions table
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->string('code', 20)->unique();
            $table->string('title', 100);
            $table->decimal('base_salary', 10, 2)->nullable();
            $table->boolean('requires_shift')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('title');
        });

        // External reviewers table
        Schema::create('external_reviewers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('email')->nullable();
            $table->string('company', 255)->nullable();
            $table->string('relationship', 100)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Employees table (combines employee_details functionality)
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('employee_number', 50)->unique();
            $table->foreignId('position_id')->constrained()->onDelete('restrict');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->foreignId('reports_to')->nullable()->constrained('employees')->nullOnDelete();
            $table->date('hire_date');
            $table->enum('employment_status', ['active', 'probation', 'terminated', 'resigned', 'on_leave'])->default('active');
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'intern']);
            
            // Personal details
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            
            // Emergency contact
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relation', 50)->nullable();
            
            // Encrypted fields
            $table->text('bank_account_encrypted')->nullable();
            $table->string('tax_identification_encrypted', 255)->nullable();
            $table->string('social_security_encrypted', 255)->nullable();
            
            // Contract details
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            
            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('employee_number');
            $table->index('employment_status');
            $table->index('reports_to');
            $table->index('user_id');
            $table->index(['first_name', 'last_name']);
            $table->index('department_id');
            $table->index('position_id');
        });

        // Employee position history
        Schema::create('employee_position_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('position_id')->constrained()->onDelete('restrict');
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->date('effective_date');
            $table->date('end_date')->nullable();
            $table->decimal('base_salary', 10, 2);
            $table->string('reason', 255)->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['employee_id', 'effective_date']);
            $table->index(['effective_date', 'end_date']);
        });

        // =====================================================
        // PART 2: PERFORMANCE MANAGEMENT
        // =====================================================

        // Performance reviews
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('reviewer_id');
            $table->boolean('reviewer_is_employee')->default(true);
            $table->foreignId('external_reviewer_id')->nullable()->constrained('external_reviewers')->nullOnDelete();
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->date('review_date');
            $table->integer('rating')->nullable();
            $table->text('comments')->nullable();
            $table->text('goals')->nullable();
            $table->text('achievements')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->enum('status', ['draft', 'submitted', 'acknowledged', 'completed'])->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('employee_id');
            $table->index('review_date');
            $table->index('status');
        });

        // Disciplinary actions
        Schema::create('disciplinary_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actioned_by')->constrained('users')->onDelete('restrict');
            $table->date('incident_date');
            $table->enum('action_type', ['verbal_warning', 'written_warning', 'final_warning', 'suspension', 'termination']);
            $table->enum('severity', ['minor', 'moderate', 'major', 'critical']);
            $table->text('description');
            $table->json('supporting_documents')->nullable();
            $table->enum('status', ['pending', 'issued', 'appealed', 'resolved'])->default('pending');
            $table->date('issued_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('employee_id');
            $table->index('status');
        });

        // =====================================================
        // PART 3: SHIFT & ATTENDANCE MANAGEMENT
        // =====================================================

        // Shifts
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('grace_period_minutes')->default(0);
            $table->boolean('overtime_allowed')->default(true);
            $table->integer('break_duration_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
        });

        // Schedules
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->constrained()->onDelete('restrict');
            $table->date('date');
            $table->boolean('is_holiday')->default(false);
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['employee_id', 'date']);
            $table->index('date');
        });

        // Attendances
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('checked_in_at');
            $table->timestamp('checked_out_at')->nullable();
            $table->enum('check_in_method', ['biometric', 'manual', 'mobile', 'web'])->default('manual');
            $table->enum('check_out_method', ['biometric', 'manual', 'mobile', 'web'])->nullable();
            $table->decimal('check_in_latitude', 10, 8)->nullable();
            $table->decimal('check_in_longitude', 11, 8)->nullable();
            $table->decimal('check_out_latitude', 10, 8)->nullable();
            $table->decimal('check_out_longitude', 11, 8)->nullable();
            $table->string('check_in_photo_path', 2048)->nullable();
            $table->string('check_out_photo_path', 2048)->nullable();
            $table->enum('status', ['present', 'late', 'early_departure', 'absent', 'on_leave', 'holiday'])->default('present');
            $table->integer('overtime_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('early_departure_minutes')->default(0);
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['employee_id', 'checked_in_at']);
            $table->index('status');
        });

        // Leave requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('leave_type', ['annual', 'sick', 'maternity', 'paternity', 'bereavement', 'unpaid', 'study']);
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('total_days')->storedAs('DATEDIFF(end_date, start_date) + 1');
            $table->text('reason');
            $table->string('supporting_document_path', 2048)->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('employee_id');
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });

        // Leave balances
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->enum('leave_type', ['annual', 'sick', 'maternity', 'paternity', 'bereavement', 'unpaid', 'study']);
            $table->integer('year');
            $table->decimal('total_days', 5, 2);
            $table->decimal('used_days', 5, 2)->default(0);
            $table->decimal('pending_days', 5, 2)->default(0);
            $table->decimal('remaining_days', 5, 2)->storedAs('total_days - used_days - pending_days');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['employee_id', 'leave_type', 'year']);
            $table->index('employee_id');
        });

        // =====================================================
        // PART 4: PAYROLL MANAGEMENT
        // =====================================================

        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('payroll_number', 50)->unique();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('pay_period_start');
            $table->date('pay_period_end');
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('overtime_pay', 10, 2)->default(0);
            $table->decimal('allowances', 10, 2)->default(0);
            $table->decimal('bonuses', 10, 2)->default(0);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('gross_pay', 10, 2);
            $table->decimal('tax_deduction', 10, 2)->default(0);
            $table->decimal('social_security_deduction', 10, 2)->default(0);
            $table->decimal('health_insurance_deduction', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2);
            $table->decimal('net_pay', 10, 2);
            $table->enum('payment_method', ['bank_transfer', 'cash', 'check'])->default('bank_transfer');
            $table->enum('status', ['draft', 'calculated', 'approved', 'paid', 'cancelled'])->default('draft');
            $table->foreignId('generated_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_reference', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['employee_id', 'pay_period_start', 'pay_period_end'], 'unique_employee_payroll_period');
            $table->index('employee_id');
            $table->index(['pay_period_start', 'pay_period_end']);
            $table->index('status');
        });

        // Payroll items
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->enum('item_type', ['earning', 'deduction']);
            $table->string('item_name', 100);
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('payroll_id');
        });

        // Employee terminations
        Schema::create('employee_terminations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('termination_date');
            $table->enum('termination_type', ['resignation', 'layoff', 'fired', 'retirement', 'contract_end']);
            $table->text('reason');
            $table->boolean('eligible_for_rehire')->default(true);
            $table->foreignId('final_payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->text('exit_interview_notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique('employee_id');
        });

        // =====================================================
        // PART 5: CATEGORIES & UNITS
        // =====================================================

        // Categories table (referenced by inventory_items and assets)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('name');
            $table->index('parent_id');
        });

        // Units table
        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->string('symbol', 10);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
        });

        // =====================================================
        // PART 6: SUPPLIER MANAGEMENT
        // =====================================================

        // Suppliers table
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->nullable()->unique();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->string('contact_person', 255)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->string('tax_id', 50)->nullable();
            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('ulid');
            $table->index('code');
            $table->index('name');
            $table->index('is_active');
        });

        // =====================================================
        // PART 7: INVENTORY MANAGEMENT
        // =====================================================

        // Inventory items
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->string('unit_of_measure', 50);
            $table->decimal('quantity', 15, 2)->default(0);
            $table->decimal('minimum_quantity', 15, 2)->nullable();
            $table->decimal('maximum_quantity', 15, 2)->nullable();
            $table->decimal('reorder_point', 15, 2)->nullable();
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_value', 15, 2)->storedAs('quantity * unit_cost');
            $table->enum('status', ['in_stock', 'low_stock', 'out_of_stock', 'discontinued'])->default('in_stock');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('department_id');
            $table->index('category_id');
            $table->index('status');
        });

        // Inventory transactions
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained()->onDelete('restrict');
            $table->enum('transaction_type', ['receipt', 'issue', 'return', 'adjustment', 'transfer']);
            $table->decimal('quantity', 15, 2);
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->foreignId('from_department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('to_department_id')->constrained('departments')->onDelete('restrict');
            $table->foreignId('employee_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('transaction_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transaction_date')->useCurrent();
            $table->string('reference_document', 100)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('inventory_item_id');
            $table->index('from_department_id');
            $table->index('to_department_id');
            $table->index('employee_id');
            $table->index('transaction_type');
            $table->index('transaction_date');
        });

        // =====================================================
        // PART 8: ASSET MANAGEMENT
        // =====================================================

        // Assets
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('asset_tag', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->string('model', 100)->nullable();
            $table->string('serial_number', 100)->nullable();
            $table->string('manufacturer', 100)->nullable();
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 15, 2)->nullable();
            $table->decimal('current_value', 15, 2)->nullable();
            $table->date('warranty_expiry')->nullable();
            $table->enum('status', ['available', 'assigned', 'maintenance', 'retired', 'lost'])->default('available');
            $table->enum('condition', ['new', 'good', 'fair', 'poor', 'damaged'])->default('good');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('asset_tag');
            $table->index('department_id');
            $table->index('category_id');
            $table->index('status');
            $table->index('serial_number');
        });

        // Asset assignments
        Schema::create('asset_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_date')->useCurrent();
            $table->date('expected_return_date')->nullable();
            $table->timestamp('returned_date')->nullable();
            $table->enum('condition_on_return', ['new', 'good', 'fair', 'poor', 'damaged'])->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['assigned', 'returned', 'lost'])->default('assigned');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('asset_id');
            $table->index('employee_id');
            $table->index('department_id');
            $table->index('status');
            $table->unique(['asset_id', 'status'], 'unique_active_assignment');
        });

        // =====================================================
        // PART 9: NOTIFICATIONS
        // =====================================================

        // Notification types
        Schema::create('notification_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->boolean('is_email')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
        });

        // Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_number', 50)->unique();
            $table->foreignId('notification_type_id')->constrained()->onDelete('restrict');
            $table->string('title', 255);
            $table->text('body');
            $table->json('data')->nullable();
            $table->string('action_url', 500)->nullable();
            $table->string('email_subject', 255)->nullable();
            $table->boolean('email_sent')->default(false);
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->enum('status', ['draft', 'queued', 'sent', 'failed', 'cancelled'])->default('draft');
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('notification_number');
            $table->index('notification_type_id');
            $table->index('status');
            $table->index('scheduled_for');
            $table->index('created_at');
        });

        // Notification recipients
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('in_app_status', ['pending', 'sent', 'read'])->default('pending');
            $table->timestamp('in_app_sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->enum('email_status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_failed_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('notification_id');
            $table->index('user_id');
            $table->index('in_app_status');
            $table->index('email_status');
            $table->unique(['notification_id', 'user_id']);
        });

        // Notification broadcasts (for Spatie roles/permissions)
        Schema::create('notification_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->cascadeOnDelete();
            $table->enum('target_type', [
                'role', 'permission', 'department', 'all_staff', 
                'all_managers', 'all_guests', 'custom'
            ]);
            $table->unsignedBigInteger('role_id')->nullable();
            $table->unsignedBigInteger('permission_id')->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('total_recipients')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('notification_id');
            $table->index('target_type');
            $table->index('role_id');
            $table->index('permission_id');
            
            // Foreign keys for Spatie tables
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
        });

        // User notification settings
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('notification_type_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('receive_in_app')->default(true);
            $table->boolean('receive_email')->default(true);
            $table->enum('setting_source', ['user', 'role', 'default'])->default('user');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['user_id', 'notification_type_id'], 'unique_user_notification_setting');
            $table->index('user_id');
        });

        // Role notification defaults
        Schema::create('role_notification_defaults', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('role_id');
            $table->foreignId('notification_type_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('receive_in_app')->default(true);
            $table->boolean('receive_email')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['role_id', 'notification_type_id'], 'unique_role_notification_setting');
            $table->index('role_id');
            
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });

        // =====================================================
        // PART 10: CUSTOMERS & HOTEL MANAGEMENT
        // =====================================================

        // Customers
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('membership_number', 50)->nullable()->unique();
            $table->integer('loyalty_points')->default(0);
            $table->enum('loyalty_tier', ['bronze', 'silver', 'gold', 'platinum'])->default('bronze');
            $table->json('preferences')->nullable();
            
            // Customer personal details
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address_line_1', 255)->nullable();
            $table->string('address_line_2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('identification_type', 50)->nullable();
            $table->string('identification_number', 100)->nullable();
            $table->string('company', 255)->nullable();
            $table->string('tax_id', 50)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('membership_number');
            $table->index(['first_name', 'last_name']);
            $table->index('email');
            $table->index('phone');
        });

        // Room types
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->integer('max_occupancy');
            $table->integer('size_sq_meters')->nullable();
            $table->string('bed_type', 50)->nullable();
            $table->json('amenities')->nullable();
            $table->json('images')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('name');
        });

        // Rooms
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('room_number', 10)->unique();
            $table->foreignId('room_type_id')->constrained()->onDelete('restrict');
            $table->integer('floor')->nullable();
            $table->string('wing', 50)->nullable();
            $table->enum('status', ['available', 'occupied', 'reserved', 'maintenance', 'dirty', 'out_of_order'])->default('available');
            $table->enum('housekeeping_status', ['clean', 'dirty', 'inspected', 'out_of_service'])->default('clean');
            $table->timestamp('last_cleaned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('room_number');
            $table->index('status');
            $table->index('housekeeping_status');
        });

        // Rate plans
        Schema::create('rate_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('base_rate', 10, 2);
            $table->text('cancellation_policy')->nullable();
            $table->boolean('prepayment_required')->default(false);
            $table->boolean('includes_breakfast')->default(false);
            $table->boolean('is_refundable')->default(true);
            $table->integer('min_stay')->default(1);
            $table->integer('max_stay')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference', 20)->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('rate_plan_id')->constrained()->onDelete('restrict');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->integer('adults');
            $table->integer('children')->default(0);
            $table->integer('infants')->default(0);
            $table->integer('number_of_nights')->storedAs('DATEDIFF(check_out_date, check_in_date)');
            $table->decimal('room_rate', 10, 2);
            $table->decimal('subtotal', 10, 2)->storedAs('number_of_nights * room_rate');
            $table->decimal('extra_charges', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->storedAs('subtotal + extra_charges - discount_amount + tax_amount');
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2)->storedAs('total_price - amount_paid');
            $table->enum('status', ['pending', 'confirmed', 'checked_in', 'checked_out', 'cancelled', 'no_show', 'in_house'])->default('pending');
            $table->enum('booking_source', ['website', 'walk_in', 'phone', 'email', 'agent', 'corporate']);
            $table->text('special_requests')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('booking_reference');
            $table->index(['check_in_date', 'check_out_date']);
            $table->index('status');
            $table->index('customer_id');
        });

        // Booking charges
        Schema::create('booking_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->string('description', 255);
            $table->decimal('amount', 10, 2);
            $table->enum('charge_type', ['room', 'restaurant', 'mini_bar', 'laundry', 'spa', 'other']);
            $table->date('charge_date');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('booking_id');
        });

        // Booking payments
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'credit_card', 'debit_card', 'mobile_money', 'bank_transfer', 'voucher']);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('transaction_id', 255)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->timestamp('payment_date')->useCurrent();
            $table->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('booking_id');
            $table->index('status');
        });

        // =====================================================
        // PART 11: RESTAURANT MANAGEMENT
        // =====================================================

        // Sections
        Schema::create('sections', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->string('location', 100)->nullable();
            $table->boolean('is_smoking')->default(false);
            $table->boolean('is_outdoor')->default(false);
            $table->boolean('is_private')->default(false);
            $table->integer('min_capacity')->nullable();
            $table->integer('max_capacity')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('name');
            $table->index('location');
        });

        // Hotel tables
        Schema::create('hotel_tables', function (Blueprint $table) {
            $table->id();
            $table->string('table_number', 10)->unique();
            $table->string('table_name', 50)->nullable();
            $table->integer('capacity');
            $table->integer('minimum_capacity')->nullable();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('shape', ['round', 'square', 'rectangle', 'booth'])->default('rectangle');
            $table->integer('position_x')->nullable();
            $table->integer('position_y')->nullable();
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->boolean('is_accessible')->default(true);
            $table->boolean('is_private')->default(false);
            $table->boolean('has_view')->default(false);
            $table->enum('status', ['available', 'occupied', 'reserved', 'cleaning', 'maintenance'])->default('available');
            $table->enum('cleaning_status', ['clean', 'dirty', 'in_progress'])->default('clean');
            $table->timestamp('last_cleaned_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('table_number');
            $table->index('status');
            $table->index('section_id');
            $table->index('capacity');
            $table->index('cleaning_status');
        });

        // Waiters (using employees table instead of employee_details)
        Schema::create('waiters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->unique()->constrained('employees')->cascadeOnDelete();
            $table->foreignId('section_id')->constrained()->onDelete('restrict');
            $table->string('code', 10)->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('is_active');
            $table->index('section_id');
        });

        // Table reservations
        Schema::create('table_reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_number', 20)->unique();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->foreignId('table_id')->constrained('hotel_tables')->onDelete('restrict');
            $table->integer('party_size');
            $table->date('reservation_date');
            $table->time('reservation_time');
            $table->integer('duration_minutes')->default(120);
            $table->time('end_time')->storedAs('ADDTIME(reservation_time, SEC_TO_TIME(duration_minutes * 60))');
            $table->enum('status', ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'])->default('pending');
            $table->enum('source', ['website', 'walk_in', 'phone', 'email', 'hotel_guest', 'concierge']);
            $table->boolean('is_hotel_guest')->default(false);
            $table->string('room_number', 10)->nullable();
            $table->decimal('bill_amount', 10, 2)->default(0);
            $table->boolean('bill_paid')->default(false);
            $table->enum('payment_method', ['cash', 'credit_card', 'debit_card', 'room_charge', 'voucher'])->nullable();
            $table->text('special_requests')->nullable();
            $table->enum('occasion', ['birthday', 'anniversary', 'business', 'date', 'other'])->nullable();
            $table->text('dietary_restrictions')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('seated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('reservation_number');
            $table->index('booking_id');
            $table->index('customer_id');
            $table->index('table_id');
            $table->index('reservation_date');
            $table->index('status');
            $table->index('is_hotel_guest');
            $table->index(['reservation_date', 'reservation_time']);
            $table->unique(['table_id', 'reservation_date', 'reservation_time'], 'unique_table_booking');
        });

        // Waiting list
        Schema::create('waiting_list', function (Blueprint $table) {
            $table->id();
            $table->string('waiting_number', 20)->unique();
            $table->foreignId('customer_id')->constrained()->onDelete('restrict');
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('party_size');
            $table->foreignId('requested_section_id')->nullable()->constrained('sections')->nullOnDelete();
            $table->foreignId('requested_table_id')->nullable()->constrained('hotel_tables')->nullOnDelete();
            $table->timestamp('check_in_time')->useCurrent();
            $table->integer('estimated_wait_minutes')->nullable();
            $table->boolean('sms_notification')->default(false);
            $table->string('phone_number', 20)->nullable();
            $table->boolean('sms_sent')->default(false);
            $table->timestamp('notified_time')->nullable();
            $table->enum('status', ['waiting', 'notified', 'seated', 'cancelled'])->default('waiting');
            $table->timestamp('seated_at')->nullable();
            $table->foreignId('seated_table_id')->nullable()->constrained('hotel_tables')->nullOnDelete();
            $table->text('cancelled_reason')->nullable();
            $table->boolean('is_hotel_guest')->default(false);
            $table->string('room_number', 10)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('waiting_number');
            $table->index('status');
            $table->index('check_in_time');
            $table->index('customer_id');
            $table->index('booking_id');
            $table->index('is_hotel_guest');
        });

        // Table orders
        Schema::create('table_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->unique();
            $table->foreignId('table_reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('table_id')->constrained('hotel_tables')->onDelete('restrict');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('order_type', ['dine_in', 'takeaway', 'room_service'])->default('dine_in');
            $table->enum('status', ['open', 'preparing', 'ready', 'served', 'cancelled', 'completed'])->default('open');
            $table->foreignId('server_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('order_time')->useCurrent();
            $table->timestamp('ready_time')->nullable();
            $table->timestamp('served_time')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->enum('payment_method', ['cash', 'credit_card', 'room_charge', 'complimentary'])->nullable();
            $table->string('room_number', 10)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('order_number');
            $table->index('table_reservation_id');
            $table->index('table_id');
            $table->index('status');
            $table->index('order_time');
        });

        // Table availability exceptions
        Schema::create('table_availability_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('table_id')->nullable()->constrained('hotel_tables')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('exception_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('reason', 255);
            $table->enum('exception_type', ['maintenance', 'private_event', 'holiday', 'renovation', 'staff_training'])->default('maintenance');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_pattern', ['daily', 'weekly', 'monthly'])->nullable();
            $table->date('recurring_end_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('exception_date');
            $table->index('table_id');
            $table->index('section_id');
            $table->index('exception_type');
        });

        // Reservation history
        Schema::create('reservation_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained('table_reservations')->cascadeOnDelete();
            $table->enum('action', ['created', 'updated', 'confirmed', 'cancelled', 'seated', 'completed', 'no_show', 'modified']);
            $table->enum('old_status', ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'])->nullable();
            $table->enum('new_status', ['pending', 'confirmed', 'seated', 'completed', 'cancelled', 'no_show'])->nullable();
            $table->json('changes')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('reservation_id');
            $table->index('action');
            $table->index('created_at');
        });

        // =====================================================
        // PART 12: MENU MANAGEMENT
        // =====================================================

        // Menu categories
        Schema::create('menu_categories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->foreignId('department_id')->constrained()->onDelete('restrict');
            $table->foreignId('parent_id')->nullable()->constrained('menu_categories')->nullOnDelete();
            $table->string('image_path', 2048)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('department_id');
            $table->index('parent_id');
        });

        // Menu items
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->char('ulid', 26)->nullable()->unique();
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->constrained('menu_categories')->onDelete('restrict');
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->integer('preparation_time_minutes')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('image_path', 2048)->nullable();
            $table->string('allergens', 255)->nullable();
            $table->json('nutritional_info')->nullable();
            $table->json('recipe')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('ulid');
            $table->index('code');
            $table->index('category_id');
            $table->index('is_available');
        });

        // Menu item ingredients
        Schema::create('menu_item_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->onDelete('restrict');
            $table->decimal('quantity', 10, 2);
            $table->foreignId('unit_id')->constrained()->onDelete('restrict');
            $table->decimal('wastage_percent', 5, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['menu_item_id', 'inventory_item_id'], 'unique_menu_ingredient');
            $table->index('unit_id');
        });

        // =====================================================
        // PART 13: ORDERS & PAYMENTS
        // =====================================================

        // Tax rates
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->decimal('rate', 5, 2);
            $table->timestamps();
            $table->softDeletes();
        });

        // Promotions
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['percentage', 'fixed_amount', 'buy_x_get_y', 'free_shipping', 'loyalty_points']);
            $table->decimal('value', 10, 2);
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->decimal('max_discount_amount', 10, 2)->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('usage_per_customer')->nullable();
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->json('days_of_week')->nullable();
            $table->enum('applicable_to', ['all', 'menu_items', 'categories', 'customers'])->default('all');
            $table->json('applicable_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('conditions')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index(['start_date', 'end_date']);
            $table->index('is_active');
        });

        // Orders
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 50)->unique();
            $table->foreignId('table_id')->nullable()->constrained('hotel_tables')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('waiter_id')->constrained('users')->onDelete('restrict');
            $table->enum('order_type', ['dine_in', 'takeaway', 'delivery'])->default('dine_in');
            $table->enum('status', ['open', 'in_progress', 'ready_to_serve', 'served', 'paid', 'cancelled'])->default('open');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('service_charge', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamp('ordered_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('order_number');
            $table->index('status');
            $table->index('table_id');
            $table->index('customer_id');
            $table->index('ordered_at');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('menu_item_id')->constrained()->onDelete('restrict');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->storedAs('quantity * unit_price - discount_amount');
            $table->foreignId('tax_rate_id')->nullable()->constrained('tax_rates')->nullOnDelete();
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->text('special_requests')->nullable();
            $table->enum('status', ['pending', 'preparing', 'ready', 'served', 'cancelled'])->default('pending');
            $table->timestamp('preparation_started_at')->nullable();
            $table->timestamp('preparation_completed_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('status');
        });

        // Invoices
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 50)->unique();
            $table->foreignId('order_id')->unique()->constrained()->onDelete('restrict');
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('issued_at')->useCurrent();
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->foreignId('promotion_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('total_amount', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('amount_due', 10, 2)->storedAs('total_amount - amount_paid');
            $table->enum('status', ['draft', 'issued', 'paid', 'partially_paid', 'overdue', 'cancelled'])->default('draft');
            $table->string('pdf_path', 2048)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('invoice_number');
            $table->index('issued_at');
            $table->index('status');
        });

        // Payments
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number', 50)->unique();
            $table->foreignId('order_id')->constrained()->onDelete('restrict');
            $table->decimal('amount', 10, 2);
            $table->enum('method', ['cash', 'credit_card', 'debit_card', 'mobile_money', 'bank_transfer', 'voucher', 'loyalty_points']);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded', 'voided'])->default('pending');
            $table->string('transaction_id', 255)->nullable();
            $table->string('reference_number', 255)->nullable();
            $table->string('card_last_four', 4)->nullable();
            $table->string('card_brand', 50)->nullable();
            $table->string('mobile_money_number', 20)->nullable();
            $table->string('mobile_money_provider', 50)->nullable();
            $table->foreignId('processed_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('processed_at')->useCurrent();
            $table->timestamp('refunded_at')->nullable();
            $table->text('refund_reason')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('order_id');
            $table->index('status');
            $table->index('transaction_id');
        });

        // =====================================================
        // PART 14: PURCHASING MODULE
        // =====================================================

        // Purchase requisitions
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 50)->unique();
            $table->foreignId('requested_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->date('requested_date');
            $table->date('required_date')->nullable();
            $table->enum('status', ['draft', 'pending', 'approved', 'rejected', 'converted_to_po', 'cancelled'])->default('draft');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_estimated_cost', 10, 2)->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('pr_number');
            $table->index('status');
            $table->index('requested_date');
        });

        // Purchase requisition items
        Schema::create('purchase_requisition_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_requisition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->onDelete('restrict');
            $table->integer('quantity_requested');
            $table->decimal('estimated_unit_price', 10, 2)->nullable();
            $table->decimal('estimated_total', 10, 2)->storedAs('quantity_requested * estimated_unit_price');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['purchase_requisition_id', 'inventory_item_id'], 'unique_req_item');
        });

        // Purchase orders
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 50)->unique();
            $table->foreignId('supplier_id')->constrained()->onDelete('restrict');
            $table->foreignId('purchase_requisition_id')->nullable()->constrained()->nullOnDelete();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->text('delivery_address')->nullable();
            $table->string('shipping_method', 100)->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->enum('status', ['draft', 'sent', 'confirmed', 'partially_received', 'received', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->storedAs('total_amount - discount_amount + tax_amount + shipping_cost');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('po_number');
            $table->index('status');
            $table->index('order_date');
        });

        // Purchase order items
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_item_id')->constrained()->onDelete('restrict');
            $table->integer('quantity_ordered');
            $table->integer('quantity_received')->default(0);
            $table->integer('quantity_invoiced')->default(0);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->storedAs('(quantity_ordered * unit_price) * (discount_percent/100)');
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->storedAs('((quantity_ordered * unit_price) - ((quantity_ordered * unit_price) * (discount_percent/100))) * (tax_percent/100)');
            $table->decimal('line_total', 10, 2)->storedAs('(quantity_ordered * unit_price) - ((quantity_ordered * unit_price) * (discount_percent/100)) + (((quantity_ordered * unit_price) - ((quantity_ordered * unit_price) * (discount_percent/100))) * (tax_percent/100))');
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['purchase_order_id', 'inventory_item_id'], 'unique_po_item');
        });

        // Goods received notes
        Schema::create('goods_received_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_number', 50)->unique();
            $table->foreignId('purchase_order_id')->constrained()->onDelete('restrict');
            $table->foreignId('received_by')->constrained('users')->onDelete('restrict');
            $table->date('received_date');
            $table->string('delivery_note_number', 100)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->enum('status', ['draft', 'completed', 'cancelled'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('grn_number');
            $table->index('received_date');
        });

        // Goods received note items
        Schema::create('goods_received_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_received_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->constrained()->onDelete('restrict');
            $table->integer('quantity_received');
            $table->string('batch_number', 100)->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->text('condition_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['goods_received_note_id', 'purchase_order_item_id'], 'unique_grn_item');
        });

        // =====================================================
        // PART 15: FEEDBACK & REVIEWS
        // =====================================================

        // Feedback
        Schema::create('feedback', function (Blueprint $table) {
            $table->id();
            $table->string('feedback_number', 50)->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('table_reservation_id')->nullable()->constrained('table_reservations')->nullOnDelete();
            $table->integer('rating');
            $table->string('title', 255)->nullable();
            $table->text('comment')->nullable();
            $table->enum('category', ['room', 'food', 'service', 'cleanliness', 'amenities', 'check_in', 'check_out', 'value', 'other']);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_anonymous')->default(false);
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('feedback_number');
            $table->index('rating');
            $table->index('category');
            $table->index('table_reservation_id');
        });

        // =====================================================
        // PART 16: ACCOUNTING & FINANCE
        // =====================================================

        // Chart of accounts
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name', 255);
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('category', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('code');
            $table->index('type');
        });

        // Journal entries
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_number', 50)->unique();
            $table->text('description')->nullable();
            $table->date('entry_date');
            $table->integer('period_year');
            $table->integer('period_month');
            $table->string('reference_type', 255)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->boolean('is_reversal')->default(false);
            $table->foreignId('reversed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversal_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->enum('status', ['draft', 'posted', 'reversed', 'cancelled'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('entry_number');
            $table->index('entry_date');
            $table->index(['period_year', 'period_month']);
            $table->index(['reference_type', 'reference_id']);
        });

        // Journal entry lines
        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->decimal('debit_amount', 10, 2)->default(0);
            $table->decimal('credit_amount', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->string('reference_type', 255)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('journal_entry_id');
            $table->index('account_id');
            $table->index(['reference_type', 'reference_id']);
        });

        // Bank accounts
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('restrict')
                  ->comment('References chart_of_accounts');
            $table->string('bank_name', 255);
            $table->string('branch_name', 255)->nullable();
            $table->string('account_name', 255);
            $table->string('account_number', 50);
            $table->string('iban', 50)->nullable();
            $table->string('swift_code', 20)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->decimal('opening_balance', 10, 2)->default(0);
            $table->decimal('current_balance', 10, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('account_id');
        });

        // Bank reconciliations
        Schema::create('bank_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->onDelete('restrict');
            $table->date('statement_date');
            $table->decimal('statement_balance', 10, 2);
            $table->decimal('book_balance', 10, 2);
            $table->decimal('difference', 10, 2)->storedAs('statement_balance - book_balance');
            $table->enum('status', ['draft', 'reconciled', 'cancelled'])->default('draft');
            $table->foreignId('reconciled_by')->constrained('users')->onDelete('restrict');
            $table->timestamp('reconciled_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('statement_date');
        });

        // Reconciliation entries
        Schema::create('reconciliation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_id')->constrained('bank_reconciliations')->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->constrained()->onDelete('restrict');
            $table->boolean('is_cleared')->default(false);
            $table->date('cleared_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['reconciliation_id', 'journal_entry_id'], 'unique_reconciliation_entry');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop tables in reverse order to avoid foreign key constraints
        Schema::dropIfExists('reconciliation_entries');
        Schema::dropIfExists('bank_reconciliations');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('journal_entry_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('feedback');
        Schema::dropIfExists('goods_received_note_items');
        Schema::dropIfExists('goods_received_notes');
        Schema::dropIfExists('purchase_order_items');
        Schema::dropIfExists('purchase_orders');
        Schema::dropIfExists('purchase_requisition_items');
        Schema::dropIfExists('purchase_requisitions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('promotions');
        Schema::dropIfExists('tax_rates');
        Schema::dropIfExists('menu_item_ingredients');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menu_categories');
        Schema::dropIfExists('reservation_history');
        Schema::dropIfExists('table_availability_exceptions');
        Schema::dropIfExists('table_orders');
        Schema::dropIfExists('waiting_list');
        Schema::dropIfExists('table_reservations');
        Schema::dropIfExists('waiters');
        Schema::dropIfExists('hotel_tables');
        Schema::dropIfExists('sections');
        Schema::dropIfExists('booking_payments');
        Schema::dropIfExists('booking_charges');
        Schema::dropIfExists('bookings');
        Schema::dropIfExists('rate_plans');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('role_notification_defaults');
        Schema::dropIfExists('user_notification_settings');
        Schema::dropIfExists('notification_broadcasts');
        Schema::dropIfExists('notification_recipients');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('notification_types');
        Schema::dropIfExists('asset_assignments');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('units');
        Schema::dropIfExists('categories');
        Schema::dropIfExists('employee_terminations');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('shifts');
        Schema::dropIfExists('disciplinary_actions');
        Schema::dropIfExists('performance_reviews');
        Schema::dropIfExists('external_reviewers');
        Schema::dropIfExists('employee_position_history');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};