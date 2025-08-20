<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\Race\AbortRace;
use App\Http\Middleware\Race\CancelRaceReady;
use App\Http\Middleware\Room\InitializeSessionRoomId;
use App\Http\Middleware\User\UpdateUserLastActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
  ->withRouting(
    web: __DIR__ . '/../routes/web.php',
    commands: __DIR__ . '/../routes/console.php',
    channels: __DIR__ . '/../routes/channels.php',
    health: '/up',
  )
  ->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
      HandleInertiaRequests::class,
      CancelRaceReady::class,
      AbortRace::class,
      UpdateUserLastActive::class,
      InitializeSessionRoomId::class
    ]);
  })
  ->withExceptions(function (Exceptions $exceptions) {
    $exceptions
      ->respond(function (Response $response, Throwable $exception, Request $request) {
        if (!app()->environment(['local', 'testing']) && in_array($response->getStatusCode(), [500, 503, 404, 403])) {
          return Inertia::render('errorPage', ['status' => $response->getStatusCode()])
            ->toResponse($request)
            ->setStatusCode($response->getStatusCode());
        } elseif ($response->getStatusCode() === 419) {
          return back()->with([
            'message' => 'The page expired, please try again.',
          ]);
        }

        return $response;
      })
      ->context(fn() => [])
      ->dontReportDuplicates();
  })->create();
