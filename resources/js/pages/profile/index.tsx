import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import BaseLayout from '@/layouts/baseLayout';
import EditProfileDialog from '@/pages/profile/components/editProfileDialog';
import StatCard from '@/pages/profile/components/statCard';
import { SharedData } from '@/types';
import { ProfileEvent, ProfileInfo } from '@/types/profile';
import { Link, router, usePage } from '@inertiajs/react';
import { useEcho } from '@laravel/echo-react';
import { toast } from 'sonner';

interface Props {
  profileInfo: ProfileInfo;
}

export default function Index({ profileInfo }: Props) {
  const { auth } = usePage<SharedData>().props;

  useEcho(`user.${auth?.user?.id ?? ''}`, ProfileEvent.UPLOAD_PROFILE_IMAGE, () => {
    toast.info('Profile image updated');
    router.reload();
  });

  return (
    <BaseLayout title="Profile page" description="This is profile page">
      <div className="flex flex-col gap-y-4 md:px-20 xl:px-36">
        {/* USER INFO */}
        <Card className="bg-primary-foreground">
          <CardContent>
            <div className="flex items-center justify-between">
              {/* USER INFO */}
              <div className="flex items-center gap-x-12">
                <img
                  src={profileInfo.profileImageUrl ?? '/default-profile-image.jpg'}
                  className="h-24 w-24 rounded-full object-cover object-center md:h-36 md:w-36"
                />
                <div className="flex flex-col gap-y-2">
                  <div className="text-3xl">{profileInfo.name}</div>
                  <div>Joined: {profileInfo.createdAt}</div>
                  <div>{profileInfo.lastActiveAt}</div>
                </div>
              </div>
              {/* EDIT PROFILE BUTTON */}
              {auth?.user?.id === profileInfo.id && <EditProfileDialog name={profileInfo.name} profileImageUrl={profileInfo.profileImageUrl} />}
            </div>
          </CardContent>
        </Card>
        {/* RESULT INFO */}
        <Card className="bg-primary-foreground px-6 *:not-last:bg-secondary">
          <Card>
            <CardContent className="grid grid-cols-3">
              <StatCard title="Total races" stat={profileInfo.totalRaces} />
              <StatCard title="Won races" stat={profileInfo.wonRaces} />
              <StatCard title="Win rate" stat={`${profileInfo.winRate}%`} />
            </CardContent>
          </Card>
          <Card>
            <CardContent className="grid grid-cols-3">
              <StatCard title="Average WPM" stat={profileInfo.averageWpm} />
              <StatCard title="Average WPM (last 10)" stat={profileInfo.averageWpmForLastTenRaces} />
              <StatCard title="Accuracy" stat={`${profileInfo.accuracyPercentage}%`} />
            </CardContent>
          </Card>
          <Card>
            <CardContent className="grid grid-cols-3">
              <StatCard title="Best Wpm" stat={profileInfo.bestWpm} />
              <StatCard title="Worst Wpm" stat={profileInfo.worstWpm} />
              <StatCard title="Not completed races" stat={profileInfo.notCompletedRaces} />
            </CardContent>
          </Card>
          <Button variant="default" asChild>
            <Link href={route('profile.show-results', { userId: profileInfo.id })}>View races history</Link>
          </Button>
        </Card>
      </div>
    </BaseLayout>
  );
}
