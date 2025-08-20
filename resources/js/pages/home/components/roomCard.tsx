import JoinRoomDialog from '@/components/joinRoomDialog';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Room as RoomType } from '../../../types/room';

interface Props {
  room: RoomType;
  processing: boolean;
  setProcessing: React.Dispatch<React.SetStateAction<boolean>>;
}

export default function RoomCard({ room, processing, setProcessing }: Props) {
  return (
    <Card className="bg-primary-foreground" data-testid="room-card">
      <CardHeader>
        <CardTitle className="flex flex-col gap-y-4">
          <p className="text-xl">{room.name}</p>
          <p className="text-sm">{room.id}</p>
        </CardTitle>
      </CardHeader>
      <CardContent>
        <p className="text-sm">
          Players: {room.playerCount} / {room.maxPlayer}
        </p>
      </CardContent>
      <CardFooter className="justify-end">
        <JoinRoomDialog processing={processing} setProcessing={setProcessing} room={room} />
      </CardFooter>
    </Card>
  );
}
