import MainContent from '@/pages/room/components/mainContent';
import RoomLayout from '@/pages/room/layouts/roomLayout';

export default function Index() {
  return (
    <RoomLayout title="Room" description="This is room page">
      <MainContent />
    </RoomLayout>
  );
}
