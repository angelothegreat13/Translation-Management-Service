<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $table) {
            $table->id();
            $table->string('key', 255);
            $table->string('locale', 10);
            $table->text('content');
            $table->timestamps();

            $table->unique(['key', 'locale']);
            $table->index('key');
            $table->index('locale');
            $table->fullText('content');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
