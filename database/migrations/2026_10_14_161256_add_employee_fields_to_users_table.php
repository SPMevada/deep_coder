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
        Schema::table('users', function (Blueprint $table) {
            $table->string('employee_code', 30)->nullable()->unique()->after('id');
            $table->foreignId('department_id')->nullable()->after('role_id')->constrained('departments')->nullOnDelete();
            $table->string('designation')->nullable()->after('department_id');
            $table->string('phone', 20)->nullable()->after('designation');
            $table->date('joined_at')->nullable()->after('phone');
            $table->foreignId('created_by')->nullable()->after('joined_at')->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn([
                'employee_code',
                'department_id',
                'designation',
                'phone',
                'joined_at',
                'is_active',
                'deleted_at',
                'created_by',
            ]);
        });
    }
};
