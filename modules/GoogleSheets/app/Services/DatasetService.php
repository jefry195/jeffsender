<?php

namespace Modules\GoogleSheets\App\Services;

use App\Contracts\DatasetServiceInterface;

class DatasetService implements DatasetServiceInterface
{
    public function getDatasets(string $activeModule): array
    {
        // Google Sheets doesn't need database datasets, we just return a single dummy option 
        // or empty array, but since dataset ID is required if has_datasets is true,
        // let's return a dummy dataset so the UI doesn't fail validation.
        return [
            [
                'id' => 1,
                'title' => 'Google Sheets Link'
            ]
        ];
    }
}
