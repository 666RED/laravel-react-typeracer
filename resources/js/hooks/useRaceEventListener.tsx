import axios from '@/lib/customAxios';
import { getWordsPerMinute } from '@/lib/utils';
import { RoomLayoutContext } from '@/pages/room/layouts/roomLayout';
import { SharedData } from '@/types';
import { InRacePlayer, Place, Quote, Race, RaceEvent, UpdateProgressProps } from '@/types/race';
import { usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { useContext, useEffect, useRef, useState } from 'react';

interface Props {
  inRacePlayers: InRacePlayer[];
  startTime: number;
  finishTime: number;
  quote: Quote;
  completedCharacters?: number;
  wrongCharacters?: number;
}

export function useRaceEventListener(props: Props) {
  const [race, setRace] = useState<Race>({
    state: 'READY',
    startTime: props.startTime,
    finishTime: props.finishTime,
    quote: props.quote,
    totalCharacters: props.quote.text.split('').filter((char) => char !== ' ').length,
    completedCharacters: props.completedCharacters || 0,
    wrongCharacters: props.wrongCharacters || 0,
  });

  const { auth } = usePage<SharedData>().props;
  const [inRacePlayers, setInRacePlayers] = useState<InRacePlayer[]>(props.inRacePlayers);
  const intervalRef = useRef<NodeJS.Timeout | null>(null);
  const { state, startTime, totalCharacters } = race;

  const { currentRoomId } = useContext(RoomLayoutContext)!;

  const completedCharactersRef = useRef<number | null>(null);
  const wrongCharactersRef = useRef<number | null>(null);

  // ? update completed characters
  useEffect(() => {
    completedCharactersRef.current = race.completedCharacters;
  }, [race.completedCharacters]);

  // ? update wrong characters
  useEffect(() => {
    wrongCharactersRef.current = race.wrongCharacters;
  }, [race.wrongCharacters]);

  // ? handle updating players percentages
  useEffect(() => {
    if (state !== 'IN_PROGRESS') return;

    intervalRef.current = setInterval(() => {
      (async () => {
        try {
          const seconds = (new Date().getTime() - new Date(startTime).getTime() * 1000) / 1000;

          const percentage = (completedCharactersRef.current! / totalCharacters) * 100;
          const wordsPerMinute = getWordsPerMinute(seconds, completedCharactersRef.current!);

          setInRacePlayers((prev) => prev.map((player) => (player.id === auth?.user.id ? { ...player, percentage, wordsPerMinute } : player)));

          const data: UpdateProgressProps = {
            percentage,
            wordsPerMinute,
            completedCharacters: completedCharactersRef.current!,
            wrongCharacters: wrongCharactersRef.current!,
          };

          await axios.post(route('race.update-progress'), data);
        } catch (err) {
          console.error(err);
        }
      })();
    }, 2000);

    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [state]);

  // ? Update players percentage & wpm
  useEcho<{ userId: number; percentage: number; wordsPerMinute: number }>(currentRoomId, RaceEvent.UPDATE_PROGRESS, (e) => {
    const { userId, percentage, wordsPerMinute } = e;

    if (userId === auth?.user.id) return;

    setInRacePlayers((prev) => prev.map((player) => (player.id === userId ? { ...player, percentage, wordsPerMinute } : player)));
  });

  // ? Update completed player percentage
  useEcho<{ playerId: number; wordsPerMinute: number; place: Place }>(currentRoomId, RaceEvent.RACE_COMPLETED, (e) => {
    const { playerId, wordsPerMinute, place } = e;
    setInRacePlayers((prev) =>
      prev.map((player) => (player.id === playerId ? { ...player, percentage: 100, finished: true, wordsPerMinute, place } : player)),
    );
  });

  // ? Update not completed player status & place
  useEcho<{ playerId: number }>(currentRoomId, RaceEvent.RACE_NOT_COMPLETE, (e) => {
    setInRacePlayers((prev) => prev.map((player) => (player.id === e.playerId ? { ...player, place: 'NC' } : player)));
  });

  // ? Update abort player status & place
  useEcho<{ playerId: number }>(currentRoomId, RaceEvent.ABORT_RACE, (e) => {
    setInRacePlayers((prev) => prev.map((player) => (player.id === e.playerId ? { ...player, status: 'abort', place: 'NC' } : player)));
  });

  return { inRacePlayers, setInRacePlayers, race, setRace };
}
