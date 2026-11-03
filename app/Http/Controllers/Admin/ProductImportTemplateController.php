<?php

namespace App\Http\Controllers\Admin;

use App\Support\AdminAccess;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\Common\Creator\WriterFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImportTemplateController
{
    public function __invoke(): StreamedResponse
    {
        abort_unless(AdminAccess::canManageProductsInAdmin(), 403);

        return response()->streamDownload(function (): void {
            $writer = WriterFactory::createFromFile('product-import-template.xlsx');
            $writer->openToFile('php://output');
            $writer->addRow(Row::fromValues(['کد کالا', 'شرح کالا', 'موجودی', 'قیمت سایت']));
            $writer->addRow(Row::fromValues(['1', 'نمونه محصول', '3', '1500000']));
            $writer->close();
        }, 'product-import-template.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
