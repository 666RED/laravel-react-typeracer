import { Message as MessageType } from '../../../types/message';

export default function Message({ message }: { message: MessageType }) {
  return (
    <div className="h-full w-full">
      <p>
        {message.isNotification ? (
          <span className="text-purple-800">{message.text}</span>
        ) : (
          <>
            <span className="text-blue-600">{message.senderName}: </span>
            <span>{message.text}</span>
          </>
        )}
      </p>
    </div>
  );
}
