import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { ButtonVariant } from '@/types';
import { ClipLoader } from 'react-spinners';

interface Props extends React.ButtonHTMLAttributes<HTMLButtonElement> {
  text: string;
  processing: boolean;
  className?: string;
  variant?: ButtonVariant;
  disabled?: boolean;
  size?: number;
}

export default function FormSubmitButton({ text, processing, className, variant = 'default', size = 20, disabled, ...props }: Props) {
  const isDisabled = disabled ?? processing;

  return (
    <Button type="submit" variant={variant} className={cn('min-w-20 px-2', className)} {...props} disabled={isDisabled || processing}>
      {processing ? <ClipLoader loading={processing} size={size} /> : text}
    </Button>
  );
}
