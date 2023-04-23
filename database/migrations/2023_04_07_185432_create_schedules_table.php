<?php

use App\Enum\GameType;
use App\Enum\GameStatus;
use App\Enum\SendStatus;
use App\Helper\EnumHelper;
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
        Schema::create('schedule', function (Blueprint $table) {
            $table->id();
            $table->enum('game', EnumHelper::getValuesAsArray(GameType::class));
            $table->string('title')->default('');
            $table->integer('number')->nullable();
            $table->integer('package')->nullable();
            $table->string('full_title')->nullable();
            $table->integer('price')->nullable();
            $table->string('place')->nullable();
            $table->dateTime('start')->nullable();
            $table->enum('status', EnumHelper::getValuesAsArray(GameStatus::class))->nullable();
            $table->enum('send_status', EnumHelper::getValuesAsArray(SendStatus::class))->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule');
    }
};
