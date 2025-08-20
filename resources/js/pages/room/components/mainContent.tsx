import ButtonWithLoader from '@/components/buttonWithLoader';
import { Button } from '@/components/ui/button';
import axios from '@/lib/customAxios';
import SettingHeader from '@/pages/room/components/settingHeader';
import { RoomLayoutContext } from '@/pages/room/layouts/roomLayout';
import { SharedData } from '@/types';
import { Player, RaceEvent, UpdatedPlayerStats } from '@/types/race';
import { RoomEvent, UpdatePlayerCountProps } from '@/types/room';
import { router, usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { CrownIcon } from 'lucide-react';
import { useContext, useState } from 'react';
import { v4 as uuidv4 } from 'uuid';

export default function MainContent() {
  const props = usePage<SharedData>().props;

  const { currentRoom, auth } = props;

  const { currentRoomId } = useContext(RoomLayoutContext)!;

  const [inRacePlayerIds, setInRacePlayerIds] = useState<number[]>(props.racePlayerIds as number[]);
  const [processing, setProcessing] = useState(false);
  const [startRaceProcessing, setStartRaceProcessing] = useState(false);
  const [players, setPlayers] = useState(props.players as Player[]);
  const { setMessages } = useContext(RoomLayoutContext)!;

  // * ROOM RELATED * //
  useEcho<UpdatePlayerCountProps & { player: Player }>(currentRoomId, RoomEvent.JOIN_ROOM, (e) => {
    setPlayers((prev) => [...prev, e.player]);

    setMessages((prev) => [
      ...prev,
      {
        id: uuidv4(),
        senderId: e.player.id,
        senderName: e.player.name,
        text: `${e.player.name} joined`,
        isNotification: true,
      },
    ]);
    if (e.playerId !== auth?.user.id) {
      router.reload(); // update currentRoom (exclude self)
    }
  });

  useEcho<UpdatePlayerCountProps & { playerName: string }>(currentRoomId, RoomEvent.LEAVE_ROOM, (e) => {
    if (e.playerId === auth?.user.id) {
      return;
    }

    setMessages((prev) => [
      ...prev,
      {
        id: uuidv4(),
        senderId: e.playerId,
        senderName: e.playerName,
        text: `${e.playerName} leave`,
        isNotification: true,
      },
    ]);
    setPlayers((prev) => prev.filter((player) => player.id !== e.playerId));
    router.reload(); // update currentRoom
  });

  //@ Update leaderboard
  useEcho<UpdatedPlayerStats>(currentRoomId, RoomEvent.UPDATE_PLAYER_STATS, (e) => {
    setPlayers((prev) =>
      prev.map((player) =>
        player.id === e.id ? { ...player, score: e.score, racesPlayed: e.racesPlayed, racesWon: e.racesWon, averageWpm: e.averageWpm } : player,
      ),
    );
  });

  //@ Delete room
  useEcho<{ owner: number }>(currentRoomId, RoomEvent.DELETE_ROOM, (e) => {
    if (auth?.user.id !== e.owner) {
      router.post(route('room.remove'), { message: 'Current room deleted by room owner' });
    }
  });

  //@ Remove inactive room
  useEcho(currentRoomId, RoomEvent.REMOVE_INACTIVE_ROOM, () => {
    router.post(route('room.remove'), { message: 'Current room is removed due to inactiveness for more than 2 hours' });
  });

  //@ Transfer ownership
  useEcho<{ newOwner: number; newOwnerName: string }>(currentRoomId, RoomEvent.TRANSFER_OWNERSHIP, (e) => {
    if (auth?.user.id === e.newOwner) {
      router.reload();
    }
  });

  //@ Update room setting
  useEcho<{ owner: number }>(currentRoomId, RoomEvent.UPDATE_ROOM_SETTING, (e) => {
    router.reload();
    setPlayers((prev) => prev.map((player) => (player.id === e.owner ? { ...player, status: 'idle' } : player)));
  });
  // * ROOM RELATED * //

  // * ============ * //
  // * ============ * //
  // * ============ * //

  // * RACE RELATED * //
  //@ Navigate all players to race page
  useEcho<{ playerIds: number[] }>(currentRoomId, RaceEvent.RACE_READY, (e) => {
    if (!e.playerIds.includes(auth?.user.id)) {
      setInRacePlayerIds([...e.playerIds]);
      setPlayers((prev) => prev.map((player) => (e.playerIds.includes(player.id) ? { ...player, status: 'play' } : player)));
      return;
    }

    router.get(route('room.show-race', { roomId: currentRoom.id }));
  });

  const handleStartNewRace = async () => {
    try {
      setStartRaceProcessing(true);
      await axios.post(route('race.race-ready'));
      setStartRaceProcessing(false);
    } catch (err) {
      console.error(err);
    }
  };

  //@ Ready / Cancel
  const handleReady = async () => {
    try {
      setProcessing(true);
      await axios.post(route('race.toggle-ready-state'));
    } catch (err) {
      console.error(err);
    } finally {
      setProcessing(false);
    }
  };

  //@ Navigate to spectate race
  const handleSpectateRace = () => {
    router.get(route('room.spectate-race', { roomId: currentRoom.id }));
  };

  //@ Toggle users' ready state
  useEcho<{ playerId: number; isReady: boolean }>(currentRoomId, RaceEvent.TOGGLE_READY_STATE, (e) => {
    const { playerId, isReady } = e;

    setPlayers((prev) => prev.map((player) => (player.id === playerId ? { ...player, status: isReady ? 'ready' : 'idle' } : player)));
  });

  //@ Update view for players in waiting room after race finished
  useEcho(currentRoomId, RaceEvent.RACE_FINISHED, (e) => {
    setInRacePlayerIds([]);
    setPlayers((prev) => prev.map((player) => ({ ...player, status: 'idle' })));
  });

  //@ Abort race
  useEcho<{ playerId: number }>(currentRoomId, [RaceEvent.ABORT_RACE], (e) => {
    setInRacePlayerIds((prev) => prev.filter((id) => id !== e.playerId));

    setPlayers((prev) => prev.map((player) => (player.id === e.playerId && player.status === 'play' ? { ...player, status: 'abort' } : player)));
  });

  //@ Race completed
  useEcho<{ playerId: number }>(currentRoomId, [RaceEvent.RACE_COMPLETED], (e) => {
    setInRacePlayerIds((prev) => prev.filter((id) => id !== e.playerId));
    setPlayers((prev) => prev.map((player) => (player.id === e.playerId ? { ...player, status: 'completed' } : player)));
  });
  // * RACE RELATED * //

  return (
    <div data-testid="room-main-content" className="contents">
      {/* SETTING HEADER */}
      <SettingHeader players={players} />
      {/* START NEW RACE BUTTON */}
      {auth?.user.id === currentRoom.owner && (
        <ButtonWithLoader
          onClick={handleStartNewRace}
          disabled={players.filter((player) => player.status === 'ready').length < 1}
          text="New race"
          processing={startRaceProcessing}
          data-testid="start-race-button"
        />
      )}
      {/* PLAYERS */}
      <div className="flex items-center gap-x-4" data-testid="room-players">
        {players.map((player) => (
          <div
            className="flex h-40 flex-col items-center gap-y-4 rounded-xl border bg-primary-foreground p-6"
            key={player.id}
            data-testid="room-player"
          >
            <div className="flex items-center gap-x-2">
              {player.id === currentRoom.owner && <CrownIcon size={20} />}
              {player.name}
            </div>
            <div className="contents" data-testid="player-status">
              {player.status === 'play' ? (
                <p className="text-yellow-500">In race</p>
              ) : player.status === 'abort' ? (
                <p className={'text-yellow-500'}>Abort</p>
              ) : player.status === 'completed' ? (
                <p className={'text-yellow-500'}>Completed</p>
              ) : (
                <p className={`text-green-500 ${player.status === 'ready' ? 'visible' : 'invisible'}`}>Ready</p>
              )}
            </div>

            {player.id !== currentRoom.owner &&
              player.id === auth?.user.id &&
              inRacePlayerIds.length <= 0 &&
              (player.status === 'ready' ? (
                <Button onClick={handleReady} disabled={processing} className="bg-red-600 text-white hover:bg-red-400">
                  Cancel
                </Button>
              ) : (
                <Button onClick={handleReady} disabled={processing} className="bg-green-600 text-white hover:bg-green-400">
                  Ready
                </Button>
              ))}
          </div>
        ))}
      </div>
      {/* SPECTATE BUTTON */}
      {inRacePlayerIds.length > 0 && (
        <Button onClick={handleSpectateRace} className="inline">
          Spectate
        </Button>
      )}
    </div>
  );
}
