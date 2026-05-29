<?php

namespace Modules\UltimatePOS\App\Services;

use App\Contracts\DatasetServiceInterface;

class DatasetService implements DatasetServiceInterface
{
    public function getDatasets(string $activeModule): array
    {
        return [
            [
                'id' => 1, // atau id apa saja yang merepresentasikan dataset default
                'title' => 'Cek Tagihan Otomatis'
            ]
        ];
    }
}
