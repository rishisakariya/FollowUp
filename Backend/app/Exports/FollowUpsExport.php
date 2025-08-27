<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class FollowUpsExport implements FromCollection, WithHeadings, WithMapping
{
    protected $followups;
    protected $counts;

    public function __construct($followups, $counts)
    {
        $this->followups = $followups;
        $this->counts = $counts;
    }

    // Map each follow-up row
    public function map($followup): array
    {
        return [
            $followup->title,
            $followup->description,
            $followup->followup_status,
            $followup->date,
        ];
    }

    // Prepare collection with follow-ups + summary counts
    public function collection()
    {
        $rows = $this->followups;

        // Add empty row to separate summary
        $rows->push((object)[
            'title' => '',
            'description' => '',
            'followup_status' => '',
            'date' => '',
        ]);

        // Add summary count rows
        foreach ($this->counts as $status => $count) {
            $rows->push((object)[
                'title' => $status . ' Count',
                'description' => '',
                'followup_status' => $count,
                'date' => '',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Title', 'Description', 'Status', 'Date'];
    }
}
