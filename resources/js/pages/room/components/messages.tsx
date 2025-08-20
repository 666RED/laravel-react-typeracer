import { ScrollArea } from '@/components/ui/scroll-area';
import Message from '@/pages/room/components/message';
import React, { useEffect, useRef } from 'react';
import { Message as MessageType } from '../../../types/message';

interface Props {
  messages: MessageType[];
  isExpand: boolean;
}

const Messages = React.memo(function Messages({ messages, isExpand }: Props) {
  const containerRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (containerRef.current) {
      containerRef.current.scrollIntoView();
    }
  }, [messages, isExpand]);

  return (
    <div className="flex-1 overflow-hidden">
      <ScrollArea className="h-full w-full">
        {messages.map((message) => (
          <Message message={message} key={message.id} />
        ))}
        <div ref={containerRef}></div>
      </ScrollArea>
    </div>
  );
});

export default Messages;
