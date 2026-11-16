<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_allocations', function (Blueprint $table) {
            $table->string('role_in_project', 50)->nullable()->after('allocation_percentage');
        });
    }

    public function down(): void
    {
        Schema::table('project_allocations', function (Blueprint $table) {
            $table->dropColumn('role_in_project');
        });
    }
};
