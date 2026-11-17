<?php

namespace App\Support;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use PowerComponents\LivewirePowerGrid\Components\Exports\OpenSpout\v5\ExportToXLS;
use PowerComponents\LivewirePowerGrid\Components\SetUp\Exportable;

/**
 * Excel del historial de vacaciones: igual al de PowerGrid, pero con un encabezado
 * que identifica al empleado antes de la tabla.
 */
final class HistorialVacacionExcel extends ExportToXLS
{
    public function __construct(private readonly string $empleado) {}

    public function build(Exportable|array $exportOptions): void
    {
        $data = $this->prepare($this->data, $this->columns, (bool) data_get($exportOptions, 'stripTags', false));

        $writer = new Writer(new Options);
        $writer->openToFile(storage_path($this->fileName.'.xlsx'));

        $writer->addRow(Row::fromValuesWithStyle(['Historial de vacaciones'], (new Style)->withFontBold(true)->withFontSize(14)));
        $writer->addRow(Row::fromValuesWithStyle(['Empleado: '.$this->empleado], (new Style)->withFontBold(true)->withFontSize(12)));
        $writer->addRow(Row::fromValues(['']));

        $writer->addRow(Row::fromValuesWithStyle($data['headers'], (new Style)
            ->withFontBold(true)
            ->withFontSize(12)
            ->withBackgroundColor('d0d3d8')));

        $estilo = (new Style)->withFontSize(12);

        /** @var array<string> $fila */
        foreach ($data['rows'] as $fila) {
            if (count($fila)) {
                $writer->addRow(Row::fromValuesWithStyle(array_values($fila), $estilo));
            }
        }

        $writer->close();
    }
}
