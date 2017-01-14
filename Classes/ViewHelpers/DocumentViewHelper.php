<?php

namespace Bithost\Pdfviewhelpers\ViewHelpers;

/* * *
 *
 * This file is part of the "PDF ViewHelpers" Extension for TYPO3 CMS.
 *
 *  (c) 2016 Markus Mächler <markus.maechler@bithost.ch>, Bithost GmbH
 *           Esteban Marin <esteban.marin@bithost.ch>, Bithost GmbH
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 * * */

use Bithost\Pdfviewhelpers\Exception\Exception;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use FPDI;

/**
 * DocumentViewHelper
 *
 * @author Markus Mächler <markus.maechler@bithost.ch>, Esteban Marin <esteban.marin@bithost.ch>
 */
class DocumentViewHelper extends AbstractPDFViewHelper {

	/**
	 * TCPDF output destinations that send http headers and echo the pdf
	 *
	 * @var array
	 */
	protected $tcpdfOutputContentDestinations = ['I', 'D', 'FI', 'FD'];

	/**
	 * TCPDF output destinations that save the pdf to the filesystem
	 *
	 * @var array
	 */
	protected $tcpdfSaveFileDestinations = ['F', 'FI', 'FD'];

	/**
	 * @return void
	 */
	public function initializeArguments() {
		$this->registerArgument('title', 'string', '', FALSE, $this->settings['document']['title']);
		$this->registerArgument('subject', 'string', '', FALSE, $this->settings['document']['subject']);
		$this->registerArgument('author', 'string', '', FALSE, $this->settings['document']['author']);
		$this->registerArgument('keywords', 'string', '', FALSE, $this->settings['document']['keywords']);
		$this->registerArgument('creator', 'string', '', FALSE, $this->settings['document']['creator']);
		$this->registerArgument('outputDestination', 'string', '', FALSE, $this->settings['document']['outputDestination']);
		$this->registerArgument('outputPath', 'string', '', FALSE, $this->settings['document']['outputPath']);
		$this->registerArgument('sourceFile', 'string', '', FALSE, $this->settings['document']['sourceFile']);
	}

	/**
	 * @return void
	 */
	public function initialize() {
		$extPath = ExtensionManagementUtility::extPath('pdfviewhelpers');
		$pdfClassName = empty($this->settings['config']['class']) ? 'TCPDF' : $this->settings['config']['class'];

		require_once($extPath . 'Resources/Private/PHP/tcpdf/examples/lang/' . $this->settings['config']['language'] . '.php');
		require_once($extPath . 'Resources/Private/PHP/tcpdf/tcpdf.php');
		require_once($extPath . 'Resources/Private/PHP/fpdi/fpdi.php');

		$this->setPDF(GeneralUtility::makeInstance($pdfClassName));

		$this->getPDF()->setJPEGQuality($this->settings['config']['jpgQuality']);
		$this->getPDF()->SetTitle($this->arguments['title']);
		$this->getPDF()->SetSubject($this->arguments['subject']);
		$this->getPDF()->SetAuthor($this->arguments['author']);
		$this->getPDF()->SetKeywords($this->arguments['keywords']);
		$this->getPDF()->SetCreator($this->arguments['creator']);

		if (!empty($this->arguments['sourceFile'])) {
			if ($this->getPDF() instanceof FPDI) {
				$this->getPDF()->setSourceFile(PATH_site . $this->arguments['sourceFile']);
			} else {
				throw new Exception('PDF object must be instance of FPDI to support option "sourceFile". ERROR: 1474144733', 1474144733);
			}
		}

		if ($GLOBALS['TSFE'] && $this->settings['config']['disableCache']) {
			$GLOBALS['TSFE']->set_no_cache();
		}
	}

	/**
	 * @return void
	 */
	public function render() {
		$this->renderChildren();

		$outputPath = $this->arguments['outputPath'];

		if (in_array($this->arguments['outputDestination'], $this->tcpdfSaveFileDestinations)) {
			$outputPath = PATH_site . $outputPath;
		}

		$this->getPDF()->Output($outputPath, $this->arguments['outputDestination']);

		if (in_array($this->arguments['outputDestination'], $this->tcpdfOutputContentDestinations)) {
			//flush and close all outputs in order to prevent TYPO3 from sending other contents and let it finish gracefully
			ob_end_flush();
			ob_flush();
			flush();
		}
	}

}
