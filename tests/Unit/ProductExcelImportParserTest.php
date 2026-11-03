<?php

namespace Tests\Unit;

use App\Services\Product\ProductExcelImportParser;
use Tests\TestCase;

class ProductExcelImportParserTest extends TestCase
{
    public function test_parses_csv_with_persian_headers_and_commas_in_price(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pli-');
        $this->assertNotFalse($path);

        file_put_contents($path, implode("\n", [
            'کد کالا,شرح کالا,موجودی,قیمت سایت',
            '101,فالکن دیپلمات,4,"2,399,978,700"',
        ]));

        $parser = new ProductExcelImportParser;
        $result = $parser->parseFile($path);

        @unlink($path);

        $this->assertSame([], $result['errors']);
        $this->assertCount(1, $result['rows']);
        $this->assertSame('101', $result['rows'][0]['sku']);
        $this->assertSame('فالکن دیپلمات', $result['rows'][0]['name']);
        $this->assertSame(4, $result['rows'][0]['stock']);
        $this->assertSame(2399978700, $result['rows'][0]['price']);
    }

    public function test_rejects_file_without_required_headers(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'pli-');
        $this->assertNotFalse($path);

        file_put_contents($path, "نام,قیمت\nfoo,1\n");

        $parser = new ProductExcelImportParser;
        $result = $parser->parseFile($path);

        @unlink($path);

        $this->assertSame([], $result['rows']);
        $this->assertNotSame([], $result['errors']);
    }
}
