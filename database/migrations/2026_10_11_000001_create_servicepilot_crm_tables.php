<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('legal_name')->nullable();
            $table->string('timezone')->default('America/New_York');
            $table->char('currency', 3)->default('USD');
            $table->string('tax_label')->default('Sales tax');
            $table->decimal('default_tax_rate', 6, 3)->default('0.000');
            $table->unsignedTinyInteger('working_hours_start')->default(8);
            $table->unsignedTinyInteger('working_hours_end')->default(17);
            $table->unsignedInteger('next_request_number')->default(1001);
            $table->unsignedInteger('next_estimate_number')->default(1001);
            $table->unsignedInteger('next_work_order_number')->default(1001);
            $table->unsignedInteger('next_invoice_number')->default(1001);
            $table->unsignedSmallInteger('default_payment_terms_days')->default(14);
            $table->text('payment_instructions')->nullable();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('alternate_phone')->nullable();
            $table->string('status')->default('active');
            $table->json('tags')->nullable();
            $table->string('communication_preference')->default('any');
            $table->text('internal_notes')->nullable();
            $table->string('billing_address_line1')->nullable();
            $table->string('billing_address_line2')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_region')->nullable();
            $table->string('billing_postal_code')->nullable();
            $table->string('billing_country', 2)->default('US');
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'name']);
            $table->index(['team_id', 'email']);
        });

        Schema::create('customer_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['team_id', 'customer_id']);
        });

        Schema::create('service_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('address_line1');
            $table->string('address_line2')->nullable();
            $table->string('city');
            $table->string('region')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country', 2)->default('US');
            $table->text('access_notes')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['team_id', 'customer_id']);
        });

        Schema::create('service_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('new');
            $table->string('priority')->default('normal');
            $table->string('source')->default('manual');
            $table->string('contact_name')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('subject');
            $table->text('description');
            $table->text('internal_notes')->nullable();
            $table->string('intake_fingerprint')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'priority']);
            $table->unique(['team_id', 'intake_fingerprint']);
        });

        Schema::create('estimates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedInteger('version')->default(1);
            $table->char('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default('0.00');
            $table->decimal('discount_amount', 12, 2)->default('0.00');
            $table->decimal('tax_rate', 6, 3)->default('0.000');
            $table->decimal('tax_amount', 12, 2)->default('0.00');
            $table->decimal('total', 12, 2)->default('0.00');
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by_name')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->json('accepted_snapshot')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'customer_id']);
        });

        Schema::create('estimate_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default('1.00');
            $table->decimal('unit_price', 12, 2)->default('0.00');
            $table->decimal('tax_rate', 6, 3)->default('0.000');
            $table->decimal('line_total', 12, 2)->default('0.00');
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('service_location_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('estimate_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->string('priority')->default('normal');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('internal_notes')->nullable();
            $table->text('customer_notes')->nullable();
            $table->string('service_address_line1')->nullable();
            $table->string('service_address_line2')->nullable();
            $table->string('service_city')->nullable();
            $table->string('service_region')->nullable();
            $table->string('service_postal_code')->nullable();
            $table->string('service_country', 2)->nullable();
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->unsignedSmallInteger('estimated_duration_minutes')->nullable();
            $table->unsignedSmallInteger('travel_buffer_minutes')->default(30);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->string('incomplete_reason')->nullable();
            $table->string('signoff_name')->nullable();
            $table->timestamp('signed_off_at')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default('0.00');
            $table->decimal('tax_amount', 12, 2)->default('0.00');
            $table->decimal('total', 12, 2)->default('0.00');
            $table->json('reschedule_history')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'scheduled_start_at']);
            $table->index(['team_id', 'customer_id']);
        });

        Schema::create('work_order_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default('1.00');
            $table->decimal('unit_price', 12, 2)->default('0.00');
            $table->decimal('tax_rate', 6, 3)->default('0.000');
            $table->decimal('line_total', 12, 2)->default('0.00');
            $table->boolean('is_material')->default(false);
            $table->timestamps();
        });

        Schema::create('work_order_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_lead')->default(false);
            $table->timestamp('scheduled_start_at')->nullable();
            $table->timestamp('scheduled_end_at')->nullable();
            $table->timestamps();

            $table->unique(['work_order_id', 'user_id']);
            $table->index(['team_id', 'user_id', 'scheduled_start_at']);
        });

        Schema::create('work_order_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->boolean('is_completed')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('work_order_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->string('from_status')->nullable();
            $table->string('to_status')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['work_order_id', 'occurred_at']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->char('currency', 3)->default('USD');
            $table->decimal('subtotal', 12, 2)->default('0.00');
            $table->decimal('tax_amount', 12, 2)->default('0.00');
            $table->decimal('total', 12, 2)->default('0.00');
            $table->decimal('amount_paid', 12, 2)->default('0.00');
            $table->decimal('balance_due', 12, 2)->default('0.00');
            $table->date('issued_on')->nullable();
            $table->date('due_on')->nullable();
            $table->text('notes')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->json('issued_snapshot')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'status']);
            $table->index(['team_id', 'due_on']);
            $table->index('work_order_id');
        });

        Schema::create('invoice_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->string('description');
            $table->decimal('quantity', 10, 2)->default('1.00');
            $table->decimal('unit_price', 12, 2)->default('0.00');
            $table->decimal('tax_rate', 6, 3)->default('0.000');
            $table->decimal('line_total', 12, 2)->default('0.00');
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method')->default('manual');
            $table->string('reference')->nullable();
            $table->string('idempotency_key')->nullable();
            $table->date('paid_on');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'invoice_id']);
            $table->unique(['invoice_id', 'idempotency_key']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('attachable');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->string('visibility')->default('internal');
            $table->string('kind')->default('file');
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->morphs('subject');
            $table->string('action');
            $table->json('properties')->nullable();
            $table->timestamps();

            $table->index(['team_id', 'created_at']);
        });

        if (! Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_line_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('work_order_events');
        Schema::dropIfExists('work_order_checklist_items');
        Schema::dropIfExists('work_order_assignments');
        Schema::dropIfExists('work_order_line_items');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('estimate_line_items');
        Schema::dropIfExists('estimates');
        Schema::dropIfExists('service_requests');
        Schema::dropIfExists('service_locations');
        Schema::dropIfExists('customer_contacts');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('team_settings');
    }
};
