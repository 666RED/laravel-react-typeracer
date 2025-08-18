import { cn } from '@/lib/utils';

interface Props extends React.InputHTMLAttributes<HTMLInputElement> {
  message: string;
  className?: string;
}

export default function FormErrorMessage({ message, className, ...props }: Props) {
  return (
    <p className={cn('text-sm text-red-500', className)} {...props}>
      {message}
    </p>
  );
}
