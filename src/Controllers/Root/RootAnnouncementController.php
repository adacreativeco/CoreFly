<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use CoreFly\Models\Announcement;

class RootAnnouncementController extends BaseRootController
{
    public function index()
    {
        $this->requireRootAuth();
        $announcements = Announcement::query()->withoutTenant()->orderBy('created_at', 'DESC')->get();
        return $this->successResponse(['items' => $announcements]);
    }

    public function store()
    {
        $this->requireRootAuth();
        $data = $this->getJsonInput();

        if (empty($data['title']) || empty($data['body'])) {
            return $this->errorResponse('Title and Body are required', 400);
        }

        $announcement = new Announcement();
        $announcement->title = $data['title'];
        $announcement->body = $data['body'];
        $announcement->type = $data['type'] ?? 'info';
        $announcement->target_audience = isset($data['target_audience']) ? json_encode($data['target_audience']) : null;
        $announcement->is_active = (int)($data['is_active'] ?? 1);
        $announcement->published_at = $data['published_at'] ?? date('Y-m-d H:i:s');
        $announcement->expires_at = $data['expires_at'] ?? null;
        
        $announcement->save();

        return $this->successResponse($announcement, 'Announcement created');
    }

    public function update($id)
    {
        $this->requireRootAuth();
        $announcement = Announcement::query()->withoutTenant()->find($id);

        if (!$announcement) {
            return $this->errorResponse('Announcement not found', 404);
        }

        $data = $this->getJsonInput();
        
        if (isset($data['title'])) $announcement->title = $data['title'];
        if (isset($data['body'])) $announcement->body = $data['body'];
        if (isset($data['type'])) $announcement->type = $data['type'];
        if (isset($data['target_audience'])) $announcement->target_audience = json_encode($data['target_audience']);
        if (isset($data['is_active'])) $announcement->is_active = (int)$data['is_active'];
        if (isset($data['expires_at'])) $announcement->expires_at = $data['expires_at'];

        $announcement->save();

        return $this->successResponse($announcement, 'Announcement updated');
    }

    public function destroy($id)
    {
        $this->requireRootAuth();
        $announcement = Announcement::query()->withoutTenant()->find($id);

        if (!$announcement) {
            return $this->errorResponse('Announcement not found', 404);
        }

        $announcement->delete();
        return $this->successResponse(null, 'Announcement deleted');
    }
}
