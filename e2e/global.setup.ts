import { test as setup } from '@hyvor/laravel-playwright';

setup('Refresh database and seed', async ({ laravel }) => {
  await laravel.artisan('migrate:refresh --seed');
});
