import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

export function getWordsPerMinute(timeSpent: number, characters: number) {
  return Number(((characters * (60 / timeSpent)) / 5).toFixed(1));
}

export function calcAccuracyPercentage(totalCharacters: number, wrongCharacters: number) {
  return Number(((totalCharacters / (totalCharacters + wrongCharacters)) * 100).toFixed(2));
}

export function getMultiplesOfTen(num: number) {
  return Math.floor(num / 10) * 10;
}
