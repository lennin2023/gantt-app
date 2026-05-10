<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('status');
            $table->index('order');
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->index('project_id');
            $table->index('date');
        });

        Schema::table('task_dependencies', function (Blueprint $table) {
            $table->index('task_id');
            $table->index('depends_on_task_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['order']);
        });

        Schema::table('milestones', function (Blueprint $table) {
            $table->dropIndex(['project_id']);
            $table->dropIndex(['date']);
        });

        Schema::table('task_dependencies', function (Blueprint $table) {
            $table->dropIndex(['task_id']);
            $table->dropIndex(['depends_on_task_id']);
        });
    }
};
