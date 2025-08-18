import BaseLayout from '@/layouts/baseLayout';
import CreateNewRoomForm from '@/pages/home/components/createNewRoomForm';
import CurrentRoom from '@/pages/home/components/currentRoom';
import JoinRoomForm from '@/pages/home/components/joinRoomForm';
import Rooms from '@/pages/home/components/rooms';
import { SharedData } from '@/types';
import { RoomEvent, Room as RoomType, UpdatePlayerCountProps } from '@/types/room';
import { Head, router, usePage } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { useState } from 'react';
import { toast } from 'sonner';

interface UpdateRoomProps {
  roomId: string;
  maxPlayer: number;
  name: string;
}

export default function Index({ availableRooms }: { availableRooms: RoomType[] }) {
  const { currentRoom, auth } = usePage<SharedData>().props;

  const [rooms, setRooms] = useState(availableRooms);

  // Add new room card
  useEchoPublic<RoomType>('public-rooms', [RoomEvent.NEW_ROOM_CREATED], (e) => {
    setRooms((prev) => [e, ...prev]);
  });

  // Remove room card
  useEchoPublic<RoomType>('public-rooms', RoomEvent.DELETE_ROOM, (e) => {
    setRooms((prev) => prev.filter((room) => room.id !== e.id));

    // ? Current room deleted & not room owner
    if (currentRoom?.id === e.id && auth?.user.id !== e.owner) {
      toast('Current room deleted by room owner');
      router.post(route('room.remove'));
    }
  });

  // Remove inactive room
  useEchoPublic<{ roomId: string }>('public-rooms', RoomEvent.REMOVE_INACTIVE_ROOM, (e) => {
    setRooms((prev) => prev.filter((room) => room.id !== e.roomId));

    // ? Current room deleted
    if (currentRoom?.id === e.roomId) {
      router.post(route('room.remove'), { message: 'Current room is removed due to inactiveness for more than 2 hours' });
    }
  });

  // Update room player counts
  useEchoPublic<UpdatePlayerCountProps>('public-rooms', [RoomEvent.LEAVE_ROOM, RoomEvent.JOIN_ROOM], (e) => {
    setRooms((prev) => prev.map((room) => (room.id === e.roomId ? { ...room, playerCount: e.playerCount } : room)));
    // ? Update player count in current room
    if (e.roomId === currentRoom?.id && auth?.user.id !== e.playerId) {
      router.reload();
    }
  });

  // Update room setting in card
  useEchoPublic<UpdateRoomProps>('public-rooms', RoomEvent.UPDATE_ROOM_IN_LOBBY, (e) => {
    setRooms((prev) => prev.map((room) => (room.id === e.roomId ? { ...room, name: e.name, maxPlayer: e.maxPlayer } : room)));
  });

  // Remove room in lobby
  useEchoPublic<RoomType>('public-rooms', RoomEvent.REMOVE_ROOM_IN_LOBBY, (e) => {
    setRooms((prev) => prev.filter((room) => room.id !== e.id));
  });

  return (
    <BaseLayout title="Homepage" description="This is home page">
      <Head title="Homepage" />
      <div className="mb-6 flex items-center justify-between">
        {/* ENTER ROOM */}
        <JoinRoomForm />
        {/* CREATE NEW ROOM */}
        <CreateNewRoomForm />
      </div>
      {/* CURRENT ROOM */}
      {currentRoom && <CurrentRoom />}
      {/* ROOMS */}
      <Rooms rooms={currentRoom ? rooms.filter((availableRoom) => availableRoom.id !== currentRoom.id) : rooms} />
    </BaseLayout>
  );
}
