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
import { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface Props {
  owner: number;
}

export default function LeaveRoomDialog({ owner }: Props) {
  const { auth } = usePage<SharedData>().props;

  const [processing, setProcessing] = useState(false);

  const handleLeaveRoom: FormEventHandler = (e) => {
    e.preventDefault();
    setProcessing(true);

    // ? If is owner -> transfer ownership, else leave room
    if (auth?.user?.id === owner) {
      router.post(
        route('room.transfer-and-leave'),
        {},
        {
          onSuccess: () => {
            setProcessing(false);
          },
        },
      );
    } else {
      router.post(
        route('room.leave'),
        {},
        {
          onSuccess: () => {
            setProcessing(false);
          },
        },
      );
    }
  };

  return (
    <AlertDialog>
      <AlertDialogTrigger asChild>
        <Button className="bg-destructive-foreground text-white">Leave</Button>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Leave room?</AlertDialogTitle>
          <AlertDialogDescription>
            {owner === auth?.user?.id
              ? 'The ownership will be transferred to one of the remaining members in the room'
              : 'You will be removed from the room'}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction asChild>
            <ButtonWithLoader processing={processing} text="Leave" onClick={handleLeaveRoom} />
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
