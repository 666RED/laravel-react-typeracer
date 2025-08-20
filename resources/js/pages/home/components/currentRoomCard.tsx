import LeaveRoomDialog from '@/components/leaveRoomDialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { SharedData } from '@/types';
import { RoomEvent, UpdatePlayerCountProps } from '@/types/room';
import { router, usePage } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { FormEventHandler } from 'react';

interface Props {
  processing: boolean;
  setProcessing: React.Dispatch<React.SetStateAction<boolean>>;
}

export default function CurrentRoomCard({ processing, setProcessing }: Props) {
  const { currentRoom } = usePage<SharedData>().props;

  const handleJoinRoom: FormEventHandler = (e) => {
    e.preventDefault();
    setProcessing(true);
    router.post(
      route('room.join'),
      { roomId: currentRoom.id },
      {
        onFinish: () => {
          setProcessing(false);
        },
      },
    );
  };

  useEchoPublic<UpdatePlayerCountProps>('public-rooms', [RoomEvent.JOIN_ROOM, RoomEvent.LEAVE_ROOM], (e) => {
    if (e.roomId === currentRoom.id) {
      router.reload();
    }
  });

  return (
    <Card className="bg-primary-foreground" data-testid="current-room-card">
      <CardHeader>
        <CardTitle className="flex flex-col gap-y-4">
          <p className="text-xl">{currentRoom.name}</p>
          <p className="text-sm">{currentRoom.id}</p>
        </CardTitle>{' '}
      </CardHeader>
      <CardContent>
        <p className="text-sm">
          Players: {currentRoom.playerCount} / {currentRoom.maxPlayer}
        </p>
      </CardContent>
      <CardFooter className="justify-end">
        <div className="flex items-center gap-x-2">
          <Button disabled={processing} onClick={handleJoinRoom}>
            Join
          </Button>
          <LeaveRoomDialog owner={currentRoom.owner} />
        </div>
      </CardFooter>
    </Card>
  );
}
