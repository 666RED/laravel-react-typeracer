import FormSubmitButton from '@/components/formSubmitButton';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';
import { v4 as uuidv4 } from 'uuid';

type NewRoomState = {
  roomId: string;
  name: string;
  playerCount: string;
  private: boolean;
};

export default function CreateNewRoomForm() {
  const { currentRoom } = usePage<SharedData>().props;

  const [newRoom, setNewRoom] = useState<NewRoomState>({
    roomId: uuidv4(),
    name: '',
    playerCount: '2',
    private: false,
  });

  const [processing, setProcessing] = useState(false);

  const handleCreateRoom: FormEventHandler = (e) => {
    e.preventDefault();
    setProcessing(true);

    router.post(
      route('room.create'),
      { ...newRoom, playerCount: parseInt(newRoom.playerCount) },
      {
        onFinish: () => {
          setProcessing(false);
        },
      },
    );
  };

  const fieldClassName = 'flex items-center gap-x-4';
  const radioItemClassName = 'text-sm cursor-pointer hover:bg-muted';

  return (
    <Dialog data-testid="create-room-dialog">
      <DialogTrigger asChild>
        {currentRoom ? (
          <Tooltip>
            <TooltipTrigger asChild>
              <Button className="opacity-50">Create new room</Button>
            </TooltipTrigger>
            <TooltipContent>
              <p>You have joined a room</p>
            </TooltipContent>
          </Tooltip>
        ) : (
          <Button>Create new room</Button>
        )}
      </DialogTrigger>
      <DialogContent>
        <form onSubmit={handleCreateRoom} className="flex flex-col gap-y-5">
          <DialogHeader>
            <DialogTitle>Create new room</DialogTitle>
            <DialogDescription>Adjust the room settings</DialogDescription>
          </DialogHeader>
          {/* ROOM ID */}
          <div className={fieldClassName}>
            <Label className="w-1/5" htmlFor="new-room-id">
              Room ID
            </Label>
            <Input type="text" readOnly value={newRoom.roomId} name="new-room-id" id="new-room-id" className="text-sm" />
          </div>
          {/* ROOM NAME */}
          <div className={fieldClassName}>
            <Label className="w-1/5" htmlFor="room-name">
              Name
            </Label>
            <Input
              type="text"
              value={newRoom.name}
              name="room-name"
              id="room-name"
              required
              maxLength={50}
              placeholder="Enter room name"
              autoFocus
              onChange={(e) => setNewRoom((prev) => ({ ...prev, name: e.target.value }))}
              autoComplete="off"
              className="text-sm"
            />
          </div>
          {/* # PLAYERS */}
          <div className={fieldClassName}>
            <Label className="w-1/6"># Players</Label>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant={'outline'} className="text-sm" data-testid="max-player-menu-trigger">
                  {newRoom.playerCount}
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                <DropdownMenuRadioGroup
                  value={newRoom.playerCount}
                  onValueChange={(e) =>
                    setNewRoom((prev) => ({
                      ...prev,
                      playerCount: e,
                    }))
                  }
                >
                  <DropdownMenuRadioItem value="2" className={radioItemClassName}>
                    2
                  </DropdownMenuRadioItem>
                  <DropdownMenuRadioItem value="3" className={radioItemClassName}>
                    3
                  </DropdownMenuRadioItem>
                  <DropdownMenuRadioItem value="4" className={radioItemClassName}>
                    4
                  </DropdownMenuRadioItem>
                  <DropdownMenuRadioItem value="5" className={radioItemClassName}>
                    5
                  </DropdownMenuRadioItem>
                </DropdownMenuRadioGroup>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
          {/* PUBLIC / PRIVATE */}
          <div className={fieldClassName}>
            <Switch
              id="private-room"
              checked={newRoom.private}
              onCheckedChange={() =>
                setNewRoom((prev) => {
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
          {/* CRATE BUTTON */}
          <DialogFooter>
            <FormSubmitButton
              processing={processing}
              text="Create"
              className="mx-0 w-20"
              disabled={newRoom.name === ''}
              data-testid="create-room-button"
            />
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
