import { useRaceEventListener } from '@/hooks/useRaceEventListener';
import axios from '@/lib/customAxios';
import { calcAccuracyPercentage } from '@/lib/utils';
import PercentageRow from '@/pages/room/components/percentageRow';
import Quote from '@/pages/room/components/quote';
import { RoomLayoutContext } from '@/pages/room/layouts/roomLayout';
import { InRacePlayer, Quote as QuoteType, RaceEvent } from '@/types/race';
import { useEcho } from '@laravel/echo-react';
import { useContext, useEffect, useState } from 'react';

interface Props {
  startTime: number;
  finishTime: number;
  quote: QuoteType;
  inRacePlayers: InRacePlayer[];
  completedCharacters: number;
  wrongCharacters: number;
}

export default function RaceMainContent(props: Props) {
  const { currentRoomId, setIsExpand } = useContext(RoomLayoutContext)!;

  const [countdown, setCountdown] = useState(5);
  const [finishCountdown, setFinishCountdown] = useState(-1);

  const { inRacePlayers, setInRacePlayers, race, setRace } = useRaceEventListener(props);
  const { startTime, finishTime, state, totalCharacters, wrongCharacters } = race;

  // ? Set finish time
  useEcho<{ finishTime: number }>(currentRoomId, RaceEvent.SET_FINISH_TIME, (e) => {
    setRace((prev) => ({ ...prev, finishTime: e.finishTime }));
  });

  // ? handle countdown
  useEffect(() => {
    const now = new Date().getTime();
    const start = new Date(startTime).getTime() * 1000;

    const duration = Math.floor((start - now) / 1000); // in seconds

    setCountdown(duration);

    const id = setInterval(() => {
      setCountdown((prev) => {
        if (prev <= 1) {
          clearInterval(id);
          return 0;
        } else {
          return prev - 1;
        }
      });
    }, 1000);

    return () => clearInterval(id);
  }, []);

  // ? set race state to IN_PROGRESS
  useEffect(() => {
    if (state === 'READY' && countdown <= 0) {
      setRace((prev) => ({ ...prev, state: 'IN_PROGRESS' }));
    }
  }, [state, countdown]);

  // ? handle finish countdown
  useEffect(() => {
    if (finishTime >= 0) {
      const now = new Date().getTime();
      const finish = new Date(finishTime).getTime() * 1000;

      const duration = Math.floor((finish - now) / 1000); // in seconds

      setFinishCountdown(duration);

      const id = setInterval(() => {
        setFinishCountdown((prev) => {
          if (prev < 1) {
            clearInterval(id);
            return -1;
          } else {
            return prev - 1;
          }
        });
      }, 1000);

      return () => clearInterval(id);
    }
  }, [finishTime]);

  // ? Race not complete after finish countdown
  useEffect(() => {
    const handleSaveNotComplete = async () => {
      try {
        if (state === 'IN_PROGRESS' && finishCountdown === 0) {
          setRace((prev) => ({ ...prev, state: 'NOT_COMPLETE' }));

          const accuracyPercentage = calcAccuracyPercentage(totalCharacters, wrongCharacters);

          await axios.post(route('race.save-not-complete'), { accuracyPercentage });
        }
      } catch (err) {
        console.error(err);
      }
    };

    handleSaveNotComplete();
  }, [state, finishCountdown]);

  // ? Close message container
  useEffect(() => {
    setIsExpand(false);
  }, []);

  return (
    <div data-testid="race-main-content" className="contents">
      {/* COUNTDOWN */}
      {state === 'READY' && (
        <div className="fixed top-0 right-0 bottom-0 left-0 z-10 flex items-center justify-center">
          <div className="text-9xl transition-all">{countdown}</div>
        </div>
      )}
      {/* PERCENTAGE ROW */}
      <PercentageRow inRacePlayers={inRacePlayers} />
      {/* QUOTE */}
      <Quote race={race} setRace={setRace} setInRacePlayers={setInRacePlayers} />
      {/* FINISH COUNTDOWN */}
      {finishCountdown >= 0 && <p className="fixed top-1/3 right-2">{finishCountdown}</p>}
    </div>
  );
}
