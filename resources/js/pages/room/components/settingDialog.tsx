import FormSubmitButton from '@/components/formSubmitButton';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { SharedData } from '@/types';
import { Player } from '@/types/race';
import { router, usePage } from '@inertiajs/react';
import { CopyIcon, InfoIcon, SettingsIcon } from 'lucide-react';
import { FormEventHandler, useState } from 'react';
import { toast } from 'sonner';

interface Props {
  players: Player[];
}

export default function SettingDialog({ players }: Props) {
  const { currentRoom } = usePage<SharedData>().props;

  const [roomSetting, setRoomSetting] = useState({
    roomId: currentRoom.id,
    maxPlayer: currentRoom.maxPlayer.toString(),
    name: currentRoom.name,
    private: currentRoom.private,
    owner: currentRoom.owner.toString(),
  });

  const [processing, setProcessing] = useState(false);

  const [open, setOpen] = useState(false);

  const fieldClassName = 'flex items-center gap-x-4';
  const radioItemClassName = 'text-sm cursor-pointer hover:bg-muted';

  const handleCopyRoomId = () => {
    navigator.clipboard.writeText(roomSetting.roomId);
    toast.info('Copied');
  };

  const handleSave: FormEventHandler = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.patch(
      route('room.update'),
      { ...roomSetting, owner: parseInt(roomSetting.owner), maxPlayer: parseInt(roomSetting.maxPlayer) },
      {
        onFinish: () => {
          setOpen(false);
          setProcessing(false);
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button data-testid="room-setting-icon">
          <SettingsIcon />
        </Button>
      </DialogTrigger>
      <DialogContent>
        <form className="flex flex-col gap-y-5" onSubmit={handleSave}>
          <DialogHeader>
            <DialogTitle>Room Setting</DialogTitle>
            <DialogDescription>Adjust room setting</DialogDescription>
          </DialogHeader>
          {/* ROOM ID */}
          <div className={fieldClassName}>
            <Label className="w-1/5" htmlFor="new-room-id">
              Room ID
            </Label>
            <div className="relative flex w-full items-center">
              <Input
                type="text"
                readOnly
                value={roomSetting.roomId}
                name="new-room-id"
                id="new-room-id"
                className="flex-1 cursor-pointer text-sm"
                onClick={handleCopyRoomId}
              />
              <CopyIcon className="absolute right-3 cursor-pointer" size="20" onClick={handleCopyRoomId} />
            </div>
          </div>
          {/* ROOM NAME */}
          <div className={fieldClassName}>
            <Label className="w-1/5" htmlFor="room-name">
              Name
            </Label>
            <Input
              type="text"
              value={roomSetting.name}
              name="room-name"
              id="room-name"
              required
              maxLength={50}
              placeholder="Enter room name"
              autoFocus
              onChange={(e) => setRoomSetting((prev) => ({ ...prev, name: e.target.value }))}
              autoComplete="off"
              className="text-sm"
            />
          </div>
          {/* # PLAYER */}
          <div className={fieldClassName}>
            <Label className="w-1/6"># Players</Label>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant={'outline'} className="text-sm" data-testid="max-player-menu-trigger">
                  {roomSetting.maxPlayer}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuRadioGroup
                  value={roomSetting.maxPlayer}
                  onValueChange={(e) =>
                    setRoomSetting((prev) => ({
                      ...prev,
                      maxPlayer: e,
                    }))
                  }
                >
                  <DropdownMenuRadioItem value="2" className={radioItemClassName} disabled={currentRoom.playerCount > 2}>
                    2
                  </DropdownMenuRadioItem>
                  <DropdownMenuRadioItem value="3" className={radioItemClassName} disabled={currentRoom.playerCount > 3}>
                    3
                  </DropdownMenuRadioItem>
                  <DropdownMenuRadioItem value="4" className={radioItemClassName} disabled={currentRoom.playerCount > 4}>
                    4
                  </DropdownMenuRadioItem>
                  <DropdownMenuRadioItem value="5" className={radioItemClassName}>
                    5
                  </DropdownMenuRadioItem>
                </DropdownMenuRadioGroup>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
          {/* OWNER */}
          <div className={fieldClassName}>
            <Label className="w-1/6">Owner</Label>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant={'outline'} className="text-sm" data-testid="owner-menu-trigger">
                  {players.find((player) => player?.id?.toString() === roomSetting.owner)?.name}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuRadioGroup
                  value={roomSetting.owner}
                  onValueChange={(e) =>
                    setRoomSetting((prev) => ({
                      ...prev,
                      owner: e,
                    }))
                  }
                >
                  {players.map((player) => (
                    <DropdownMenuRadioItem value={player?.id?.toString()} className={radioItemClassName} key={player.id} data-testid>
                      {player.name}
                    </DropdownMenuRadioItem>
                  ))}
                </DropdownMenuRadioGroup>
              </DropdownMenuContent>
            </DropdownMenu>
            <Tooltip>
              <TooltipTrigger asChild>
                <InfoIcon size="20" />
              </TooltipTrigger>
              <TooltipContent>
                <p>Transfer ownership to another player</p>
              </TooltipContent>
            </Tooltip>
          </div>
          {/* PUBLIC / PRIVATE */}
          <div className={fieldClassName}>
            <Switch
              id="private-room"
              checked={roomSetting.private}
              onCheckedChange={() =>
                setRoomSetting((prev) => {
                  return {
                    ...prev,
                    private: !prev.private,
                  };
                })
              }
              className="cursor-pointer"
            />
            <Label htmlFor="private-room">Private room</Label>
          </div>
          <DialogFooter>
            <FormSubmitButton text="Save" processing={processing} />
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
