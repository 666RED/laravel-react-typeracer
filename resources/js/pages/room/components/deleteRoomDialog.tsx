import ButtonWithLoader from '@/components/buttonWithLoader';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

export default function DeleteRoomDialog() {
  const [processing, setProcessing] = useState(false);

  const handleDeleteRoom: FormEventHandler = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.delete(route('room.delete', {}), {
      onFinish: () => {
        setProcessing(false);
      },
    });
  };

  return (
    <AlertDialog>
      <AlertDialogTrigger asChild>
        <Button className="bg-destructive-foreground text-white">Delete</Button>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Delete room?</AlertDialogTitle>
          <AlertDialogDescription>All players will be removed from the room</AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction asChild>
            <ButtonWithLoader processing={processing} text="Delete" onClick={handleDeleteRoom} />
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
