import FormErrorMessage from '@/components/formErrorMessage';
import FormSubmitButton from '@/components/formSubmitButton';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/authLayout';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Login() {
  const { data, setData, post, processing, errors, reset } = useForm({
    email: '',
    password: '',
  });

  const handleSubmit: FormEventHandler = (e) => {
    e.preventDefault();
    post(route('auth.login'), {
      onFinish: () => {
        reset('password');
      },
    });
  };

  const fieldStyle = 'grid gap-2';

  return (
    <AuthLayout title="Login" description="This is login page">
      <form className="grid gap-6" onSubmit={handleSubmit} data-testid="login-form">
        <div className={fieldStyle}>
          <Label htmlFor="email">Email:</Label>
          <Input
            type="email"
            placeholder="Email"
            name="email"
            required
            id="email"
            value={data.email}
            onChange={(e) => setData('email', e.target.value)}
          />
          {errors.email && <FormErrorMessage message={errors.email} />}
        </div>

        <div className={fieldStyle}>
          <Label htmlFor="password">Password:</Label>
          <Input
            type="password"
            placeholder="Password"
            name="password"
            required
            id="password"
            value={data.password}
            minLength={8}
            onChange={(e) => setData('password', e.target.value)}
          />
        </div>

        <FormSubmitButton text="Login" processing={processing} disabled={data.email === '' || data.password === ''} />
      </form>
    </AuthLayout>
  );
}
