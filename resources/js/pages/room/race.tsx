import RaceMainContent from '@/pages/room/components/raceMainContent';
import RoomLayout from '@/pages/room/layouts/roomLayout';
import { InRacePlayer, Quote } from '@/types/race';

interface Props {
  startTime: number;
  finishTime: number;
  quote: Quote;
  inRacePlayers: InRacePlayer[];
  completedCharacters: number;
  wrongCharacters: number;
}

export default function Race({ startTime, finishTime, quote, inRacePlayers, completedCharacters, wrongCharacters }: Props) {
  return (
    <RoomLayout title="Race page" description="This is race page">
      <RaceMainContent
        startTime={startTime}
        finishTime={finishTime}
        quote={quote}
        inRacePlayers={inRacePlayers}
        completedCharacters={completedCharacters}
        wrongCharacters={wrongCharacters}
      />
    </RoomLayout>
  );
}
