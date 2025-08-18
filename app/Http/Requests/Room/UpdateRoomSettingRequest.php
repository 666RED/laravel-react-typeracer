<?php

namespace App\Http\Requests\Room;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class UpdateRoomSettingRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true; //@ authorization already done in middleware
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'name' => 'required|string|max:50',
      'maxPlayer' => 'required|integer|between:2,5',
      'owner' => 'required|integer|exists:users,id',
      'private' => 'required|boolean'
    ];
  }
}
