<?php

namespace App\Http\Middleware;

use App\Services\MessageHelperService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
  /**
   * The root template that's loaded on the first page visit.
   *
   * @see https://inertiajs.com/server-side-setup#root-template
   *
   * @var string
   */
  protected $rootView = 'app';

  /**
   * Determines the current asset version.
   *
   * @see https://inertiajs.com/asset-versioning
   */
  public function version(Request $request): ?string
  {
    return parent::version($request);
  }

  /**
   * Define the props that are shared by default.
   *
   * @see https://inertiajs.com/shared-data
   *
   * @return array<string, mixed>
   */
  public function share(Request $request): array
  {
    // Log::info('', [
    //   'user' => $request->user()?->name ?? '',
    //   'url'     => $request->fullUrl(),
    //   'method'  => $request->method(),
    // ]);
    return [
      ...parent::share($request),
      'name' => config('app.name'),
      'auth' => [
        'user' => $request->user()?->only(['id', 'name', 'is_guest']) ?? null,
      ],
      'ziggy' => fn(): array => [
        ...(new Ziggy)->toArray(),
        'location' => $request->url(),
      ],
      'flash' => [
        'message' => fn() => $request->session()->get('message'),
        'type' => fn() => $request->session()->get('type'),
      ],
      'currentRoom' => $this->getCurrentRoom(),
      'messages' => fn() => $this->getRoomMessages(),
    ];
  }

  private function getCurrentRoom()
  {
    $roomId = session('roomId');

    if (!$roomId) {
      return null;
    }

    $rawRoom = Redis::HGETALL("room:$roomId");

    if (!$rawRoom) {
      return null;
    }

    $room = [];

    foreach ($rawRoom as $key => $value) {
      $room[$key] = in_array($key, ['id', 'name']) ? $value : (int) $value;
    }

    $room['private'] = $room['private'] === 1;

    return $room;
  }

  private function getRoomMessages()
  {
    $roomId = session('roomId');

    if (!$roomId) {
      return null;
    }

    $messageHelper = app(MessageHelperService::class);

    return $messageHelper->getMessages($roomId);
  }
}