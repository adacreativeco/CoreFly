<?php

declare(strict_types=1);

namespace CoreFly\Controllers\Root;

use App\Services\AIService;

class RootAIController extends BaseRootController
{
    public function ask()
    {
        $this->requireRootAuth();
        $data = $this->getJsonInput();
        $query = $data['query'] ?? '';

        if (empty($query)) {
            return $this->errorResponse('Query is required', 400);
        }

        $ai = new AIService();
        $response = $ai->ask($query);

        return $this->successResponse(['response' => $response]);
    }
}
