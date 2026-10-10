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
            $table->string('first_name')->nullable()->after('name');
            $table->string('last_name')->nullable()->after('first_name');
            $table->string('username')->nullable()->unique()->after('last_name');
            $table->string('avatar_path')->nullable();
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->string('city')->nullable()->index();
            $table->text('bio')->nullable();
            $table->string('profile_type')->default('developer')->index();
            $table->string('experience_level')->nullable()->index();
            $table->string('availability')->default('available')->index();
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('portfolio_url')->nullable();
            $table->string('website_url')->nullable();
            $table->unsignedInteger('devscore')->default(0)->index();
            $table->timestamp('profile_completed_at')->nullable();
            $table->boolean('is_public')->default(true);
            $table->boolean('show_email')->default(false);
            $table->boolean('show_availability')->default(true);
            $table->boolean('notify_by_email')->default(true);
            $table->boolean('notify_on_platform')->default(true);
            $table->boolean('is_admin')->default(false);
            $table->timestamp('suspended_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropUnique(['username']);
            $table->dropIndex(['city']);
            $table->dropIndex(['profile_type']);
            $table->dropIndex(['experience_level']);
            $table->dropIndex(['availability']);
            $table->dropIndex(['devscore']);
            $table->dropColumn([
                'first_name', 'last_name', 'username', 'avatar_path', 'city', 'bio',
                'profile_type', 'experience_level', 'availability',
                'github_url', 'linkedin_url', 'portfolio_url', 'website_url',
                'devscore', 'profile_completed_at',
                'is_public', 'show_email', 'show_availability',
                'notify_by_email', 'notify_on_platform', 'is_admin', 'suspended_at',
            ]);
        });
    }
};
