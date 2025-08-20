import { User } from '@/types';

export interface Quote {
  id: number;
  text: string;
}

export enum RaceEvent {
  RACE_READY = 'Race.RaceReady',
  SET_FINISH_TIME = 'Race.SetFinishTime',
  ABORT_RACE = 'Race.AbortRace',
  RACE_COMPLETED = 'Race.RaceCompleted',
  RACE_NOT_COMPLETE = 'Race.RaceNotComplete',
  TOGGLE_READY_STATE = 'Race.ToggleReadyState',
  UPDATE_PROGRESS = 'Race.UpdateProgress',
  RACE_FINISHED = 'Race.RaceFinished',

  READY_FOR_RACE = 'READY_FOR_RACE',
  CANCEL_READY = 'CANCEL_READY',
}

export interface UpdateProgressProps {
  wordsPerMinute: number;
  completedCharacters: number;
  wrongCharacters: number;
  percentage: number;
}

export type RaceState = 'READY' | 'IN_PROGRESS' | 'COMPLETE' | 'NOT_COMPLETE';

export interface Race {
  state: RaceState;
  startTime: number;
  finishTime: number;
  quote: Quote;
  totalCharacters: number;
  completedCharacters: number;
  wrongCharacters: number;
}

export type Place = '' | '1st' | '2nd' | '3rd' | '4th' | '5th' | 'NC';

export interface Player extends User {
  score: number;
  averageWpm: number;
  racesWon: number;
  racesPlayed: number;

  // isReady: boolean;
  status: 'idle' | 'ready' | 'abort' | 'completed' | 'play';
}

export interface InRacePlayer {
  id: number;
  name: string;
  percentage: number;
  wordsPerMinute: number;
  finished: boolean;
  place: Place;
  status: 'play' | 'completed' | 'abort';
}

export interface UpdatedPlayerStats {
  id: number;
  score: number;
  averageWpm: number;
  racesWon: number;
  racesPlayed: number;
}

export interface Result {
  id: number;
  quote: { id: number; text: string };
  place: Place;
  wpm: number;
  total_players: number;
  accuracy_percentage: number;
  created_at: string;
}
