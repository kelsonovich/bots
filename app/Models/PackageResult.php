<?php

namespace App\Models;

use App\Enum\GameStatus;
use App\Enum\GameType;
use App\Enum\SendStatus;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageResult extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'game',
        'package',
        'results',
        'status',
        'send_status',
    ];

    public static function createPackage(GameType $type, string $package): void
    {
        PackageResult::firstOrCreate([
            'game'        => $type,
            'package'     => $package,
            'status'      => GameStatus::NEW,
            'send_status' => SendStatus::IN_QUEUE,
        ]);
    }

    public static function getNew(): Collection
    {
        return PackageResult::where('status', GameStatus::NEW)->get();
    }

    public static function getFinished(): Collection
    {
        return PackageResult::where('status', GameStatus::FINISHED)
            ->whereNot('send_status', SendStatus::SEND)
            ->where('package', 'not like', '%стрим%')
            ->get();
    }

    public static function setSend(PackageResult $packageResult): void
    {
        $packageResult->send_status = SendStatus::SEND;
        $packageResult->save();
    }
}
