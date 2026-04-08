<?php

namespace Modules\QAReply\App\Services;

use App\Contracts\DatasetServiceInterface;
use Modules\QAReply\App\Models\QaReply;

class DatasetService implements DatasetServiceInterface
{
    public function getDatasets(string $activeModule): array
    {
        return QaReply::query()
            ->where('module', $activeModule)
            ->where('owner_id', activeWorkspaceOwnerId())
            ->get(['id', 'title'])
            ->toArray();
    }
}
