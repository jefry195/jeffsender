<?php

namespace Modules\AiTraining\App\Services;

use App\Contracts\DatasetServiceInterface;
use Modules\AiTraining\App\Models\AiTraining;

class DatasetService implements DatasetServiceInterface
{
    public function getDatasets(string $activeModule): array
    {
        return AiTraining::query()
            ->where('user_id', activeWorkspaceOwnerId())
            ->where('status', 'succeeded')
            ->get(['id', 'title'])
            ->toArray();
    }
}
