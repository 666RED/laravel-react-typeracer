import FormSubmitButton from '@/components/formSubmitButton';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { router } from '@inertiajs/react';
import { FormEventHandler, useEffect, useState } from 'react';
import { toast } from 'sonner';

interface Props {
  name: string;
  profileImageUrl: string;
}

export default function EditProfileDialog(props: Props) {
  const [name, setName] = useState(props.name);
  const [image, setImage] = useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = useState('');
  const [processing, setProcessing] = useState(false);
  const [open, setOpen] = useState(false);

  const handleSubmit: FormEventHandler = (e) => {
    e.preventDefault();

    setProcessing(true);

    const formdata = new FormData();
    formdata.append('name', name.trim());
    if (image) {
      formdata.append('profileImage', image);
    }

    router.post(route('profile.update'), formdata, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
      onFinish: () => {
        if (image) {
          toast.info('Updating profile image...');
        }
        setProcessing(false);
        setOpen(false);
        setPreviewUrl('');
        setImage(null);
      },
    });
  };

  const handleImageOnChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      setImage(e.target.files[0]);
      setPreviewUrl(URL.createObjectURL(e.target.files![0]));
    }
  };

  const handleClickFileInput = () => {
    const fileInput = document.querySelector('#file-input') as HTMLInputElement;
    fileInput.click();
  };

  useEffect(() => {
    setName(props.name);
  }, [props.name, props.profileImageUrl]);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="submit">Edit profile</Button>
      </DialogTrigger>
      <DialogContent>
        <form onSubmit={handleSubmit} className="flex flex-col gap-y-4">
          <DialogHeader>
            <DialogTitle>Edit profile</DialogTitle>
            <DialogDescription>Edit name and profile image</DialogDescription>
          </DialogHeader>
          {/* NAME & PROFILE IMAGE */}
          <div className="flex flex-col items-center gap-y-4">
            <div className="group relative aspect-square h-40 w-40 rounded-full" onClick={handleClickFileInput}>
              <img
                src={previewUrl ? previewUrl : (props.profileImageUrl ?? '/default-profile-image.jpg')}
                alt="Profile"
                className="absolute inline-block h-full w-full rounded-full object-cover"
                onError={(e) => {
                  e.currentTarget.src = '/default-profile-image.jpg';
                }}
              />
              <Button
                type="button"
                className="absolute h-full w-full cursor-pointer items-center justify-center rounded-full opacity-0 transition-all group-hover:opacity-100"
              >
                Upload
              </Button>
            </div>
            <Input value={name} onChange={(e) => setName(e.target.value)} required />
          </div>
          <DialogFooter>
            <DialogClose asChild>
              <Button variant="outline">Cancel</Button>
            </DialogClose>
            <FormSubmitButton
              processing={processing}
              text="Submit"
              variant="submit"
              disabled={name === '' || (name === props.name && image === null)}
            />
          </DialogFooter>
          <input type="file" id="file-input" onChange={handleImageOnChange} accept="image/*" className="hidden" />
        </form>
      </DialogContent>
    </Dialog>
  );
}
