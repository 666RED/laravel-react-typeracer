import { Message } from '@/types/message';
import { Room } from '@/types/room';
import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
  user: User;
}

export interface BreadcrumbItem {
  title: string;
  href: string;
}

export interface NavGroup {
  title: string;
  items: NavItem[];
}

export interface NavItem {
  title: string;
  href: string;
  icon?: LucideIcon | null;
  isActive?: boolean;
}

export interface SharedData {
  name: string;
  quote: { message: string; author: string };
  auth: Auth;
  ziggy: Config & { location: string };
  sidebarOpen: boolean;
  [key: string]: unknown;
  currentRoom: Room;
  messages: Message[];
  response_data: { [key: string]: unknown };
  flash: { message: string; type: '' | 'success' | 'warning' | 'error' };
}

export interface User {
  id: number;
  name: string;
  email?: string;
  avatar?: string;
  email_verified_at?: string | null;
  created_at?: string;
  updated_at?: string;
  is_guest: number;
  [key: string]: unknown;
}

export interface LayoutProps {
  children?: ReactNode;
  title?: string;
  description?: string;
}

export type ButtonVariant = 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link' | 'submit';

export interface PaginationProps<T> {
  current_page: number;
  data: T[];
  first_page_url: string | null;
  from: number;
  last_page: number;
  links: { active: boolean; label: string; url: string }[];
  next_page_url: number;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number;
  total: number;
}
