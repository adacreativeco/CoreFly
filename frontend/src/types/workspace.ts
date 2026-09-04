import { User } from './auth';

export interface Comment {
  id: string;
  post_id: string;
  author_id: string;
  content: string;
  parent_id?: string;
  created_at: string;
  author?: User; // Optional if joined
}

export interface Post {
  id: string;
  tenant_id: string;
  author_id: string;
  title: string;
  content: string;
  post_type: 'general' | 'announcement' | 'alert';
  view_count: number;
  comment_count: number;
  created_at: string;
  updated_at: string;
  author?: User; // Optional if joined
  comments?: Comment[];
}

export interface FeedResponse {
  data: Post[];
}
