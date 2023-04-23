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
        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->enum('game', EnumHelper::getValuesAsArray(GameType::class));
            $table->integer('number');
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
        Schema::dropIfExists('tables');
    }
};
