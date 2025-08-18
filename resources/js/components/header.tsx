import { navigationMenuTriggerStyle } from '@/components/ui/navigation-menu';
import { SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { NavigationMenu, NavigationMenuItem, NavigationMenuLink, NavigationMenuList } from '@radix-ui/react-navigation-menu';

export default function Header() {
  const { auth } = usePage<SharedData>().props;

  return (
    <div className="flex items-center justify-between border-b border-b-secondary px-4 pb-2" data-testid="header">
      <Link href={route('home')}>Typeracer</Link>

      <NavigationMenu>
        {!auth.user || auth.user?.is_guest ? (
          //@ register & login buttons
          <NavigationMenuList className="flex items-center gap-x-2">
            <NavigationMenuItem>
              <NavigationMenuLink asChild className={navigationMenuTriggerStyle()}>
                <Link href={route('auth.show-register')}>Register</Link>
              </NavigationMenuLink>
            </NavigationMenuItem>
            <NavigationMenuItem>
              <NavigationMenuLink asChild className={navigationMenuTriggerStyle()}>
                <Link href={route('auth.show-login')}>Login</Link>
              </NavigationMenuLink>
            </NavigationMenuItem>
          </NavigationMenuList>
        ) : (
          //@ profile & logout button
          <NavigationMenuList className="flex items-center gap-x-2">
            <NavigationMenuItem>
              <NavigationMenuLink asChild className={navigationMenuTriggerStyle()}>
                <Link href={route('profile.show', { userId: auth?.user.id })} method="get">
                  Profile
                </Link>
              </NavigationMenuLink>
            </NavigationMenuItem>
            <NavigationMenuItem>
              <NavigationMenuLink asChild className={navigationMenuTriggerStyle()}>
                <Link href={route('logout')} method="post" className="cursor-pointer">
                  Logout
                </Link>
              </NavigationMenuLink>
            </NavigationMenuItem>
          </NavigationMenuList>
        )}
      </NavigationMenu>
    </div>
  );
}
