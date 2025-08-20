<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AuthController extends Controller
{
  public function showRegister()
  {
    return Inertia::render('auth/register');
  }

  public function showLogin()
  {
    return Inertia::render('auth/login');
  }

  public function register(RegisterRequest $request)
  {
    $validated = $request->validated();

    $user = User::create($validated);

    Auth::login($user);

    session()->remove('roomId');

    return to_route('home')->with(['message' => 'Registered successfully', 'type' => 'success']);
  }

  public function login(LoginRequest $request)
  {
    $validated = $request->validated();

    //@ Prevent user logged in as user after logged in as guest & has joined any room
    session()->remove('roomId');

    if (Auth::attempt($validated)) {
      $request->session()->regenerate(); // regenerate session id
      $roomId = User::find(Auth::id())?->room_id;
      if ($roomId) {
        session()->put('roomId', $roomId);
      }

      return to_route('home');
    } else {
      return back()->withErrors('Incorrect credentials');
    }
  }

  public function logout(Request $request)
  {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    session()->remove('roomId');
    return to_route('home')->with(['message' => 'You had logout', 'type' => 'success']);
  }

  public function removeSessionRoomId()
  {
    session()->remove('roomId');
    return to_route('home')->with(['message' => 'You have idle for more than 6 hours', 'type' => 'warning']);
  }
}
