<?php

namespace App\Support;

use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Excel de cuentas con el formato de la hoja "CUENTAS STREAM": un bloque
 * "CUENTA DE <PLATAFORMA>" por plataforma, con sus columnas debajo.
 */
class SubscriptionsSpreadsheet
{
    /** @var list<string> */
    public const HEADINGS = ['NÚMERO', 'PERSONA', 'CUENTA', 'CONTRASEÑA', 'PIN', 'INICIO', 'EXPIRA', 'NOTAS'];

    private const FIRST_COLUMN = 'C';

    private const LAST_COLUMN = 'J';

    private const TITLE_COLOR = 'FFFF00';

    private const HEADING_COLOR = 'F4B084';

    /**
     * @param  Collection<int, Subscription>  $subscriptions  Con la relación "service" cargada.
     */
    public function build(Collection $subscriptions): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Cuentas');

        $groups = $subscriptions
            ->sortBy('expires_on')
            ->groupBy('service_id')
            ->sortBy(fn (Collection $rows) => [$rows->first()->service->sort_order, $rows->first()->service->name]);

        $row = 3;
        foreach ($groups as $rows) {
            $row = $this->writeBlock($sheet, $row, $rows) + 2;
        }

        foreach (['C' => 14, 'D' => 22, 'E' => 34, 'F' => 18, 'G' => 8, 'H' => 22, 'I' => 22, 'J' => 50] as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }

        return $spreadsheet;
    }

    /**
     * Escribe un bloque y devuelve la última fila usada.
     *
     * @param  Collection<int, Subscription>  $rows
     */
    private function writeBlock(Worksheet $sheet, int $row, Collection $rows): int
    {
        $range = fn (int $line): string => self::FIRST_COLUMN.$line.':'.self::LAST_COLUMN.$line;
        $firstRow = $row;

        $sheet->setCellValue(self::FIRST_COLUMN.$row, 'CUENTA DE '.mb_strtoupper($rows->first()->service->name));
        $sheet->mergeCells($range($row));
        $this->fill($sheet, $range($row), self::TITLE_COLOR);

        $row++;
        $sheet->fromArray(self::HEADINGS, null, self::FIRST_COLUMN.$row);
        $this->fill($sheet, $range($row), self::HEADING_COLOR);

        foreach ($rows as $subscription) {
            $row++;
            $column = self::FIRST_COLUMN;
            foreach ($this->cells($subscription) as $value) {
                $sheet->setCellValueExplicit($column.$row, $value, DataType::TYPE_STRING);
                $column++;
            }
        }

        $block = $sheet->getStyle(self::FIRST_COLUMN.$firstRow.':'.self::LAST_COLUMN.$row);
        $block->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $block->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('J'.($firstRow + 2).':J'.$row)->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)->setWrapText(true);

        return $row;
    }

    /**
     * Valores de la fila; se escriben como texto para que un PIN "0505" no pierda el cero.
     *
     * @return list<string>
     */
    private function cells(Subscription $subscription): array
    {
        return [
            (string) $subscription->formattedPhone(),
            $subscription->customer_name.($subscription->profile_name ? " ({$subscription->profile_name})" : ''),
            (string) $subscription->account_email,
            (string) $subscription->account_password,
            (string) $subscription->pin,
            $this->date($subscription->starts_on),
            $this->date($subscription->expires_on),
            (string) $subscription->notes,
        ];
    }

    private function date(Carbon $date): string
    {
        return $date->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
    }

    private function fill(Worksheet $sheet, string $range, string $color): void
    {
        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($color);
    }
}
