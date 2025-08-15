<?php
/**
 * This file is part of PHPWord - A pure PHP library for reading and writing
 * word processing documents.
 *
 * PHPWord is free software distributed under the terms of the GNU Lesser
 * General Public License version 3 as published by the Free Software Foundation.
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code. For the full list of
 * contributors, visit https://github.com/PHPOffice/PHPWord/contributors.
 *
 * @see         https://github.com/PHPOffice/PhpWord
 * @copyright   2010-2018 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

namespace PhpOffice\PhpWord\Writer\PDF;

use Dompdf\Dompdf as DompdfLib;
use PhpOffice\PhpWord\Writer\WriterInterface;

/**
 * DomPDF writer
 *
 * @see  https://github.com/dompdf/dompdf
 * @since 0.10.0
 */
class DomPDF extends AbstractRenderer implements WriterInterface
{
    /**
     * Name of renderer include file
     *
     * @var string
     */
    protected $includeFile = null;

    /**
     * Gets the implementation of external PDF library that should be used.
     *
     * @return Dompdf implementation
     */
    protected function createExternalWriterInstance()
    {
        return new DompdfLib();
    }

    /**
     * Save PhpWord to file.
     *
     * @param string $filename Name of the file to save as
     */
    public function save($filename = null)
    {
        $fileHandle = parent::prepareForSave($filename);

        //  PDF settings
        $paperSize = 'A4';
        $orientation = 'portrait';

		// Get HTML content
		$html = $this->getContent();

		// HOSTCMS::Replace tab to span
		$html = str_replace('&nbsp;', ' ', $html);
		$html = str_replace('	', '<span style="padding-left: 45px"> </span>', $html);

        //  Create PDF
        $pdf = $this->createExternalWriterInstance();
        $pdf->setPaper(strtolower($paperSize), $orientation);

		// HOSTCMS
		$html = str_replace('<meta charset="UTF-8" />', '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />', $html);
		// $html = str_replace('font-family: Cambria;', 'font-family: DejaVu Sans, sans-serif !important;', $html);

       	$pdf->loadHtml(str_replace(PHP_EOL, '', $html), "UTF-8");

		$options = $pdf->getOptions();

		$options->set(array('defaultFont' => 'dejavu sans'));
		// $options->setPdfBackend('GD');
		$pdf->setOptions($options);

		// debug options
		//$options = $pdf->getOptions(); die();

        $pdf->render();

        //  Write to file
        fwrite($fileHandle, $pdf->output());

        parent::restoreStateAfterSave($fileHandle);
    }
}
