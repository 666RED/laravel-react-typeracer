<?php

namespace App\Http\Controllers;

use App\Http\Requests\Message\RoomMessageRequest;
use App\Services\MessageHelperService;

class MessageController extends Controller
{
  public function sendMessage(RoomMessageRequest $request, MessageHelperService $helper)
  {
    $validated = $request->validated();
    $roomId = session('roomId');

    $helper->pushMessage($roomId, $validated);
  }
}