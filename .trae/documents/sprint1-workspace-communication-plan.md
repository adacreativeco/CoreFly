# Sprint 1: Workspace & Communication Modülü - Detaylı Plan

## Sprint Hedefi
Kullanıcıların workspace'lerde gönderi paylaşabileceği ve yorum yapabileceği temel iletişim altyapısını oluşturmak.

## 1. Veritabanı Migrasyonları

### 1.1 Tenants Tablosu
```sql
-- Tenants tablosu oluşturma
CREATE TABLE tenants (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    logo_url VARCHAR(500),
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexler
CREATE INDEX idx_tenants_slug ON tenants(slug);
CREATE INDEX idx_tenants_active ON tenants(is_active);

-- RLS Politikaları
ALTER TABLE tenants ENABLE ROW LEVEL SECURITY;

-- Tüm kullanıcılar aktif tenant'ları görebilir
CREATE POLICY "Aktif tenant'ları herkes görebilir" ON tenants
    FOR SELECT USING (is_active = true);

-- Sadece authenticated kullanıcılar tenant oluşturabilir
CREATE POLICY "Authenticated kullanıcılar tenant oluşturabilir" ON tenants
    FOR INSERT WITH CHECK (auth.role() = 'authenticated');
```

### 1.2 Users Tablosu (Supabase Auth Entegrasyonu)
```sql
-- Users tablosu (Supabase auth.users ile ilişkili)
CREATE TABLE public.users (
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    email VARCHAR(255) UNIQUE NOT NULL,
    full_name VARCHAR(255),
    avatar_url VARCHAR(500),
    tenant_id UUID REFERENCES tenants(id) ON DELETE CASCADE,
    role VARCHAR(50) DEFAULT 'member' CHECK (role IN ('admin', 'moderator', 'member')),
    is_active BOOLEAN DEFAULT true,
    last_seen_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexler
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_tenant ON users(tenant_id);
CREATE INDEX idx_users_active ON users(is_active);

-- RLS Politikaları
ALTER TABLE users ENABLE ROW LEVEL SECURITY;

-- Kullanıcılar kendi profillerini görebilir ve güncelleyebilir
CREATE POLICY "Kullanıcılar kendi profillerini görebilir" ON users
    FOR SELECT USING (auth.uid() = id);

CREATE POLICY "Kullanıcılar kendi profillerini güncelleyebilir" ON users
    FOR UPDATE USING (auth.uid() = id);

-- Adminler tüm kullanıcıları görebilir
CREATE POLICY "Adminler tüm kullanıcıları görebilir" ON users
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users u
            WHERE u.id = auth.uid() 
            AND u.role = 'admin'
            AND u.tenant_id = users.tenant_id
        )
    );
```

### 1.3 Posts Tablosu
```sql
-- Posts tablosu
CREATE TABLE posts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tenant_id UUID NOT NULL REFERENCES tenants(id) ON DELETE CASCADE,
    author_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    post_type VARCHAR(50) DEFAULT 'general' CHECK (post_type IN ('general', 'announcement', 'question')),
    is_pinned BOOLEAN DEFAULT false,
    is_locked BOOLEAN DEFAULT false,
    view_count INTEGER DEFAULT 0,
    like_count INTEGER DEFAULT 0,
    comment_count INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexler
CREATE INDEX idx_posts_tenant ON posts(tenant_id);
CREATE INDEX idx_posts_author ON posts(author_id);
CREATE INDEX idx_posts_created ON posts(created_at DESC);
CREATE INDEX idx_posts_pinned ON posts(is_pinned) WHERE is_pinned = true;
CREATE INDEX idx_posts_type ON posts(post_type);

-- RLS Politikaları
ALTER TABLE posts ENABLE ROW LEVEL SECURITY;

-- Tenant üyeleri gönderileri görebilir
CREATE POLICY "Tenant üyeleri gönderileri görebilir" ON posts
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users u
            WHERE u.id = auth.uid()
            AND u.tenant_id = posts.tenant_id
            AND u.is_active = true
        )
    );

-- Authenticated kullanıcılar gönderi oluşturabilir
CREATE POLICY "Authenticated kullanıcılar gönderi oluşturabilir" ON posts
    FOR INSERT WITH CHECK (
        auth.uid() = author_id AND
        EXISTS (
            SELECT 1 FROM users u
            WHERE u.id = auth.uid()
            AND u.tenant_id = posts.tenant_id
            AND u.is_active = true
        )
    );

-- Yazarlar kendi gönderilerini güncelleyebilir
CREATE POLICY "Yazarlar kendi gönderilerini güncelleyebilir" ON posts
    FOR UPDATE USING (auth.uid() = author_id);

-- Adminler tüm gönderileri güncelleyebilir
CREATE POLICY "Adminler tüm gönderileri güncelleyebilir" ON posts
    FOR UPDATE USING (
        EXISTS (
            SELECT 1 FROM users u
            WHERE u.id = auth.uid() 
            AND u.role = 'admin'
            AND u.tenant_id = posts.tenant_id
        )
    );
```

### 1.4 Comments Tablosu
```sql
-- Comments tablosu
CREATE TABLE comments (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    post_id UUID NOT NULL REFERENCES posts(id) ON DELETE CASCADE,
    author_id UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    parent_id UUID REFERENCES comments(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    is_edited BOOLEAN DEFAULT false,
    like_count INTEGER DEFAULT 0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexler
CREATE INDEX idx_comments_post ON comments(post_id);
CREATE INDEX idx_comments_author ON comments(author_id);
CREATE INDEX idx_comments_parent ON comments(parent_id);
CREATE INDEX idx_comments_created ON comments(created_at);

-- RLS Politikaları
ALTER TABLE comments ENABLE ROW LEVEL SECURITY;

-- Tenant üyeleri yorumları görebilir
CREATE POLICY "Tenant üyeleri yorumları görebilir" ON comments
    FOR SELECT USING (
        EXISTS (
            SELECT 1 FROM users u
            JOIN posts p ON p.tenant_id = u.tenant_id
            WHERE u.id = auth.uid()
            AND p.id = comments.post_id
            AND u.is_active = true
        )
    );

-- Authenticated kullanıcılar yorum oluşturabilir
CREATE POLICY "Authenticated kullanıcılar yorum oluşturabilir" ON comments
    FOR INSERT WITH CHECK (
        auth.uid() = author_id AND
        EXISTS (
            SELECT 1 FROM users u
            JOIN posts p ON p.tenant_id = u.tenant_id
            WHERE u.id = auth.uid()
            AND p.id = comments.post_id
            AND u.is_active = true
            AND p.is_locked = false
        )
    );

-- Yazarlar kendi yorumlarını güncelleyebilir
CREATE POLICY "Yazarlar kendi yorumlarını güncelleyebilir" ON comments
    FOR UPDATE USING (auth.uid() = author_id);
```

## 2. Model Implementasyonları

### 2.1 Backend Modelleri (TypeScript)

#### Tenant Model
```typescript
// models/Tenant.ts
export interface Tenant {
  id: string;
  name: string;
  slug: string;
  description?: string;
  logoUrl?: string;
  isActive: boolean;
  createdAt: Date;
  updatedAt: Date;
}

export interface CreateTenantRequest {
  name: string;
  slug: string;
  description?: string;
  logoUrl?: string;
}

export interface UpdateTenantRequest {
  name?: string;
  description?: string;
  logoUrl?: string;
  isActive?: boolean;
}
```

#### User Model
```typescript
// models/User.ts
export interface User {
  id: string;
  email: string;
  fullName?: string;
  avatarUrl?: string;
  tenantId: string;
  role: 'admin' | 'moderator' | 'member';
  isActive: boolean;
  lastSeenAt: Date;
  createdAt: Date;
  updatedAt: Date;
}

export interface CreateUserRequest {
  email: string;
  password: string;
  fullName?: string;
  tenantId: string;
  role?: 'admin' | 'moderator' | 'member';
}

export interface UpdateUserRequest {
  fullName?: string;
  avatarUrl?: string;
  role?: 'admin' | 'moderator' | 'member';
  isActive?: boolean;
}
```

#### Post Model
```typescript
// models/Post.ts
export interface Post {
  id: string;
  tenantId: string;
  authorId: string;
  title: string;
  content: string;
  postType: 'general' | 'announcement' | 'question';
  isPinned: boolean;
  isLocked: boolean;
  viewCount: number;
  likeCount: number;
  commentCount: number;
  createdAt: Date;
  updatedAt: Date;
  author?: User;
  comments?: Comment[];
}

export interface CreatePostRequest {
  title: string;
  content: string;
  postType?: 'general' | 'announcement' | 'question';
}

export interface UpdatePostRequest {
  title?: string;
  content?: string;
  postType?: 'general' | 'announcement' | 'question';
  isPinned?: boolean;
  isLocked?: boolean;
}

export interface PostListResponse {
  posts: Post[];
  totalCount: number;
  hasMore: boolean;
  nextCursor?: string;
}
```

#### Comment Model
```typescript
// models/Comment.ts
export interface Comment {
  id: string;
  postId: string;
  authorId: string;
  parentId?: string;
  content: string;
  isEdited: boolean;
  likeCount: number;
  createdAt: Date;
  updatedAt: Date;
  author?: User;
  replies?: Comment[];
}

export interface CreateCommentRequest {
  postId: string;
  content: string;
  parentId?: string;
}

export interface UpdateCommentRequest {
  content: string;
}
```

## 3. Controller Logic

### 3.1 Authentication Controller
```typescript
// controllers/AuthController.ts
import { Request, Response } from 'express';
import { supabase } from '../config/supabase';
import { User, CreateUserRequest } from '../models/User';

export class AuthController {
  // Kullanıcı kaydı
  async register(req: Request, res: Response) {
    try {
      const { email, password, fullName, tenantId }: CreateUserRequest = req.body;

      // Supabase auth ile kullanıcı oluştur
      const { data: authData, error: authError } = await supabase.auth.signUp({
        email,
        password,
      });

      if (authError) throw authError;

      // Public users tablosuna kullanıcı ekle
      const { data: userData, error: userError } = await supabase
        .from('users')
        .insert({
          id: authData.user?.id,
          email,
          full_name: fullName,
          tenant_id: tenantId,
          role: 'member'
        })
        .select()
        .single();

      if (userError) throw userError;

      res.status(201).json({
        success: true,
        data: {
          user: userData,
          auth: authData
        }
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }

  // Kullanıcı girişi
  async login(req: Request, res: Response) {
    try {
      const { email, password } = req.body;

      const { data, error } = await supabase.auth.signInWithPassword({
        email,
        password,
      });

      if (error) throw error;

      // Kullanıcı bilgilerini getir
      const { data: userData } = await supabase
        .from('users')
        .select('*')
        .eq('id', data.user?.id)
        .single();

      res.json({
        success: true,
        data: {
          user: userData,
          session: data.session
        }
      });
    } catch (error) {
      res.status(401).json({
        success: false,
        error: error.message
      });
    }
  }

  // Çıkış yap
  async logout(req: Request, res: Response) {
    try {
      const { error } = await supabase.auth.signOut();
      if (error) throw error;

      res.json({
        success: true,
        message: 'Çıkış başarılı'
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }

  // Mevcut kullanıcı bilgileri
  async me(req: Request, res: Response) {
    try {
      const { data: { user }, error: authError } = await supabase.auth.getUser();
      
      if (authError) throw authError;

      const { data: userData, error: userError } = await supabase
        .from('users')
        .select('*')
        .eq('id', user?.id)
        .single();

      if (userError) throw userError;

      res.json({
        success: true,
        data: userData
      });
    } catch (error) {
      res.status(401).json({
        success: false,
        error: error.message
      });
    }
  }
}
```

### 3.2 Workspace Feed Controller
```typescript
// controllers/WorkspaceController.ts
import { Request, Response } from 'express';
import { supabase } from '../config/supabase';
import { Post, CreatePostRequest, UpdatePostRequest } from '../models/Post';

export class WorkspaceController {
  // Workspace feed'ini getir
  async getFeed(req: Request, res: Response) {
    try {
      const { tenantId } = req.params;
      const { page = 1, limit = 20, type, sortBy = 'created_at' } = req.query;

      let query = supabase
        .from('posts')
        .select(`
          *, 
          author:users(id, full_name, avatar_url, role),
          comments:comments(id, created_at)
        `)
        .eq('tenant_id', tenantId)
        .order(sortBy as string, { ascending: false })
        .range((+page - 1) * +limit, +page * +limit - 1);

      if (type) {
        query = query.eq('post_type', type);
      }

      const { data, error, count } = await query;

      if (error) throw error;

      res.json({
        success: true,
        data: {
          posts: data,
          pagination: {
            page: +page,
            limit: +limit,
            total: count,
            hasMore: data.length === +limit
          }
        }
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }

  // Tekil gönderi getir
  async getPost(req: Request, res: Response) {
    try {
      const { postId } = req.params;

      // Görüntüleme sayısını artır
      await supabase
        .from('posts')
        .update({ view_count: supabase.sql('view_count + 1') })
        .eq('id', postId);

      // Gönderiyi getir
      const { data, error } = await supabase
        .from('posts')
        .select(`
          *, 
          author:users(id, full_name, avatar_url, role),
          comments:comments(*, author:users(id, full_name, avatar_url))
        `)
        .eq('id', postId)
        .single();

      if (error) throw error;

      res.json({
        success: true,
        data
      });
    } catch (error) {
      res.status(404).json({
        success: false,
        error: 'Gönderi bulunamadı'
      });
    }
  }

  // Yeni gönderi oluştur
  async createPost(req: Request, res: Response) {
    try {
      const { tenantId } = req.params;
      const { title, content, postType }: CreatePostRequest = req.body;
      const authorId = req.user.id; // Auth middleware'den gelen kullanıcı

      const { data, error } = await supabase
        .from('posts')
        .insert({
          tenant_id: tenantId,
          author_id: authorId,
          title,
          content,
          post_type: postType || 'general'
        })
        .select(`
          *, 
          author:users(id, full_name, avatar_url, role)
        `)
        .single();

      if (error) throw error;

      res.status(201).json({
        success: true,
        data
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }

  // Gönderi güncelle
  async updatePost(req: Request, res: Response) {
    try {
      const { postId } = req.params;
      const { title, content, postType, isPinned, isLocked }: UpdatePostRequest = req.body;
      const userId = req.user.id;

      // Yetki kontrolü
      const { data: post } = await supabase
        .from('posts')
        .select('author_id, tenant_id')
        .eq('id', postId)
        .single();

      if (!post) {
        return res.status(404).json({
          success: false,
          error: 'Gönderi bulunamadı'
        });
      }

      // Admin kontrolü
      const { data: user } = await supabase
        .from('users')
        .select('role')
        .eq('id', userId)
        .single();

      const isAdmin = user?.role === 'admin';
      const isAuthor = post.author_id === userId;

      if (!isAuthor && !isAdmin) {
        return res.status(403).json({
          success: false,
          error: 'Gönderiyi güncelleme yetkiniz yok'
        });
      }

      // Güncelleme verilerini hazırla
      const updateData: any = {};
      if (title) updateData.title = title;
      if (content) updateData.content = content;
      if (postType) updateData.post_type = postType;
      if (isPinned !== undefined && isAdmin) updateData.is_pinned = isPinned;
      if (isLocked !== undefined && isAdmin) updateData.is_locked = isLocked;
      updateData.updated_at = new Date();

      const { data, error } = await supabase
        .from('posts')
        .update(updateData)
        .eq('id', postId)
        .select(`
          *, 
          author:users(id, full_name, avatar_url, role)
        `)
        .single();

      if (error) throw error;

      res.json({
        success: true,
        data
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }

  // Gönderi sil
  async deletePost(req: Request, res: Response) {
    try {
      const { postId } = req.params;
      const userId = req.user.id;

      // Yetki kontrolü
      const { data: post } = await supabase
        .from('posts')
        .select('author_id')
        .eq('id', postId)
        .single();

      if (!post) {
        return res.status(404).json({
          success: false,
          error: 'Gönderi bulunamadı'
        });
      }

      if (post.author_id !== userId) {
        return res.status(403).json({
          success: false,
          error: 'Gönderiyi silme yetkiniz yok'
        });
      }

      const { error } = await supabase
        .from('posts')
        .delete()
        .eq('id', postId);

      if (error) throw error;

      res.json({
        success: true,
        message: 'Gönderi silindi'
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }

  // Yorum ekle
  async addComment(req: Request, res: Response) {
    try {
      const { postId } = req.params;
      const { content, parentId } = req.body;
      const authorId = req.user.id;

      // Post'un kilitli olup olmadığını kontrol et
      const { data: post } = await supabase
        .from('posts')
        .select('is_locked')
        .eq('id', postId)
        .single();

      if (post?.is_locked) {
        return res.status(403).json({
          success: false,
          error: 'Bu gönderiye yorum yapılamaz'
        });
      }

      // Yorum oluştur
      const { data, error } = await supabase
        .from('comments')
        .insert({
          post_id: postId,
          author_id: authorId,
          content,
          parent_id: parentId
        })
        .select(`
          *, 
          author:users(id, full_name, avatar_url)
        `)
        .single();

      if (error) throw error;

      // Yorum sayısını güncelle
      await supabase
        .from('posts')
        .update({ comment_count: supabase.sql('comment_count + 1') })
        .eq('id', postId);

      res.status(201).json({
        success: true,
        data
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }

  // Yorum güncelle
  async updateComment(req: Request, res: Response) {
    try {
      const { commentId } = req.params;
      const { content } = req.body;
      const userId = req.user.id;

      // Yetki kontrolü
      const { data: comment } = await supabase
        .from('comments')
        .select('author_id')
        .eq('id', commentId)
        .single();

      if (!comment) {
        return res.status(404).json({
          success: false,
          error: 'Yorum bulunamadı'
        });
      }

      if (comment.author_id !== userId) {
        return res.status(403).json({
          success: false,
          error: 'Yorumu güncelleme yetkiniz yok'
        });
      }

      const { data, error } = await supabase
        .from('comments')
        .update({
          content,
          is_edited: true,
          updated_at: new Date()
        })
        .eq('id', commentId)
        .select(`
          *, 
          author:users(id, full_name, avatar_url)
        `)
        .single();

      if (error) throw error;

      res.json({
        success: true,
        data
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }

  // Yorum sil
  async deleteComment(req: Request, res: Response) {
    try {
      const { commentId } = req.params;
      const userId = req.user.id;

      // Yetki kontrolü
      const { data: comment } = await supabase
        .from('comments')
        .select('author_id, post_id')
        .eq('id', commentId)
        .single();

      if (!comment) {
        return res.status(404).json({
          success: false,
          error: 'Yorum bulunamadı'
        });
      }

      if (comment.author_id !== userId) {
        return res.status(403).json({
          success: false,
          error: 'Yorumu silme yetkiniz yok'
        });
      }

      const { error } = await supabase
        .from('comments')
        .delete()
        .eq('id', commentId);

      if (error) throw error;

      // Yorum sayısını güncelle
      await supabase
        .from('posts')
        .update({ comment_count: supabase.sql('comment_count - 1') })
        .eq('id', comment.post_id);

      res.json({
        success: true,
        message: 'Yorum silindi'
      });
    } catch (error) {
      res.status(400).json({
        success: false,
        error: error.message
      });
    }
  }
}
```

## 4. API Route Tanımlamaları

### 4.1 Authentication Routes
```typescript
// routes/auth.ts
import { Router } from 'express';
import { AuthController } from '../controllers/AuthController';
import { authenticateToken } from '../middleware/auth';

const router = Router();
const authController = new AuthController();

// Public routes
router.post('/register', authController.register);
router.post('/login', authController.login);
router.post('/logout', authController.logout);

// Protected routes
router.get('/me', authenticateToken, authController.me);

export default router;
```

### 4.2 Workspace Routes
```typescript
// routes/workspace.ts
import { Router } from 'express';
import { WorkspaceController } from '../controllers/WorkspaceController';
import { authenticateToken } from '../middleware/auth';
import { checkTenantAccess } from '../middleware/tenant';

const router = Router();
const workspaceController = new WorkspaceController();

// Tüm route'lar için authentication ve tenant access kontrolü
router.use(authenticateToken);
router.use(checkTenantAccess);

// Workspace feed routes
router.get('/:tenantId/feed', workspaceController.getFeed);
router.get('/:tenantId/posts/:postId', workspaceController.getPost);
router.post('/:tenantId/posts', workspaceController.createPost);
router.put('/:tenantId/posts/:postId', workspaceController.updatePost);
router.delete('/:tenantId/posts/:postId', workspaceController.deletePost);

// Comment routes
router.post('/:tenantId/posts/:postId/comments', workspaceController.addComment);
router.put('/:tenantId/comments/:commentId', workspaceController.updateComment);
router.delete('/:tenantId/comments/:commentId', workspaceController.deleteComment);

export default router;
```

### 4.3 Main Routes Index
```typescript
// routes/index.ts
import { Router } from 'express';
import authRoutes from './auth';
import workspaceRoutes from './workspace';

const router = Router();

// Health check
router.get('/health', (req, res) => {
  res.json({ status: 'OK', timestamp: new Date().toISOString() });
});

// API routes
router.use('/auth', authRoutes);
router.use('/workspace', workspaceRoutes);

export default router;
```

## 5. Middleware'ler

### 5.1 Authentication Middleware
```typescript
// middleware/auth.ts
import { Request, Response, NextFunction } from 'express';
import { supabase } from '../config/supabase';

export interface AuthRequest extends Request {
  user?: any;
}

export const authenticateToken = async (
  req: AuthRequest,
  res: Response,
  next: NextFunction
) => {
  try {
    const token = req.headers.authorization?.split(' ')[1];
    
    if (!token) {
      return res.status(401).json({
        success: false,
        error: 'Token gerekli'
      });
    }

    const { data: { user }, error } = await supabase.auth.getUser(token);
    
    if (error || !user) {
      return res.status(401).json({
        success: false,
        error: 'Geçersiz token'
      });
    }

    req.user = user;
    next();
  } catch (error) {
    res.status(401).json({
      success: false,
      error: 'Kimlik doğrulama hatası'
    });
  }
};
```

### 5.2 Tenant Access Middleware
```typescript
// middleware/tenant.ts
import { Response, NextFunction } from 'express';
import { supabase } from '../config/supabase';
import { AuthRequest } from './auth';

export const checkTenantAccess = async (
  req: AuthRequest,
  res: Response,
  next: NextFunction
) => {
  try {
    const { tenantId } = req.params;
    const userId = req.user.id;

    // Kullanıcının tenant'a erişimi var mı?
    const { data: user, error } = await supabase
      .from('users')
      .select('tenant_id, is_active, role')
      .eq('id', userId)
      .single();

    if (error || !user) {
      return res.status(404).json({
        success: false,
        error: 'Kullanıcı bulunamadı'
      });
    }

    if (user.tenant_id !== tenantId) {
      return res.status(403).json({
        success: false,
        error: 'Bu workspace\'e erişim yetkiniz yok'
      });
    }

    if (!user.is_active) {
      return res.status(403).json({
        success: false,
        error: 'Hesabınız aktif değil'
      });
    }

    // Kullanıcı bilgilerini request'e ekle
    req.user = {
      ...req.user,
      tenantId: user.tenant_id,
      role: user.role,
      isActive: user.is_active
    };

    next();
  } catch (error) {
    res.status(500).json({
      success: false,
      error: 'Tenant erişim kontrolü hatası'
    });
  }
};
```

## 6. Doğrulama Adımları

### 6.1 Test Senaryoları

#### Authentication Testleri
1. **Kullanıcı Kaydı**
   - Başarılı kayıt işlemi
   - Mevcut email ile kayıt denemesi
   - Geçersiz email formatı
   - Zorunlu alan eksikliği

2. **Kullanıcı Girişi**
   - Başarılı giriş
   - Yanlış şifre
   - Mevcut olmayan kullanıcı
   - Geçersiz token ile giriş

3. **Token Doğrulama**
   - Geçerli token ile erişim
   - Süresi dolmuş token
   - Geçersiz token formatı
   - Token olmadan erişim

#### Workspace Feed Testleri
1. **Gönderi Listeleme**
   - Tenant'a ait gönderileri listeleme
   - Farklı türdeki gönderileri filtreleme
   - Sayfalama işlevselliği
   - Boş feed durumu

2. **Gönderi Oluşturma**
   - Başarılı gönderi oluşturma
   - Zorunlu alan eksikliği
   - Tenant üyesi olmayan kullanıcı
   - Farklı gönderi türleri

3. **Gönderi Güncelleme**
   - Yazarın kendi gönderisini güncellemesi
   - Adminin her gönderiyi güncellemesi
   - Yetkisiz güncelleme denemesi
   - Kilitli gönderi güncelleme

4. **Yorum Sistemi**
   - Başarılı yorum ekleme
   - Kilitli gönderiye yorum denemesi
   - Yorum güncelleme
   - Yorum silme
   - Nested yorumlar

### 6.2 Entegrasyon Testleri

#### API Endpoint Testleri
```bash
# Authentication testleri
curl -X POST http://localhost:3000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456","fullName":"Test User","tenantId":"tenant-uuid"}'

curl -X POST http://localhost:3000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"123456"}'

# Workspace feed testleri
curl -X GET http://localhost:3000/api/workspace/tenant-uuid/feed \
  -H "Authorization: Bearer TOKEN"

curl -X POST http://localhost:3000/api/workspace/tenant-uuid/posts \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"title":"Test Post","content":"Test content","postType":"general"}'
```

### 6.3 Güvenlik Testleri

1. **SQL Injection Testleri**
   - Input validasyonu
   - Parameterized queries
   - Supabase RLS politikaları

2. **XSS Koruması**
   - Content sanitization
   - Output encoding
   - React built-in XSS koruması

3. **Yetki Kontrolleri**
   - Tenant erişim kontrolü
   - Rol bazlı yetkilendirme
   - Resource ownership kontrolü

### 6.4 Performans Testleri

1. **Database Performansı**
   - Index kullanımı
   - Query optimization
   - Connection pooling

2. **API Response Süreleri**
   - Feed yükleme süresi (< 200ms)
   - Gönderi oluşturma (< 100ms)
   - Yorum ekleme (< 100ms)

3. **Ölçeklenebilirlik**
   - Pagination implementasyonu
   - Lazy loading
   - Cache stratejileri

## 7. Deployment Kontrol Listesi

### 7.1 Environment Variables
```bash
# Supabase Configuration
SUPABASE_URL=your-supabase-url
SUPABASE_ANON_KEY=your-supabase-anon-key
SUPABASE_SERVICE_KEY=your-supabase-service-key

# Application Settings
NODE_ENV=production
PORT=3000
JWT_SECRET=your-jwt-secret
```

### 7.2 Database Migration Kontrolleri
- [ ] Tüm tablolar oluşturuldu
- [ ] Indexler eklendi
- [ ] RLS politikaları aktif
- [ ] Foreign key constraint'ler doğrulandı
- [ ] Initial data eklendi

### 7.3 API Deployment
- [ ] Tüm endpoint'ler test edildi
- [ ] Swagger dokümantasyonu hazırlandı
- [ ] Error handling implemente edildi
- [ ] Rate limiting aktif
- [ ] CORS konfigürasyonu yapıldı

### 7.4 Monitoring & Logging
- [ ] Application logging konfigüre edildi
- [ ] Error tracking (Sentry) entegre edildi
- [ ] Performance monitoring aktif
- [ ] Database query monitoring
- [ ] API response time tracking

## 8. Sprint 1 Tamamlama Kriterleri

### 8.1 Fonksiyonel Gereksinimler
- [ ] Kullanıcı kaydı ve girişi çalışıyor
- [ ] Tenant sistemi aktif
- [ ] Workspace feed'i gösteriliyor
- [ ] Gönderi oluşturma/güncelleme/silme işlevselliği
- [ ] Yorum sistemi çalışıyor
- [ ] Rol bazlı yetkilendirme aktif

### 8.2 Teknik Gereksinimler
- [ ] Tüm API endpoint'leri test edildi
- [ ] Database migrasyonları başarılı
- [ ] RLS politikaları doğru çalışıyor
- [ ] Error handling implemente edildi
- [ ] Code review tamamlandı
- [ ] Dokümantasyon güncellendi

### 8.3 Kalite Kriterleri
- [ ] Unit test coverage > 80%
- [ ] Integration test'ler geçti
- [ ] Security test'ler tamamlandı
- [ ] Performance test'ler başarılı
- [ ] Code quality gates geçti
- [ ] Deployment pipeline hazır

Bu Sprint 1 planı, Workspace & Communication modülünün başarılı bir şekilde implemente edilmesi için gereken tüm adımları içermektedir. Her bir madde tamamlandıkanda check işareti konulmalı ve sprint review'de değerlendirilmelidir.