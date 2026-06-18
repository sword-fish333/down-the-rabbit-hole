<?php

use App\Models\Admin;
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
        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            // Nullable so OAuth-only admins (e.g. Google) can exist without a local password.
            $table->string('password')->nullable();
            $table->string('profile_image')->nullable();
            $table->enum('role', Admin::ROLES)->default(Admin::ROLES[0]);
            $table->enum('login_method', Admin::LOGIN_METHODS)->default(Admin::CREDENTIALS_LOGIN_METHOD);
            $table->boolean('enabled')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admins');
    }
};