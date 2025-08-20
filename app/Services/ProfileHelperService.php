<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ProfileHelperService
{
  public function getUserProfileInfoCache(User $user)
  {
    $profileInfo = Cache::remember("user:$user->id:profile", 5 * 60, function () use ($user) {
      $results = $user->results()->select(['quote_id', 'place', 'wpm', 'total_players', 'accuracy_percentage'])->orderBy('created_at', 'desc')->get();

      $lastTenRaces = $results->take(10);
      $noResult = $results->count() < 1;

      $averageWpm = $noResult ? 0 : round($results->average('wpm'), 2);

      $averageWpmForLastTenRaces = $noResult ? 0 : round($lastTenRaces->average('wpm'), 2);

      $accuracyPercentage = $noResult ? 0 : round($results->average('accuracy_percentage'), 2);

      $totalRaces = $results->count();

      $wonRaces = $noResult ? 0 : $results->filter(fn($result) => $result->place === '1st')->count();

      $notCompletedRaces = $noResult ? 0 : $results->filter(fn($result) => $result->place === 'NC')->count();

      $winRate = $noResult ? 0 : round(($wonRaces / $totalRaces) * 100, 2);

      $bestWpm = $noResult ? 0 : round($results->max('wpm'), 2);

      $worstWpm = $noResult ? 0 : round($results->filter(fn($result) => $result->place !== 'NC')->min('wpm'), 2);

      return [
        'id' => $user->id,
        'name' => $user->name,
        'profileImageUrl' => $user->profileImage?->url,
        'createdAt' => Carbon::parse($user->created_at)->format('Y-m-d'),
        'averageWpm' => $averageWpm,
        'averageWpmForLastTenRaces' => $averageWpmForLastTenRaces,
        'accuracyPercentage' => $accuracyPercentage,
        'totalRaces' => $totalRaces,
        'wonRaces' => $wonRaces,
        'notCompletedRaces' => $notCompletedRaces,
        'winRate' => $winRate,
        'bestWpm' => $bestWpm,
        'worstWpm' => $worstWpm
      ];
    });

    $profileInfo['lastActiveAt'] = Carbon::parse($user->last_active)->diffInSeconds() <= 120 ? 'Currently online' : 'Last active: ' . Carbon::parse($user->last_active)->diffForHumans();

    return $profileInfo;
  }
}
