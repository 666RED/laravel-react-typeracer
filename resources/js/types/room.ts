import { Message } from '@/types/message';

export interface Room {
  id: string;
  name: string;
  owner: number;
  maxPlayer: number;
  playerCount: number;
  private: boolean;
}

export enum RoomEvent {
  NEW_ROOM_CREATED = 'Room.NewRoomCreated',

  JOIN_ROOM = 'Room.JoinRoom',
  LEAVE_ROOM = 'Room.LeaveRoom',
  DELETE_ROOM = 'Room.DeleteRoom',
  REMOVE_INACTIVE_ROOM = 'Room.RemoveInactiveRoomEvent',

  TRANSFER_OWNERSHIP = 'Room.TransferOwnership',

  UPDATE_ROOM_SETTING = 'Room.UpdateRoomSetting',
  UPDATE_PLAYER_STATS = 'Room.UpdatePlayerStats',

  UPDATE_ROOM_IN_LOBBY = 'Room.UpdateRoomInLobby',
  REMOVE_ROOM_IN_LOBBY = 'Room.RemoveRoomInLobby',
}

export interface UpdatePlayerCountProps {
  roomId: string;
  playerCount: number;
  playerId: number;
}

export interface RoomLayoutValues {
  messages: Message[];
  setMessages: React.Dispatch<React.SetStateAction<Message[]>>;
  isExpand: boolean;
  setIsExpand: React.Dispatch<React.SetStateAction<boolean>>;
  currentRoomId: string;
}
