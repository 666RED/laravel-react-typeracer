import { Progress } from '@/components/ui/progress';
import { InRacePlayer } from '@/types/race';
import { useEffect } from 'react';

interface Props {
  inRacePlayers: InRacePlayer[];
}

export default function PercentageRow({ inRacePlayers }: Props) {
  // ? Update grid style
  useEffect(() => {
    document.getElementById('percentage-grid')!.style.gridTemplateColumns = `repeat(${inRacePlayers.length}, minmax(0, 1fr))`;
  }, [inRacePlayers]);

  return (
    <div className="flex-1">
      <div className="grid" id="percentage-grid">
        {inRacePlayers.map((player) => (
          <div className="mx-auto flex flex-col items-center gap-y-3" key={player.id} data-testid="in-race-player">
            <Progress value={player.percentage} color={player.finished ? '' : `bg-primary/50`} />
            <div>{player.name}</div>
            <div>{player.wordsPerMinute} WPM</div>
            <div className="text-2xl">{player.status === 'abort' ? 'Abort' : player.place}</div>
          </div>
        ))}
      </div>
    </div>
  );
}
