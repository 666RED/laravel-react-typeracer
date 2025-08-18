import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ButtonVariant } from '@/types';
import { FormEventHandler } from 'react';
import { ClipLoader } from 'react-spinners';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  text: string;
  processing: boolean;
  onClick: FormEventHandler;
  className?: string;
  variant?: ButtonVariant;
  size?: number;
}

export default function ButtonWithLoader({ text, processing, className, variant = 'default', onClick, disabled, size = 20, ...props }: Props) {
  const isDisabled = disabled ?? processing;

  return (
    <Button
      type="button"
      variant={variant}
      className={cn('min-w-20 px-2', className)}
      {...props}
      disabled={isDisabled || processing}
      onClick={onClick}
    >
      {processing ? <ClipLoader loading={processing} size={size} /> : text}
    </Button>
  );
}
