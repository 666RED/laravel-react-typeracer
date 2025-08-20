import FormSubmitButton from '@/components/formSubmitButton';
import { Input } from '@/components/ui/input';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function JoinRoomForm() {
  const { data, setData, processing, post, reset } = useForm<{ roomId: string }>({
    roomId: '',
  });

  const handleJoinRoom: FormEventHandler = (e) => {
    e.preventDefault();
    post(route('room.join'), {
      onError: (errors) => {
        reset();
      },
    });
  };

  return (
    <form onSubmit={handleJoinRoom} className="flex w-full max-w-sm items-center gap-2" autoComplete="off">
      <Input
        type="text"
        placeholder="Enter Room ID"
        name="room-number"
        required
        value={data.roomId}
        onChange={(e) =>
          setData(() => ({
            roomId: e.target.value,
          }))
        }
      />
      <FormSubmitButton text="Join" processing={processing} />
    </form>
  );
}
