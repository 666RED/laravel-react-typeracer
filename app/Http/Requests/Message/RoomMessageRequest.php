<?php

namespace App\Http\Requests\Message;

use Illuminate\Foundation\Http\FormRequest;

class RoomMessageRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'text' => 'required|string|max:500',
      'senderId' => 'required|integer|exists:users,id',
      'senderName' => 'required|string',
      'isNotification' => 'required|boolean'
    ];
  }
}
