<?php

namespace App\Http\Requests\Race;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProgressRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'percentage' => 'required|numeric|between:0,100',
      'wordsPerMinute' => 'required|numeric|min:0',
      'completedCharacters' => 'required|integer|min:0',
      'wrongCharacters' => 'required|integer|min:0',
    ];
  }
}
