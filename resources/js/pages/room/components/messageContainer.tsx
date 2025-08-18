import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Messages from '@/pages/room/components/messages';
import { SharedData } from '@/types';
import { Message, MessageEvent } from '@/types/message';
import { useForm, usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { ChevronLeftIcon, ChevronRightIcon, SendHorizontalIcon } from 'lucide-react';
import { FormEventHandler } from 'react';

interface Props {
  messages: Message[];
  setMessages: React.Dispatch<React.SetStateAction<Message[]>>;
  isExpand: boolean;
  setIsExpand: React.Dispatch<React.SetStateAction<boolean>>;
}

export default function MessageContainer({ messages, setMessages, isExpand, setIsExpand }: Props) {
  const { auth, currentRoom } = usePage<SharedData>().props;

  const { post, data, setData, reset, processing } = useForm({
    text: '',
    senderId: auth.user.id,
    senderName: auth.user.name,
    isNotification: false,
  });

  // ? getting new messages
  useEcho<Message>(`room.${currentRoom.id}`, MessageEvent.MESSAGE_SENT, (e) => {
    setMessages((prev) => [...prev, e]);
  });

  const handleSendMessage: FormEventHandler = (e) => {
    e.preventDefault();

    post(route('message.send-message'), {
      onSuccess: () => {
        reset();
      },
    });
  };

  const expandClassName = 'cursor-pointer text-white hover:bg-neutral-200 h-full transition-all rounded-l-xl';

  return (
    <div
      className={`fixed right-0 bottom-0 flex h-64 max-h-64 flex-row items-center ${isExpand ? 'w-2/5' : 'w-fit'}`}
      data-testid="room-message-container"
    >
      {/* EXPAND / COLLAPSE BUTTONS */}
      {isExpand ? (
        <ChevronRightIcon className={expandClassName} onClick={() => setIsExpand(false)} />
      ) : (
        <ChevronLeftIcon className={expandClassName} onClick={() => setIsExpand(true)} />
      )}

      <div className={`h-full flex-1 flex-col gap-2 border border-black bg-gray-200 p-2 text-black ${isExpand ? 'flex' : 'hidden'} `}>
        {/* MESSAGES */}
        <Messages messages={messages} isExpand={isExpand} />
        {/* SEND MESSAGE */}
        <form className="flex items-center gap-2" onSubmit={handleSendMessage} autoComplete="off">
          <Input
            maxLength={500}
            type="text"
            name="new-message"
            id="new-message"
            value={data.text}
            onChange={(e) =>
              setData((prev) => ({
                ...prev,
                text: e.target.value,
              }))
            }
            required
            className="flex-1"
          />
          <Button variant="secondary" size="icon" type="submit" disabled={processing}>
            <SendHorizontalIcon />
          </Button>
        </form>
      </div>
    </div>
  );
}
