import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import BaseLayout from '@/layouts/baseLayout';
import axios from '@/lib/customAxios';
import { FormEventHandler, useState } from 'react';

interface Props {
  profileInfo: {
    id: number;
    name: string;
    profileImageUrl: string;
  };
}

export default function EditProfile({ profileInfo }: Props) {
  const [image, setImage] = useState<File | null>(null);
  const [isUploadingImage, setIsUploadingImage] = useState(false);

  const handleUploadImage: FormEventHandler = async (e) => {
    try {
      e.preventDefault();
      setIsUploadingImage(true);

      if (image) {
        const formdata = new FormData();
        formdata.append('profileImage', image);

        await axios.post(route('profile.update'), formdata, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });
      }
    } catch (err) {
      console.error(err);
    } finally {
      setIsUploadingImage(false);
    }
  };

  const handleImageOnChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setImage(e.target.files[0]);
    }
  };

  return (
    <BaseLayout title="Edit page" description="This is edit page">
      {/* PROFILE IMAGE */}
      <div className="group relative h-28 w-28 rounded-full">
        <img
          src={profileInfo.profileImageUrl ?? '/default-profile-image.jpg'}
          alt="Profile"
          className="absolute inline-block h-full w-full rounded-full object-cover"
          onError={(e) => {
            e.currentTarget.src = '/default-profile-image.jpg';
          }}
        />
        {image ? (
          <Button
            onClick={handleUploadImage}
            className="absolute h-full w-full cursor-pointer items-center justify-center rounded-full opacity-0 transition-all group-hover:opacity-100"
          >
            Submit
          </Button>
        ) : (
          <Button
            asChild
            className="absolute h-full w-full cursor-pointer items-center justify-center rounded-full opacity-0 transition-all group-hover:opacity-100"
          >
            <Label htmlFor="file-input">Upload</Label>
          </Button>
        )}
      </div>
      <input type="file" id="file-input" onChange={handleImageOnChange} accept="image/*" className="hidden" />

      <div>{profileInfo.id}</div>
      <div>{profileInfo.name}</div>
    </BaseLayout>
  );
}
