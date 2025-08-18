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
import { Room } from '@/types/room';
import { router, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

interface Props {
  processing: boolean;
  setProcessing: React.Dispatch<React.SetStateAction<boolean>>;
  room: Room;
}

export default function JoinRoomDialog({ processing, setProcessing, room }: Props) {
  const { currentRoom, auth } = usePage<SharedData>().props;

  // Join new room
  const handleJoinRoom: FormEventHandler = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(
      route('room.join'),
      { roomId: room.id },
      {
        onFinish: () => {
          setProcessing(false);
        },
      },
    );
  };

  // ? Leave previous room and join new room
  const handleLeavePreviousRoomAndJoinRoom: FormEventHandler = (e) => {
    e.preventDefault();
    setProcessing(true);

    // ? Player is previous room's owner
    if (currentRoom?.owner === auth?.user?.id) {
      router.post(
        route('room.transfer-and-join'),
        { roomId: room.id },
        {
          onFinish: () => {
            setProcessing(false);
          },
        },
      );
    } else {
      router.post(
        route('room.leave-and-join'),
        { roomId: room.id },
        {
          onFinish: () => {
            setProcessing(false);
          },
        },
      );
    }
  };

  return currentRoom ? (
    <AlertDialog>
      <AlertDialogTrigger asChild>
        <Button disabled={processing}>Join</Button>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>Join room?</AlertDialogTitle>
          <AlertDialogDescription>
            {currentRoom?.owner === auth?.user?.id
              ? 'The ownership will be transferred to one of the remaining members in the previous room'
              : 'You will be removed from previous room'}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>Cancel</AlertDialogCancel>
          <AlertDialogAction asChild>
            <ButtonWithLoader processing={processing} text="Proceed" onClick={handleLeavePreviousRoomAndJoinRoom} />
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  ) : (
    <Button disabled={processing || room.playerCount >= room.maxPlayer} onClick={handleJoinRoom}>
      Join
    </Button>
  );
}
