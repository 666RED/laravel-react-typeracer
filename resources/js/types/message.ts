export interface Message {
  id: string;
  senderId: number;
  senderName: string;
  text: string;
  isNotification: boolean;
}

export enum MessageEvent {
  MESSAGE_SENT = 'Message.MessageSent',
}
