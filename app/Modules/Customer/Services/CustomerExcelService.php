<?php

namespace App\Modules\Customer\Services;

use App\Modules\Customer\Models\PosCustomer;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerExcelService
{
    /**
     * Create a styled sample spreadsheet for customer imports.
     */
    public function createSampleSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Exhibitors');

        $headers = [
            'A1' => 'Name',
            'B1' => 'Phone',
            'C1' => 'Address',
            'D1' => 'Exhibitor Level (1=Standard, 2=Silver, 3=VIP)',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Header styling
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '465FFF'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A1:D1')->applyFromArray($headerStyle);
        $sheet->getRowDimension(1)->setRowHeight(28);

        // Sample data
        $samples = [
            ['Daw Mya Mya', '09450011223', 'No. 12, Inya Road, Yangon', 3],
            ['U Kyaw Swar', '09790022334', 'Corner of 78th & 30th St, Mandalay', 2],
            ['Ma Hnin Wai', '09960033445', 'Pyay Road, Kamayut, Yangon', 1],
        ];

        $row = 2;
        foreach ($samples as $sample) {
            $sheet->setCellValue('A'.$row, $sample[0]);
            $sheet->setCellValueExplicit('B'.$row, $sample[1], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('C'.$row, $sample[2]);
            $sheet->setCellValue('D'.$row, $sample[3]);
            $sheet->getRowDimension($row)->setRowHeight(22);
            $row++;
        }

        foreach (['A', 'B', 'C', 'D'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Download the sample customer Excel file as a stream.
     */
    public function downloadSample(): StreamedResponse
    {
        $spreadsheet = $this->createSampleSpreadsheet();

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 'exhibitor_import_sample.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Save the sample Excel file to disk.
     */
    public function saveSampleFile(string $targetPath): void
    {
        $dir = dirname($targetPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $spreadsheet = $this->createSampleSpreadsheet();
        $writer = new Xlsx($spreadsheet);
        $writer->save($targetPath);
    }

    /**
     * Parse and import customers from an Excel/CSV file.
     *
     * @return array{imported: int, skipped: int, errors: array<string>}
     */
    public function import(string|UploadedFile $file, int $userId): array
    {
        $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return [
                'imported' => 0,
                'skipped' => 0,
                'errors' => ['The uploaded spreadsheet is empty.'],
            ];
        }

        // Determine column indexes from the header row
        $headerRow = array_map(fn ($col) => strtolower(trim((string) $col)), $rows[0]);
        $nameIndex = null;
        $phoneIndex = null;
        $addressIndex = null;
        $levelIndex = null;

        foreach ($headerRow as $idx => $heading) {
            if ($nameIndex === null && str_contains($heading, 'name')) {
                $nameIndex = $idx;
            } elseif ($phoneIndex === null && (str_contains($heading, 'phone') || str_contains($heading, 'mobile') || str_contains($heading, 'contact'))) {
                $phoneIndex = $idx;
            } elseif ($addressIndex === null && (str_contains($heading, 'address') || str_contains($heading, 'location'))) {
                $addressIndex = $idx;
            } elseif ($levelIndex === null && (str_contains($heading, 'level') || str_contains($heading, 'tier') || str_contains($heading, 'type'))) {
                $levelIndex = $idx;
            }
        }

        // Fallback to default column positions if header matching didn't catch them
        if ($nameIndex === null) {
            $nameIndex = 0;
            $phoneIndex = 1;
            $addressIndex = 2;
            $levelIndex = 3;
        }

        $imported = 0;
        $skipped = 0;
        $errors = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rowNum = $i + 1;

            $name = trim((string) ($row[$nameIndex] ?? ''));
            $phone = trim((string) ($row[$phoneIndex] ?? ''));
            $address = trim((string) ($row[$addressIndex] ?? ''));
            $levelRaw = strtolower(trim((string) ($row[$levelIndex] ?? '1')));

            // Skip completely empty row
            if ($name === '' && $phone === '' && $address === '') {
                continue;
            }

            if ($name === '') {
                $skipped++;
                $errors[] = "Row {$rowNum}: Skipped because exhibitor name is missing.";
                continue;
            }

            $customerLevel = match (true) {
                str_contains($levelRaw, '3') || str_contains($levelRaw, 'vip') || str_contains($levelRaw, 'gold') => 3,
                str_contains($levelRaw, '2') || str_contains($levelRaw, 'silver') => 2,
                default => 1,
            };

            // If phone is provided, update existing or create new. Otherwise, create new.
            if ($phone !== '') {
                PosCustomer::updateOrCreate(
                    ['phone' => $phone],
                    [
                        'name' => $name,
                        'address' => $address ?: null,
                        'customer_level' => $customerLevel,
                        'created_by' => $userId,
                    ]
                );
            } else {
                PosCustomer::create([
                    'name' => $name,
                    'phone' => null,
                    'address' => $address ?: null,
                    'customer_level' => $customerLevel,
                    'created_by' => $userId,
                ]);
            }

            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
