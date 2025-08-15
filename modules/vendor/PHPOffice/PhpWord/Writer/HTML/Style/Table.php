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

// ADDED HOSTCMS
namespace PhpOffice\PhpWord\Writer\HTML\Style;

use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Paragraph style HTML writer
 *
 * @since 0.10.0
 */
class Table extends AbstractStyle
{
    /**
     * Write style
     *
     * @return string
     */
    public function write()
    {
        $style = $this->getStyle();

// var_dump($style);

        if (!$style instanceof \PhpOffice\PhpWord\Style\Table
			&& !$style instanceof \PhpOffice\PhpWord\Style\Cell)
		{
            return '';
        }
        $css = array();

		$css['border-collapse'] = 'collapse';

		$css = array_merge($css, $this->_getArrayOfCss($style));

        return $this->assembleCss($css);
    }

	 /**
     * Write style
     *
     * @return string
     */
    public function writeTdStyle()
    {
		$style = $this->getStyle();

		var_dump($style);

        if (!$style instanceof \PhpOffice\PhpWord\Style\Table
			&& !$style instanceof \PhpOffice\PhpWord\Style\Cell)
		{
            return '';
        }

		$css = $this->_getArrayOfCss($style);

        return $this->assembleCss($css);
	}

	protected function _getArrayOfCss($style)
	{
		$css = array();

		$aBorderSizes = $style->getBorderSize();
		$aBorderColors = $style->getBorderColor();
		$aBorderStyles = $style->getBorderStyle();
// var_dump($style);
// var_dump($aBorderSizes, $aBorderColors, $aBorderStyles);

		if ($style->getBgColor())
		{
			$bgColor = $style->getBgColor() == 'auto'
				? 'FFF'
				: $style->getBgColor();

			$css['background-color'] = '#' . $bgColor;
		}

		$aBorderNames = array('top', 'left', 'right', 'bottom'/*, 'insideH', 'insideV'*/);
		foreach ($aBorderSizes as $key => $borderSize)
		{
			if ($borderSize && isset($aBorderNames[$key]))
			{
				$color = $aBorderColors[$key] == 'auto'
					? '000000'//$style->getDefaultBorderColor()
					: $aBorderColors[$key];

				switch ($aBorderStyles[$key])
				{
					case 'dashed':
						$type = 'dashed';
					break;
					case 'nil':
					case 'none':
						$type = 'none';
					break;
					case 'single':
					case 'thick':
					default:
						$type = 'solid';
					break;
				}

				$css['border-' . $aBorderNames[$key]] = $type . ' #' . $color . ' ' . round($borderSize/6, 2) . 'pt';
			}
		}
// var_dump($css);
		return $css;
	}
}
