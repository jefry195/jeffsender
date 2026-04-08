<?php

namespace Modules\Whatsapp\App\Exports;

use App\Models\Customer;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class CustomerListExport implements FromQuery, WithHeadings, WithMapping
{
    public function __construct(
        public $moduleName
    ) {}

    protected $columns = [
        'name',
        'dial_code',
        'phone',
        'picture',
    ];

    public function query()
    {
        return Customer::query()
            ->where('module', $this->moduleName)
            ->where('owner_id', activeWorkspaceOwnerId());
    }

    public function headings(): array
    {
        return $this->columns;
    }

    /**
     * @param  Customer  $customer
     */
    public function map($customer): array
    {
        return [
            $customer->name,
            $customer->meta['dial_code'] ?? '',
            $customer->meta['phone'] ?? '',
            $customer->picture,
        ];
    }
}
