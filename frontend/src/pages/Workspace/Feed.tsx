import React, { useEffect, useState, useCallback } from 'react';
import { useAuthStore } from '@/store/authStore';
import { getFeed, createPost } from '@/services/workspaceService';
import { Post } from '@/types/workspace';
import { MessageSquare, Eye, Send } from 'lucide-react';

export const Feed: React.FC = () => {
  const { user } = useAuthStore();
  const [posts, setPosts] = useState<Post[]>([]);
  const [loading, setLoading] = useState(true);
  const [newPostTitle, setNewPostTitle] = useState('');
  const [newPostContent, setNewPostContent] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const loadFeed = useCallback(async () => {
    try {
      if (!user?.tenant_id) {
        console.warn('No tenant_id found in user object:', user);
        return;
      }
      console.log('Loading feed for tenant:', user.tenant_id);
      const data = await getFeed(user.tenant_id);
      console.log('Feed loaded:', data);
      setPosts(data);
    } catch (error: any) {
      console.error('Failed to load feed:', error);
      if (error.response) {
         console.error('Error Response Data:', error.response.data);
         console.error('Error Response Status:', error.response.status);
      } else if (error.request) {
         console.error('No response received:', error.request);
      } else {
         console.error('Error Message:', error.message);
      }
    } finally {
      setLoading(false);
    }
  }, [user?.tenant_id]);

  useEffect(() => {
    if (user?.tenant_id) {
      loadFeed();
    }
  }, [loadFeed, user?.tenant_id]);

  const handleCreatePost = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!user?.tenant_id || !newPostTitle.trim() || !newPostContent.trim()) return;

    setIsSubmitting(true);
    try {
      await createPost(user.tenant_id, {
        title: newPostTitle,
        content: newPostContent,
        post_type: 'general',
      });
      setNewPostTitle('');
      setNewPostContent('');
      loadFeed(); // Reload feed to show new post
    } catch (error) {
      console.error('Failed to create post:', error);
    } finally {
      setIsSubmitting(false);
    }
  };

  if (loading) {
    return <div className="text-center py-10">Akış yükleniyor...</div>;
  }

  return (
    <div className="max-w-3xl mx-auto space-y-6">
      {/* Create Post Card */}
      <div className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
        <h2 className="text-lg font-semibold text-gray-900 dark:text-white mb-4">Yeni Gönderi Oluştur</h2>
        <form onSubmit={handleCreatePost}>
          <div className="space-y-4">
            <input
              type="text"
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              placeholder="Gönderi Başlığı"
              value={newPostTitle}
              onChange={(e) => setNewPostTitle(e.target.value)}
              required
            />
            <textarea
              className="w-full px-4 py-2 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              rows={3}
              placeholder="Aklınızdan neler geçiyor?"
              value={newPostContent}
              onChange={(e) => setNewPostContent(e.target.value)}
              required
            />
            <div className="flex justify-end">
              <button
                type="submit"
                disabled={isSubmitting}
                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition"
              >
                <Send className="h-4 w-4 mr-2" />
                {isSubmitting ? 'Paylaşılıyor...' : 'Paylaş'}
              </button>
            </div>
          </div>
        </form>
      </div>

      {/* Feed List */}
      <div className="space-y-4">
        {posts.length === 0 ? (
          <div className="text-center text-gray-500 dark:text-gray-400 py-10">Henüz gönderi yok. İlk gönderiyi siz paylaşın!</div>
        ) : (
          posts.map((post) => (
            <div key={post.id} className="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
              <div className="p-6">
                <div className="flex justify-between items-start">
                  <h3 className="text-xl font-bold text-gray-900 dark:text-white">{post.title}</h3>
                  <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-950/60 text-blue-800 dark:text-blue-300">
                    {post.post_type}
                  </span>
                </div>
                <p className="mt-2 text-gray-600 dark:text-gray-300">{post.content}</p>
                <div className="mt-4 flex items-center justify-between text-sm text-gray-500 dark:text-gray-400">
                  <div className="flex items-center space-x-4">
                    <span className="flex items-center">
                      <Eye className="h-4 w-4 mr-1" />
                      {post.view_count}
                    </span>
                    <span className="flex items-center">
                      <MessageSquare className="h-4 w-4 mr-1" />
                      {post.comment_count}
                    </span>
                  </div>
                  <span>{new Date(post.created_at).toLocaleDateString('tr-TR')}</span>
                </div>
              </div>
            </div>
          ))
        )}
      </div>
    </div>
  );
};
