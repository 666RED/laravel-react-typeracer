<?php

namespace App\Http\Controllers;

use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Jobs\UploadImageToCloudinary;
use App\Models\RaceResult;
use App\Models\User;
use App\Services\CloudinaryHelperService;
use App\Services\ProfileHelperService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ProfileController extends Controller
{
  public function show(int $userId, ProfileHelperService $helper)
  {
    $validated = Validator::make(['userId' => $userId], [
      'userId' => 'required|integer|exists:users,id',
    ])->validate();

    $userId = $validated['userId'];
    $user = User::find($userId);

    $profileInfo = $helper->getUserProfileInfoCache($user);

    return Inertia::render('profile/index', ['profileInfo' => $profileInfo]);
  }

  public function update(UpdateProfileRequest $request, CloudinaryHelperService $cloudinaryHelper, ProfileHelperService $profileHelper)
  {
    $validated = $request->validated();
    $name = $validated['name'] ?? null;
    $profileImage = $validated['profileImage'] ?? null;
    $user = User::find(Auth::id());
    $profileInfo = Cache::has("user:$user->id:profile") ? Cache::get("user:$user->id:profile") : $profileHelper->getUserProfileInfoCache($user);

    if ($profileImage) {
      //@ Remove previous profile image if any
      $previousProfileImage = $user->profileImage;
      if ($previousProfileImage) {
        $cloudinaryHelper->destroy($previousProfileImage->public_id);

        $user->profile_image_id = null;
        $user->save();
        $previousProfileImage->delete();
      }

      $folderName = 'profile-images';
      $path = $profileImage->store($folderName);
      $fullPath = storage_path("app/private/$path");

      UploadImageToCloudinary::dispatch($fullPath, Auth::id(), $folderName);

      if ($name) {
        $user->name = $name;
        $profileInfo['name'] = $name;
      }
    } else {
      $user = User::find(Auth::id());
      $user->name = $name;
      $profileInfo['name'] = $name;
    }

    $user->save();

    Cache::put("user:$user->id:profile", $profileInfo, 5 * 60);
  }

  public function destroy(CloudinaryHelperService $helper)
  {
    $user = User::find(Auth::id());
    $profileImage = $user->profileImage;

    if ($profileImage) {
      $helper->destroy($profileImage->public_id);

      $user->profile_image_id = null;
      $user->save();

      $profileImage->delete();

      return back()->with(['message' => 'Profile image deleted', 'type' => 'success']);
    } else {
      return back()->with(['message' => 'No profile image', 'type' => 'error']);
    }
  }

  public function showResults(int $userId, int $page = 1)
  {
    $validated = Validator::make(['userId' => $userId], [
      'userId' => 'required|integer|exists:users,id',
    ])->validate();

    $userId = $validated['userId'];

    $results = RaceResult::where('user_id', $userId)->orderBy("created_at", 'desc')->with('quote', function ($query) {
      $query->select(['id', 'text']);
    })->paginate(10);

    return Inertia::render('profile/results', ['results' => $results, 'userId' => $userId]);
  }
}
