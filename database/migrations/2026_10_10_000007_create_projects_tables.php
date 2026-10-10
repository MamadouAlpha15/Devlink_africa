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
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('category')->index();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('recruiting')->index();
            $table->unsignedTinyInteger('members_wanted')->default(1);
            $table->string('cover_path')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_skill', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained()->cascadeOnDelete();

            $table->unique(['project_id', 'skill_id']);
        });

        Schema::create('project_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->foreignId('skill_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('slots')->default(1);
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_role_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        Schema::create('collaboration_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('direction')->default('application');
            $table->foreignId('project_role_id')->nullable()->constrained()->nullOnDelete();
            $table->text('message')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collaboration_requests');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('project_roles');
        Schema::dropIfExists('project_skill');
        Schema::dropIfExists('projects');
    }
};
