import Header from '@/components/header';
import { LayoutProps, SharedData } from '@/types';
import { UserEvent } from '@/types/user';
import { Head, router, usePage } from '@inertiajs/react';
import { useEchoPublic } from '@laravel/echo-react';
import { useEffect } from 'react';
import { toast, Toaster } from 'sonner';

export default function AuthLayout({ children, title = '', description = '' }: LayoutProps) {
  const { props } = usePage<SharedData>();

  // display errors globally
  useEffect(() => {
    if (props.errors && typeof props.errors === 'string') {
      toast.error(props.errors);
    } else if (props.errors && typeof props.errors === 'object' && Object.keys(props.errors).length > 0) {
      let description = '';

      for (const key in props.errors) {
        description += `${props.errors[key]}`;
      }

      toast.error(description);
    }
  }, [props.errors]);

  useEchoPublic<{ userId: number }>(`user.${props.auth?.user?.id}`, UserEvent.REMOVE_GUEST_USER_SESSION_ROOM_ID, (e) => {
    router.post(route('auth.remove-session'));
  });

  useEffect(() => {
    if (props.flash?.message) {
      switch (props.flash?.type) {
        case 'warning': {
          toast.warning(props.flash.message, { duration: Infinity });
          break;
        }

        case 'success': {
          toast.success(props.flash.message);
          break;
        }

        case 'error': {
          toast.error(props.flash.message, { duration: Infinity });
          break;
        }

        default: {
          toast.info(props.flash.message);
        }
      }
    }
  }, [props.flash]);

  return (
    <div className="flex flex-col gap-6 py-4">
      <Head>
        <title>{title}</title>
        <meta name="description" content={description} />
      </Head>
      <Header />
      <div className="flex flex-col items-center justify-center p-6">
        <h1 className="mb-8 text-2xl font-bold">{title}</h1>
        <div className="w-full max-w-sm">{children}</div>
      </div>
      <Toaster />
    </div>
  );
}
