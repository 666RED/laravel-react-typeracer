<?php

namespace App\Jobs;

use App\Events\Profile\UploadProfileImage;
use App\Models\ProfileImage;
use App\Models\User;
use App\Services\CloudinaryHelperService;
use App\Services\CloudinaryService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class UploadImageToCloudinary implements ShouldQueue
{
  use Queueable;

  protected string $fullPath;
  protected string $folderName;
  protected int $userId;

  public function __construct(string $fullPath, int $userId, string $folderName = '')
  {
    $this->fullPath = $fullPath;
    $this->folderName = $folderName;
    $this->userId = $userId;
  }

  public function handle(CloudinaryHelperService $helper): void
  {
    ['publicId' => $publicId, 'url' => $url] = $helper->upload($this->fullPath, $this->folderName);

    $profileImage = ProfileImage::create(['public_id' => $publicId, 'url' => $url]);

    $user = User::find($this->userId);
    $user->profile_image_id = $profileImage->id;

    $user->save();

    $profileInfo = Cache::get("user:$this->userId:profile");
    $profileInfo['profileImageUrl'] = $url;
    Cache::put("user:$this->userId:profile", $profileInfo, 5 * 60);

    Storage::deleteDirectory('profile-images');

    broadcast(new UploadProfileImage($this->userId));
  }

  public function getFullPath()
  {
    return $this->fullPath;
  }

  public function getUserId()
  {
    return $this->userId;
  }

  public function getFolderName()
  {
    return $this->folderName;
  }
}
