<?php

namespace App\Services\Ingestion;

use App\Models\Node;
use App\Models\Tenant;
use Illuminate\Http\UploadedFile;
use RuntimeException;

class CsvNodeImporter
{
    /**
     * @var list<string>
     */
    private const REQUIRED_COLUMNS = ['name'];

    /**
     * @return array{processed:int,created:int,updated:int,errors:list<array<string,string|int>>}
     */
    public function import(Tenant $tenant, UploadedFile $file): array
    {
        if (! $file->isValid()) {
            throw new RuntimeException('Invalid CSV upload.');
        }

        $path = $file->getRealPath();

        if ($path === false) {
            throw new RuntimeException('Unable to read uploaded file.');
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open uploaded file for reading.');
        }

        $header = null;

        $summary = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        while (($row = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = $this->normalizeHeader($row);
                $missing = array_diff(self::REQUIRED_COLUMNS, $header);

                if ($missing !== []) {
                    fclose($handle);

                    return [
                        'processed' => 0,
                        'created' => 0,
                        'updated' => 0,
                        'errors' => [
                            ['row' => 0, 'error' => 'Missing required columns: '.implode(', ', $missing)],
                        ],
                    ];
                }

                continue;
            }

            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $summary['processed']++;

            $data = $this->combineRow($header, $row);

            $error = $this->validateRow($data);

            if ($error !== null) {
                $summary['errors'][] = ['row' => $summary['processed'], 'error' => $error];
                continue;
            }

            $node = Node::firstOrNew([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
            ]);

            $node->fill([
                'type' => $data['type'] ?? null,
                'location' => $data['location'] ?? null,
                'capacity' => isset($data['capacity']) ? (int) $data['capacity'] : null,
            ]);

            $isNew = ! $node->exists;
            $wasDirty = $node->isDirty();

            $node->tenant()->associate($tenant);
            $node->save();

            if ($isNew) {
                $summary['created']++;
            } elseif ($wasDirty) {
                $summary['updated']++;
            }
        }

        fclose($handle);

        return $summary;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function validateRow(array $row): ?string
    {
        if (empty($row['name'])) {
            return 'Name is required.';
        }

        if (isset($row['capacity']) && ! is_numeric($row['capacity'])) {
            return 'Capacity must be numeric.';
        }

        return null;
    }

    /**
     * @param  list<string>  $row
     */
    private function rowIsEmpty(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $row
     * @return array<string, string>
     */
    private function combineRow(array $header, array $row): array
    {
        $row = array_pad($row, count($header), null);

        return array_combine($header, array_map(fn ($value) => $value !== null ? trim((string) $value) : null, $row)) ?: [];
    }

    /**
     * @param  list<string>  $row
     * @return list<string>
     */
    private function normalizeHeader(array $row): array
    {
        return array_map(fn ($value) => strtolower(trim($value)), $row);
    }
}
