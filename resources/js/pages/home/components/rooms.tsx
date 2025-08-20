import RoomCard from '@/pages/home/components/roomCard';
import { Room as RoomType } from '@/types/room';
import { useState } from 'react';

export default function Rooms({ rooms }: { rooms: RoomType[] }) {
  const [processing, setProcessing] = useState(false);

  return (
    <>
      <div className="mb-4 text-2xl">Available rooms:</div>
      {rooms.length ? (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          {rooms.map((room) => {
            return <RoomCard room={room} processing={processing} setProcessing={setProcessing} key={room.id} />;
          })}
        </div>
      ) : (
        <div>No rooms</div>
      )}
    </>
  );
}
