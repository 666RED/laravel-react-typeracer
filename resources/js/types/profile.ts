export enum ProfileEvent {
  UPLOAD_PROFILE_IMAGE = 'Profile.UploadProfileImage',
}

export interface ProfileInfo {
  id: number;
  name: string;
  profileImageUrl: string;
  createdAt: string;
  lastActiveAt: string;
  averageWpm: number;
  averageWpmForLastTenRaces: number;
  accuracyPercentage: number;
  totalRaces: number;
  wonRaces: number;
  notCompletedRaces: number;
  winRate: number;
  bestWpm: number;
  worstWpm: number;
}
