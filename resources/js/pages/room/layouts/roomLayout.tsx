import BaseLayout from '@/layouts/baseLayout';
import MessageContainer from '@/pages/room/components/messageContainer';
import { LayoutProps, SharedData } from '@/types';
import { RoomLayoutValues } from '@/types/room';
import { usePage } from '@inertiajs/react';
import { createContext, useState } from 'react';

export const RoomLayoutContext = createContext<RoomLayoutValues | null>(null);

export default function RoomLayout({ children, title, description }: LayoutProps) {
  const sharedProps = usePage<SharedData>().props;
  const [messages, setMessages] = useState(sharedProps.messages);
  const [isExpand, setIsExpand] = useState(true);

  const currentRoomId = `room.${sharedProps.currentRoom.id}`;

  const contextValues = {
    messages,
    setMessages,
    isExpand,
    setIsExpand,
    currentRoomId,
  };

  return (
    <RoomLayoutContext value={contextValues}>
      <BaseLayout title={title} description={description}>
        <div className="flex h-full flex-col gap-y-6">
          {children}
          {/* MESSAGE */}
          <MessageContainer messages={messages} setMessages={setMessages} isExpand={isExpand} setIsExpand={setIsExpand} />
        </div>
      </BaseLayout>
    </RoomLayoutContext>
  );
}
