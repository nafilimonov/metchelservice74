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
 * @see         https://github.com/PHPOffice/PHPWord
 * @copyright   2010-2018 PHPWord contributors
 * @license     http://www.gnu.org/licenses/lgpl.txt LGPL version 3
 */

// ADD HOSTCMS
namespace PhpOffice\PhpWord\Writer\HTML\Element;

use PhpOffice\PhpWord\Writer\HTML\Style\Table as TableStyleWriter;
use PhpOffice\PhpWord\Writer\HTML\Style\Cell as CellStyleWriter;
use PhpOffice\PhpWord\Writer\HTML\Style\Paragraph as ParagraphStyleWriter;


/**
 * Table element HTML writer
 *
 * @since 0.10.0
 */
class Table extends AbstractElement
{
    /**
     * Write table
     *
     * @return string
     */
    public function write()
    {
        if (!$this->element instanceof \PhpOffice\PhpWord\Element\Table) {
            return '';
        }

        $content = '';
        $rows = $this->element->getRows();
        $rowCount = count($rows);
        if ($rowCount > 0) {
			$tableStyle = self::getTableStyle($this->element->getStyle());

			$content .= '<table cellpadding="0" cellspacing="0"';

			if (is_object($tableStyle))
			{
				$styleWriter = new TableStyleWriter($tableStyle);

				$content .= ' style="' . $styleWriter->write() . '"';
			}
			else
			{
				$content .= ' class="' . htmlspecialchars($tableStyle) . '"';
			}

			$content .= '>' . PHP_EOL;


            for ($i = 0; $i < $rowCount; $i++) {
                /** @var $row \PhpOffice\PhpWord\Element\Row Type hint */
                $rowStyle = $rows[$i]->getStyle();

				$height = $rows[$i]->getHeight();

                $tblHeader = $rowStyle->isTblHeader();

				$sTrStyle = '';

				$height && $sTrStyle .= 'height: ' . intval($height * 0.2 / 3) . 'px';

                $content .= '<tr style="' . $sTrStyle . '">' . PHP_EOL;

                $rowCells = $rows[$i]->getCells();
                $rowCellCount = count($rowCells);
                for ($j = 0; $j < $rowCellCount; $j++) {
                    $cellStyle = $rowCells[$j]->getStyle();
					$width = $rowCells[$j]->getWidth();
                    $cellColSpan = $cellStyle->getGridSpan();
                    $cellRowSpan = 1;
                    $cellVMerge = $cellStyle->getVMerge();
                    // If this is the first cell of the vertical merge, find out how man rows it spans
                    if ($cellVMerge === 'restart') {
                        for ($k = $i + 1; $k < $rowCount; $k++) {
                            $kRowCells = $rows[$k]->getCells();
                            if (isset($kRowCells[$j])) {
								$kVMerge = $kRowCells[$j]->getStyle()->getVMerge();
                                if ($kVMerge === 'continue' || $kVMerge === '') {
                                    $cellRowSpan++;
                                } else {
                                    break;
                                }
                            } else {
                                break;
                            }
                        }
                    }
                    // Ignore cells that are merged vertically with previous rows
                    if ($cellVMerge !== 'continue'
						// HOSTCMS
						&& $cellVMerge !== ''
					) {
                        $cellTag = $tblHeader ? 'th' : 'td';
                        $cellColSpanAttr = (is_numeric($cellColSpan) && ($cellColSpan > 1) ? " colspan=\"{$cellColSpan}\"" : '');
                        $cellRowSpanAttr = ($cellRowSpan > 1 ? " rowspan=\"{$cellRowSpan}\"" : '');

                        $sTdStyle = '';

						$width && $sTdStyle .= 'width: ' . intval($width * 0.2 / 3) . 'px; ';

						$styleWriter = new CellStyleWriter($cellStyle);

						$x =  $rowCells[$j]->getElement(0);
						if ($x)
						{
							$paragraphStyle = $x->getParagraphStyle();
							$ParagraphStyleWriter = new ParagraphStyleWriter($paragraphStyle);
							$sTdStyle .= $ParagraphStyleWriter->write();
						}

						$sTdStyle .= $styleWriter->write();

                        $content .= "<{$cellTag}{$cellColSpanAttr}{$cellRowSpanAttr} style=\"" . $sTdStyle . "\">" . PHP_EOL;

                        $writer = new Container($this->parentWriter, $rowCells[$j]);
                        $insideContent = $writer->write();
                        if ($cellRowSpan > 1) {
                            // There shouldn't be any content in the subsequent merged cells, but lets check anyway
                            for ($k = $i + 1; $k < $rowCount; $k++) {
                                $kRowCells = $rows[$k]->getCells();
                                if (isset($kRowCells[$j])) {
                                    if ($kRowCells[$j]->getStyle()->getVMerge() === 'continue') {
                                        $writer = new Container($this->parentWriter, $kRowCells[$j]);
                                        $insideContent .= $writer->write();
                                    } else {
                                        break;
                                    }
                                } else {
                                    break;
                                }
                            }
                        }

						// Default content for empty cell without defined height
						$content .= $insideContent != '' || $height
							? $insideContent
							: '<br />';

                        $content .= "</{$cellTag}>" . PHP_EOL;
                    }
                }
                $content .= '</tr>' . PHP_EOL;
            }
            $content .= '</table>' . PHP_EOL;
        }

        return $content;
    }

    /**
     * Translates Table style in CSS equivalent
     *
     * @param string|\PhpOffice\PhpWord\Style\Table|null $tableStyle
     * @return string
     */
    private function getTableStyle($tableStyle = null)
    {
        if ($tableStyle == null) {
            return '';
        }
        if (is_string($tableStyle)) {
            $style = ' class="' . $tableStyle;
        } else {
            $style = ' style="';
            if ($tableStyle->getLayout() == \PhpOffice\PhpWord\Style\Table::LAYOUT_FIXED) {
                $style .= 'table-layout: fixed;';
            } elseif ($tableStyle->getLayout() == \PhpOffice\PhpWord\Style\Table::LAYOUT_AUTO) {
                $style .= 'table-layout: auto;';
            }
        }

        return $style . '"';
    }
}
