import { Separator } from '@/components/ui/separator';
import CurrentRoomCard from '@/pages/home/components/currentRoomCard';
import { useState } from 'react';

export default function CurrentRoom() {
  const [processing, setProcessing] = useState(false);

  return (
    <div className="mb-4">
      <div className="mb-4 text-2xl">Current room</div>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <CurrentRoomCard processing={processing} setProcessing={setProcessing} />
        <Separator className="col-span-full my-6 bg-secondary-foreground" />
      </div>
    </div>
  );
}
