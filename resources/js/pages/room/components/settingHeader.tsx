import LeaveRoomDialog from '@/components/leaveRoomDialog';
import DeleteRoomDialog from '@/pages/room/components/deleteRoomDialog';
import LeaderBoard from '@/pages/room/components/leaderBoard';
import SettingDialog from '@/pages/room/components/settingDialog';
import { SharedData } from '@/types';
import { Player } from '@/types/race';
import { usePage } from '@inertiajs/react';

interface Props {
  players: Player[];
}

export default function SettingHeader({ players }: Props) {
  const { currentRoom, auth } = usePage<SharedData>().props;

  return (
    <div className="flex items-center justify-between">
      <div className="text-2xl" data-testid="room-name">
        {currentRoom.name}
      </div>
      <div className="flex items-center justify-end gap-x-2">
        {currentRoom?.owner === auth?.user.id && <SettingDialog players={players} />}
        <LeaveRoomDialog owner={currentRoom.owner} />
        {currentRoom?.owner === auth?.user.id && <DeleteRoomDialog />}
        <LeaderBoard players={players} />
      </div>
    </div>
  );
}
