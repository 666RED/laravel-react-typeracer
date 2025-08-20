import FormErrorMessage from '@/components/formErrorMessage';
import FormSubmitButton from '@/components/formSubmitButton';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/authLayout';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function Register() {
  const { data, reset, setData, post, processing, errors } = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
  });

  const handleSubmit: FormEventHandler = (e) => {
    e.preventDefault();
    post(route('auth.register'), {
      onError: () => {
        reset('password', 'password_confirmation');
      },
    });
  };

  const fieldStyle = 'grid gap-2';

  return (
    <AuthLayout title="Register" description="This is register page">
      <form onSubmit={handleSubmit} className="grid gap-6" data-testid="register-form">
        <div className={fieldStyle}>
          <Label htmlFor="name">Name:</Label>
          <Input
            type="text"
            placeholder="Name"
            name="name"
            required
            maxLength={255}
            id="name"
            value={data.name}
            onChange={(e) => setData('name', e.target.value)}
          />
          {errors.name && <FormErrorMessage message={errors.name} />}
        </div>
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
            placeholder="At least 8 characters"
            name="password"
            required
            id="password"
            value={data.password}
            onChange={(e) => setData('password', e.target.value)}
            minLength={8}
          />
          {errors.password && <FormErrorMessage message={errors.password} />}
        </div>

        <div className={fieldStyle}>
          <Label htmlFor="password-confirmation">Confirm Password:</Label>
          <Input
            type="password"
            placeholder="Should match password"
            name="password_confirmation"
            required
            id="confirmPassword"
            value={data.password_confirmation}
            onChange={(e) => setData('password_confirmation', e.target.value)}
            minLength={8}
          />
          {errors.password_confirmation && <FormErrorMessage message={errors.password_confirmation} />}
        </div>

        <FormSubmitButton
          text="Register"
          processing={processing}
          disabled={data.email === '' || data.name === '' || data.password.length < 8 || data.password_confirmation.length < 8}
        />
      </form>
    </AuthLayout>
  );
}
