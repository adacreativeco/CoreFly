import client from '@/api/client';
import { Post, FeedResponse } from '@/types/workspace';

export const getFeed = async (tenantId: string): Promise<Post[]> => {
  const response = await client.get<FeedResponse>(`/workspace/${tenantId}/feed`);
  return response.data.data;
};

export const createPost = async (
  tenantId: string,
  data: { title: string; content: string; post_type: string }
): Promise<Post> => {
  const response = await client.post<{ data: Post }>(`/workspace/${tenantId}/posts`, data);
  return response.data.data;
};
