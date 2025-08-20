import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';

export default function ReturnWaitingRoomButton() {
  const { currentRoom } = usePage<SharedData>().props;

  const handleReturnWaitingRoom = () => {
    router.get(route('room.show', { roomId: currentRoom.id }));
  };

  return <Button onClick={handleReturnWaitingRoom}>Return waiting room</Button>;
}
