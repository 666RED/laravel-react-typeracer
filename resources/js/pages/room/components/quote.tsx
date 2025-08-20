import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import axios from '@/lib/customAxios';
import { calcAccuracyPercentage, getWordsPerMinute } from '@/lib/utils';
import ReturnWaitingRoomButton from '@/pages/room/components/returnWaitingRoomButton';
import { SharedData } from '@/types';
import { InRacePlayer, Race } from '@/types/race';
import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

interface Props {
  race: Race;
  setRace: React.Dispatch<React.SetStateAction<Race>>;
  setInRacePlayers: React.Dispatch<React.SetStateAction<InRacePlayer[]>>;
}

export default function Quote({ race, setRace, setInRacePlayers }: Props) {
  const { auth } = usePage<SharedData>().props;
  const { startTime, state, totalCharacters, completedCharacters, quote, wrongCharacters } = race;

  type TextProps = {
    text: string;
    color: string;
  };

  const inputRef = useRef<HTMLInputElement>(null);

  const [words, setWords] = useState(
    quote.text.split(' ').map((word) => word.split('').map((char): TextProps => ({ text: char, color: 'text-gray-500' }))),
  );

  const immutedWords = quote.text.split(' ').map((word) => word.split('')); // used for removing red spaces

  const [currentWordIndex, setCurrentWordIndex] = useState(0); // keep track of current word
  const [input, setInput] = useState(''); // input text
  const [lastKey, setLastKey] = useState(''); // get the keyboard key code
  const [wrongIndex, setWrongIndex] = useState(-1);

  const [timeSpent, setTimeSpent] = useState(0);
  const [wordsPerMinute, setWordsPerMinute] = useState(0);

  const scrollAreaRef = useRef<HTMLDivElement>(null);

  //@ Resume progress when refresh
  useEffect(() => {
    if (completedCharacters <= 0) return;

    let count = 0;
    let index = 0;

    while (count < completedCharacters) {
      count += words[index].length;
      index++;
    }

    setWords((prev) => prev.map((word, idx) => (idx < index ? word.map((char): TextProps => ({ ...char, color: 'text-green-500' })) : word)));

    setCurrentWordIndex(index);
  }, []);

  //@ handle auto focus input after countdown reached
  useEffect(() => {
    if (state === 'IN_PROGRESS') {
      if (inputRef.current) {
        inputRef.current.focus();
      }
    }
  }, [state]);

  //@ Update scroll position
  useEffect(() => {
    const viewport = scrollAreaRef.current?.querySelector('[data-radix-scroll-area-viewport]') as HTMLElement;

    if (viewport) {
      const currentWord = document.getElementById(`word-${currentWordIndex}`);
      const ROW_HEIGHT = currentWord!.getBoundingClientRect().height;

      const currentRow = currentWord!.offsetTop / ROW_HEIGHT + 1;

      //@ if user scroll down when type the first 3 rows -> scroll to top
      if (currentRow <= 3 && viewport.scrollTop !== 0) {
        viewport.scroll({ top: 0, behavior: 'smooth' });
        return;
      }

      //@ only start auto scroll after third row
      if (currentRow > 3 && viewport.scrollTop !== 2 * ROW_HEIGHT) {
        viewport.scroll({ top: currentWord!.offsetTop - 2 * ROW_HEIGHT, behavior: 'smooth' });
      }
    }
  }, [input]);

  const handleOnChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = e.target.value;

    // ? prevent user type too long word
    if (value.length - immutedWords[currentWordIndex].length >= 10) return;

    setInput(value);
    checkKey(value);
  };

  const handleOnKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
    setLastKey(e.key);
  };

  const checkKey = (value: string) => {
    // ? if press function keys (ctry, shift, alt, etc.) -> return
    if (lastKey.length > 1 && lastKey !== 'Backspace') return;

    // ? handle Space, Backspace, Normal keys
    if (lastKey === 'Backspace') {
      handlePressBackspace(value);
    } else if (lastKey === ' ') {
      handlePressSpace(value);
    } else {
      matchTextAndInput(value);
    }
  };

  //@ HANDLE KEY FUNCTIONS
  const handlePressSpace = (value: string) => {
    const currentValue = value.trim();

    // ? if input is empty & press enter -> return
    if (currentValue.length === 0) {
      setInput('');
      return;
    }

    const currentWord = words[currentWordIndex].map((word) => word.text).join('');

    // ? input match with current word -> jump to next word
    if (currentValue === currentWord) {
      setInput('');
      setCurrentWordIndex((prev) => prev + 1); // go to next word
      setRace((prev) => ({ ...prev, completedCharacters: prev.completedCharacters + currentValue.length }));
      return;
    }

    matchTextAndInput(value);
  };

  const handlePressBackspace = (value: string) => {
    // ? if input is empty & press backspace -> return
    if (input === '') return;

    // ? handle delete char(s) (ctrl delete && shift delete included)
    const inputLength = input.length;
    const deletedCharLength = inputLength - value.length;

    for (let i = 1; i <= deletedCharLength; i++) {
      setGrayText(inputLength - i);
      // ? Reset wrong index
      if (inputLength - i === wrongIndex) {
        setWrongIndex(-1);
      }
    }
  };

  const matchTextAndInput = (value: string) => {
    const inputLength = value.length;
    const lastChar = value.split('').slice(-1)[0];

    // ? if already have mistake -> set red text / space to every incoming char
    if (wrongIndex !== -1) {
      if (inputLength > words[currentWordIndex].length) {
        setRedSpace();
      } else {
        setRedText(inputLength - 1);
      }
      return;
    }

    if (inputLength > words[currentWordIndex].length) {
      setRedSpace();
    } else {
      if (lastChar === words[currentWordIndex][inputLength - 1].text) {
        setGreenText(inputLength - 1);
      } else {
        setRedText(inputLength - 1);
        setWrongIndex(inputLength - 1);
      }
    }
  };

  //@ SET TEXT COLOR FUNCTIONS
  const setTextColor = (word: TextProps[], wordIndex: number, charIndex: number, color: string) => {
    return wordIndex === currentWordIndex ? word.map((value, index) => (index === charIndex ? { ...value, color } : value)) : word;
  };

  const setRedText = (charIndex: number) => {
    setWords((prev) => prev.map((word, wordIndex) => setTextColor(word, wordIndex, charIndex, 'text-red-500')));
    setRace((prev) => ({ ...prev, wrongCharacters: prev.wrongCharacters + 1 }));
  };

  const setRedSpace = () => {
    setWords((prev) =>
      prev.map((word, wordIndex) => (wordIndex === currentWordIndex ? [...word, { text: '_', color: 'bg-red-500 w-2 h-5' }] : word)),
    );
    setRace((prev) => ({ ...prev, wrongCharacters: prev.wrongCharacters + 1 }));
  };

  const setGreenText = (charIndex: number) => {
    setWords((prev) => prev.map((word, wordIndex) => setTextColor(word, wordIndex, charIndex, 'text-green-500')));

    // * Finished
    if (currentWordIndex === words.length - 1 && charIndex === words[currentWordIndex].length - 1) {
      handleFinishedRace();
    }
  };

  const setGrayText = (charIndex: number) => {
    // ? remove red space
    if (charIndex >= immutedWords[currentWordIndex].length) {
      setWords((prev) => prev.map((word, wordIndex) => (wordIndex === currentWordIndex ? word.slice(0, -1) : word)));
    } else {
      // ? set gray text
      setWords((prev) => prev.map((word, wordIndex) => setTextColor(word, wordIndex, charIndex, 'text-gray-500')));
    }
  };

  const handleFinishedRace = async () => {
    try {
      const time = (new Date().getTime() - new Date(startTime).getTime() * 1000) / 1000;
      const wpm = getWordsPerMinute(time, totalCharacters);
      const accuracyPercentage = calcAccuracyPercentage(totalCharacters, wrongCharacters);

      setTimeSpent(time);
      setWordsPerMinute(wpm);

      const data = {
        userId: auth.user.id,
        wordsPerMinute: wpm,
      };

      // ? Set percentage, wpm & places for finished player
      setRace((prev) => ({ ...prev, state: 'COMPLETE' }));

      setInRacePlayers((prev) =>
        prev.map((player) =>
          player.id === data.userId ? { ...player, percentage: 100, wordsPerMinute: data.wordsPerMinute, finished: true } : player,
        ),
      );

      // ? saving race result to database
      await axios.post(route('race.save'), { wpm, accuracyPercentage });
    } catch (err) {
      console.error(err);
    }
  };

  return (
    <div>
      {/* QUOTE */}
      <ScrollArea className="h-48" ref={scrollAreaRef} data-testid="quote">
        <p className="flex h-full w-full flex-wrap content-start items-start gap-x-1 text-justify text-2xl leading-12 text-wrap">
          {words.map((word, index) => (
            <span key={`word-${index}`} id={`word-${index}`}>
              {word.map((char, idx) => (
                <span
                  key={`char-${idx}`}
                  className={`${char.color} ${index === currentWordIndex ? (idx === 0 && input.length === 0 ? "relative before:absolute before:-left-0.5 before:animate-pulse before:text-gray-200 before:content-['|']" : idx === input.length - 1 ? "relative after:absolute after:-right-1 after:animate-pulse after:text-gray-200 after:content-['|']" : '') : ''}`}
                >
                  {char.text}
                </span>
              ))}
            </span>
          ))}
        </p>
        <div className="h-24"></div>
      </ScrollArea>
      {/* INPUT */}
      {race.state === 'COMPLETE' ? (
        <>
          <ReturnWaitingRoomButton />
          <div className="flex items-center justify-between text-2xl">
            <p>
              Time spent: <strong>{timeSpent}s</strong>
            </p>
            <p>
              Total characters: <strong>{race.totalCharacters}</strong>
            </p>
            <p>
              WPM: <strong>{wordsPerMinute}</strong>
            </p>
          </div>
        </>
      ) : race.state === 'NOT_COMPLETE' ? (
        <>
          <div>not complete</div>
          <ReturnWaitingRoomButton />
        </>
      ) : (
        <Input
          onKeyDown={handleOnKeyDown}
          value={input}
          onChange={handleOnChange}
          autoFocus
          onPaste={(e) => e.preventDefault()}
          disabled={state !== 'IN_PROGRESS'}
          ref={inputRef}
          data-testid="typing-area"
        />
      )}
    </div>
  );
}
