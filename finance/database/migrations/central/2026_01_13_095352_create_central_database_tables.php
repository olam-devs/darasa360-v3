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
        // Super Admins Table
        Schema::create('super_admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email', 191)->unique();
            $table->string('password');
            $table->string('master_password'); // Encrypted password for accessing any school
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // Schools Table
        Schema::create('schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 191)->unique(); // For URL routing (e.g., school001)
            $table->string('database_name', 191)->unique(); // e.g., darasa_school_001
            $table->string('domain')->nullable(); // Optional custom domain
            $table->string('logo')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('subscription_status', ['trial', 'active', 'suspended', 'cancelled'])->default('active');
            $table->timestamp('subscription_expires_at')->nullable();
            $table->integer('max_students')->default(1000);
            $table->timestamps();
        });

        // School Accountants Table (linked to schools)
        Schema::create('school_accountants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->string('name');
            $table->string('email', 191)->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });

        // Activity Logs Table
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->nullable()->constrained('schools')->onDelete('cascade');
            $table->enum('user_type', ['super_admin', 'accountant']);
            $table->unsignedBigInteger('user_id');
            $table->string('action'); // e.g., 'create_school', 'impersonate', 'toggle_status'
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
            
            $table->index(['school_id', 'created_at']);
            $table->index(['user_type', 'user_id']);
        });

        // Analytics Summary Table (for cross-school analytics)
        Schema::create('analytics_summary', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->date('date');
            $table->integer('total_students')->default(0);
            $table->decimal('total_fees_expected', 15, 2)->default(0);
            $table->decimal('total_fees_collected', 15, 2)->default(0);
            $table->decimal('collection_rate', 5, 2)->default(0); // Percentage
            $table->integer('active_parents')->default(0);
            $table->timestamps();
            
            $table->unique(['school_id', 'date']);
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_summary');
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('school_accountants');
        Schema::dropIfExists('schools');
        Schema::dropIfExists('super_admins');
    }
};
