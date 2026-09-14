<?php

declare(strict_types=1);

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
        Schema::create('org_invitations', function (Blueprint $table): void {
            $table->id();
            $table->string('email');
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('created_by_id');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('org_invitations');
    }
};
