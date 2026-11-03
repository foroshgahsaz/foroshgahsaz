<?php

namespace App\Services\Product;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\Common\Creator\ReaderFactory;
final class ProductExcelImportParser
{
    /** @var array<string, string> normalized header => field key */
    private const HEADER_MAP = [
        'کدکالا' => 'sku',
        'کد_کالا' => 'sku',
        'sku' => 'sku',
        'code' => 'sku',
        'شرحکالا' => 'name',
        'شرح_کالا' => 'name',
        'نام' => 'name',
        'name' => 'name',
        'موجودی' => 'stock',
        'stock' => 'stock',
        'قیمتسایت' => 'price',
        'قیمت_سایت' => 'price',
        'قیمت' => 'price',
        'price' => 'price',
    ];

    /**
     * @return array{rows: list<array{sku: string, name: string, stock: int, price: int}>, errors: list<string>}
     */
    public function parseFile(string $absolutePath): array
    {
        if (! is_readable($absolutePath)) {
            return ['rows' => [], 'errors' => ['فایل قابل خواندن نیست.']];
        }

        $reader = ReaderFactory::createFromFile($absolutePath);
        $reader->open($absolutePath);

        $rows = [];
        $errors = [];
        $headerMap = null;
        $line = 0;

        try {
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $line++;
                    $cells = $this->cellsToStrings($row);

                    if ($headerMap === null) {
                        $headerMap = $this->mapHeaders($cells);
                        if ($headerMap === []) {
                            return [
                                'rows' => [],
                                'errors' => ['سطر اول باید شامل ستون‌های «کد کالا»، «شرح کالا»، «موجودی» و «قیمت سایت» باشد.'],
                            ];
                        }

                        continue;
                    }

                    if ($this->isEmptyRow($cells)) {
                        continue;
                    }

                    $parsed = $this->parseDataRow($cells, $headerMap, $line);
                    if ($parsed === null) {
                        continue;
                    }

                    if (isset($parsed['error'])) {
                        $errors[] = $parsed['error'];

                        continue;
                    }

                    $rows[] = $parsed;
                }

                break;
            }
        } finally {
            $reader->close();
        }

        return ['rows' => $rows, 'errors' => $errors];
    }

    /** @return list<string> */
    private function cellsToStrings(Row $row): array
    {
        $values = [];

        foreach ($row->getCells() as $cell) {
            $value = $cell->getValue();

            if ($value instanceof \DateTimeInterface) {
                $values[] = $value->format('Y-m-d H:i:s');

                continue;
            }

            $values[] = trim((string) $value);
        }

        return $values;
    }

    /**
     * @param  list<string>  $headerCells
     * @return array<int, string> column index => field
     */
    private function mapHeaders(array $headerCells): array
    {
        $map = [];

        foreach ($headerCells as $index => $label) {
            $key = self::HEADER_MAP[$this->normalizeHeader($label)] ?? null;
            if ($key !== null) {
                $map[$index] = $key;
            }
        }

        $required = ['sku', 'name', 'stock', 'price'];
        foreach ($required as $field) {
            if (! in_array($field, $map, true)) {
                return [];
            }
        }

        return $map;
    }

    private function normalizeHeader(string $label): string
    {
        $label = trim($label);
        $label = preg_replace('/[\s_\x{200c}\x{00a0}]+/u', '', $label) ?? $label;

        return mb_strtolower($label, 'UTF-8');
    }

    /** @param  list<string>  $cells */
    private function isEmptyRow(array $cells): bool
    {
        foreach ($cells as $cell) {
            if ($cell !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $cells
     * @param  array<int, string>  $headerMap
     * @return array{sku: string, name: string, stock: int, price: int}|array{error: string}|null
     */
    private function parseDataRow(array $cells, array $headerMap, int $line): ?array
    {
        $data = [
            'sku' => '',
            'name' => '',
            'stock' => 0,
            'price' => 0,
        ];

        foreach ($headerMap as $index => $field) {
            $data[$field] = $cells[$index] ?? '';
        }

        $sku = trim((string) $data['sku']);
        $name = trim((string) $data['name']);

        if ($sku === '' && $name === '') {
            return null;
        }

        if ($sku === '' || $name === '') {
            return ['error' => "سطر {$line}: کد کالا و شرح کالا هر دو الزامی است."];
        }

        $stock = $this->parseInteger($data['stock']);
        if ($stock === null) {
            return ['error' => "سطر {$line}: موجودی نامعتبر است."];
        }

        $price = $this->parseInteger($data['price']);
        if ($price === null) {
            return ['error' => "سطر {$line}: قیمت نامعتبر است."];
        }

        return [
            'sku' => $sku,
            'name' => $name,
            'stock' => max(0, $stock),
            'price' => max(0, $price),
        ];
    }

    private function parseInteger(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            return (int) round($value);
        }

        $string = trim((string) $value);
        if ($string === '') {
            return 0;
        }

        $string = str_replace([',', '،', ' ', "\u{00a0}"], '', $string);
        $string = $this->persianDigitsToAscii($string);

        if (! preg_match('/^-?\d+$/', $string)) {
            return null;
        }

        return (int) $string;
    }

    private function persianDigitsToAscii(string $value): string
    {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];

        return str_replace($persian, range('0', '9'), str_replace($arabic, range('0', '9'), $value));
    }
}
