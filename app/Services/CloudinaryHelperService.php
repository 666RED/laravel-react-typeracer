<?php

namespace App\Services;

class CloudinaryHelperService
{
  public function upload(string $originalPath, ?string $folderName)
  {
    $options = [];

    if ($folderName !== '') {
      $options['folder'] = $folderName;
    }

    $results = cloudinary()->uploadApi()->upload($originalPath, $options);

    return ['publicId' => $results['public_id'], 'url' => $results['url']];
  }

  public function destroy(string $publicId)
  {
    cloudinary()->uploadApi()->destroy($publicId);
  }
}
