import SpectateMainContent from '@/pages/room/components/spectateMainContent';
import RoomLayout from '@/pages/room/layouts/roomLayout';
import { InRacePlayer, Player, Quote } from '@/types/race';

interface Props {
  players: Player[];
  inRacePlayers: InRacePlayer[];
  startTime: number;
  finishTime: number;
  quote: Quote;
}

export default function Spectate({ players, inRacePlayers, startTime, finishTime, quote }: Props) {
  return (
    <RoomLayout title="Spectate race" description="This is spectate race page">
      <SpectateMainContent players={players} inRacePlayers={inRacePlayers} startTime={startTime} finishTime={finishTime} quote={quote} />
    </RoomLayout>
  );
}
