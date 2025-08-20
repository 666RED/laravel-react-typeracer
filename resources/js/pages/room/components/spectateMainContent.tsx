import { useRaceEventListener } from '@/hooks/useRaceEventListener';
import PercentageRow from '@/pages/room/components/percentageRow';
import ReturnWaitingRoomButton from '@/pages/room/components/returnWaitingRoomButton';
import SettingHeader from '@/pages/room/components/settingHeader';
import { RoomLayoutContext } from '@/pages/room/layouts/roomLayout';
import { InRacePlayer, Player, Quote } from '@/types/race';
import { useContext, useEffect } from 'react';

interface Props {
  players: Player[];
  inRacePlayers: InRacePlayer[];
  startTime: number;
  finishTime: number;
  quote: Quote;
}

export default function SpectateMainContent(props: Props) {
  const { setIsExpand } = useContext(RoomLayoutContext)!;

  useEffect(() => {
    setIsExpand(false);
  }, []);

  const { inRacePlayers } = useRaceEventListener(props);

  return (
    <div data-testid="spectate-main-content" className="contents">
      <SettingHeader players={props.players} />
      <p className="text-3xl" data-testid="spectate-title">
        Spectating
      </p>
      <PercentageRow inRacePlayers={inRacePlayers} />
      <ReturnWaitingRoomButton />
    </div>
  );
}
