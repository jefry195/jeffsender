<?php

namespace App\Contracts;

interface DatasetServiceInterface
{
    /**
     * Get all datasets
     */
    public function getDatasets(string $activeModule): array;
}
