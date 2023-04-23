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
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->enum('game', EnumHelper::getValuesAsArray(GameType::class))->nullable();
            $table->integer('position')->nullable();
            $table->string('team')->nullable();
            $table->float('points')->nullable();
            $table->float('percent')->nullable();
            $table->integer('count_games')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
