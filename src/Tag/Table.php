<?php

namespace Mpdf\Tag;

use Mpdf\Css\Border;
use Mpdf\Mpdf;

class Table extends Tag
{

	public function open($attr, &$ahtml, &$ihtml)
	{
		// Initialize variables to prevent undefined variable warnings
		$direction = '';
		$txta = '';
		$cellLineHeight = '';
		$cellLineStackingStrategy = '';
		$cellLineStackingShift = '';
		
		$this->mpdf->tdbegin = false;
		$this->mpdf->lastoptionaltag = '';
		// Disable vertical justification in columns
		if ($this->mpdf->ColActive) {
			$this->mpdf->colvAlign = '';
		} // *COLUMNS*
		if ($this->mpdf->lastblocklevelchange == 1) {
			$blockstate = 1;
		} // Top margins/padding only
		elseif ($this->mpdf->lastblocklevelchange < 1) {
			$blockstate = 0;
		} // NO margins/padding
		// called from block after new div e.g. <div> ... <table> ...    Outputs block top margin/border and padding
		if (count($this->mpdf->textbuffer) == 0 && $this->mpdf->lastblocklevelchange == 1 && !$this->mpdf->tableLevel && !$this->mpdf->kwt) {
			$this->mpdf->newFlowingBlock($this->mpdf->blk[$this->mpdf->blklvl]['width'], $this->mpdf->lineheight, '', false, 1, true, $this->mpdf->blk[$this->mpdf->blklvl]['direction']);
			$this->mpdf->finishFlowingBlock(true); // true = END of flowing block
		} elseif (!$this->mpdf->tableLevel && count($this->mpdf->textbuffer)) {
			$this->mpdf->printbuffer($this->mpdf->textbuffer, $blockstate);
		}

		$this->mpdf->textbuffer = [];
		$this->mpdf->lastblocklevelchange = -1;



		if ($this->mpdf->tableLevel) { // i.e. now a nested table coming...
			// Save current level table
			$this->mpdf->cell['PARENTCELL'] = $this->mpdf->saveInlineProperties();
			
			// Ensure tbctr is set for this table level
			if (!isset($this->mpdf->tbctr[$this->mpdf->tableLevel])) {
				$this->mpdf->tbctr[$this->mpdf->tableLevel] = 0;
			}
			
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['baseProperties'] = $this->mpdf->base_table_properties;
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['cells'] = $this->mpdf->cell;
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['currrow'] = $this->mpdf->row;
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['currcol'] = $this->mpdf->col;
		}
		$this->mpdf->tableLevel++;
		$this->cssManager->tbCSSlvl++;

		if ($this->mpdf->tableLevel > 1) { // inherit table properties from cell in which nested
			//$this->mpdf->base_table_properties['FONT-KERNING'] = ($this->mpdf->textvar & TextVars::FC_KERNING);	// mPDF 6
			$this->mpdf->base_table_properties['LETTER-SPACING'] = $this->mpdf->lSpacingCSS;
			$this->mpdf->base_table_properties['WORD-SPACING'] = $this->mpdf->wSpacingCSS;
			// mPDF 6
			if (isset($this->mpdf->cell) && isset($this->mpdf->row) && isset($this->mpdf->col) && 
				isset($this->mpdf->cell[$this->mpdf->row]) && 
				isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col])) {
				
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['direction'])) {
					$direction = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['direction'];
				}
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['a'])) {
					$txta = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['a'];
				}
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineHeight'])) {
					$cellLineHeight = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineHeight'];
				}
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineStackingStrategy'])) {
					$cellLineStackingStrategy = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineStackingStrategy'];
				}
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineStackingShift'])) {
					$cellLineStackingShift = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineStackingShift'];
				}
			}
		}

		if (isset($this->mpdf->tbctr[$this->mpdf->tableLevel])) {
			$this->mpdf->tbctr[$this->mpdf->tableLevel] ++;
		} else {
			$this->mpdf->tbctr[$this->mpdf->tableLevel] = 1;
		}

		$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['level'] = $this->mpdf->tableLevel;
		$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['levelid'] = $this->mpdf->tbctr[$this->mpdf->tableLevel];

		if ($this->mpdf->tableLevel > $this->mpdf->innermostTableLevel) {
			$this->mpdf->innermostTableLevel = $this->mpdf->tableLevel;
		}
		if ($this->mpdf->tableLevel > 1) {
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nestedpos'] = [
				$this->mpdf->row,
				$this->mpdf->col,
				$this->mpdf->tbctr[$this->mpdf->tableLevel - 1],
			];
		}
		//++++++++++++++++++++++++++++

		$this->mpdf->cell = [];
		$this->mpdf->col = -1; //int
		$this->mpdf->row = -1; //int
		$table = &$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]];

		// New table - any level
		$table['direction'] = $this->mpdf->directionality;
		$table['bgcolor'] = false;
		$table['va'] = false;
		$table['txta'] = false;
		$table['topntail'] = false;
		$table['thead-underline'] = false;
		$table['border'] = false;
		$table['border_details']['R']['w'] = 0;
		$table['border_details']['L']['w'] = 0;
		$table['border_details']['T']['w'] = 0;
		$table['border_details']['B']['w'] = 0;
		$table['border_details']['R']['style'] = '';
		$table['border_details']['L']['style'] = '';
		$table['border_details']['T']['style'] = '';
		$table['border_details']['B']['style'] = '';
		$table['max_cell_border_width']['R'] = 0;
		$table['max_cell_border_width']['L'] = 0;
		$table['max_cell_border_width']['T'] = 0;
		$table['max_cell_border_width']['B'] = 0;
		$table['padding']['L'] = false;
		$table['padding']['R'] = false;
		$table['padding']['T'] = false;
		$table['padding']['B'] = false;
		$table['margin']['L'] = false;
		$table['margin']['R'] = false;
		$table['margin']['T'] = false;
		$table['margin']['B'] = false;
		$table['a'] = false;
		$table['border_spacing_H'] = false;
		$table['border_spacing_V'] = false;
		$table['decimal_align'] = false;
		$this->mpdf->Reset();
		$this->mpdf->InlineProperties = [];
		$this->mpdf->InlineBDF = []; // mPDF 6
		$this->mpdf->InlineBDFctr = 0; // mPDF 6
		$table['nc'] = $table['nr'] = 0;
		$this->mpdf->tablethead = 0;
		$this->mpdf->tabletfoot = 0;
		$this->mpdf->tabletheadjustfinished = false;

		// mPDF 6
		if ($this->mpdf->tableLevel > 1) { // inherit table properties from cell in which nested
			$table['direction'] = $direction;
			$table['txta'] = $txta;
			$table['cellLineHeight'] = $cellLineHeight;
			$table['cellLineStackingStrategy'] = $cellLineStackingStrategy;
			$table['cellLineStackingShift'] = $cellLineStackingShift;
		}


		$lastbottommargin = 0;
		if ($this->mpdf->blockjustfinished && !count($this->mpdf->textbuffer) && $this->mpdf->y != $this->mpdf->tMargin && $this->mpdf->collapseBlockMargins && $this->mpdf->tableLevel == 1) {
			$lastbottommargin = $this->mpdf->lastblockbottommargin;
		}
		$this->mpdf->lastblockbottommargin = 0;
		$this->mpdf->blockjustfinished = false;

		if ($this->mpdf->tableLevel == 1) {
			$table['headernrows'] = 0;
			$table['footernrows'] = 0;
			$this->mpdf->base_table_properties = [];
		}

		// ADDED CSS FUNCIONS FOR TABLE
		if ($this->cssManager->tbCSSlvl == 1) {
			$properties = $this->cssManager->MergeCSS('TOPTABLE', 'TABLE', $attr);
		} else {
			$properties = $this->cssManager->MergeCSS('TABLE', 'TABLE', $attr);
		}

		$w = '';
		if (isset($properties['WIDTH'])) {
			$w = $properties['WIDTH'];
		} elseif (!empty($attr['WIDTH'])) {
			$w = $attr['WIDTH'];
		}

		if (isset($attr['ALIGN']) && array_key_exists(strtolower($attr['ALIGN']), self::ALIGN)) {
			$table['a'] = self::ALIGN[strtolower($attr['ALIGN'])];
		}
		if (!$table['a']) {
			if (isset($table['direction']) && $table['direction'] === 'rtl') {
				$table['a'] = 'R';
			} else {
				$table['a'] = 'L';
			}
		}

		if (!empty($properties['DIRECTION'])) {
			$table['direction'] = strtolower($properties['DIRECTION']);
		} elseif (!empty($attr['DIR'])) {
			$table['direction'] = strtolower($attr['DIR']);
		} elseif ($this->mpdf->tableLevel == 1) {
			$table['direction'] = $this->mpdf->blk[$this->mpdf->blklvl]['direction'];
        }
        $table['bgcolor'] = empty($table['bgcolor']) ? [] : $table['bgcolor'];
        if (isset($properties['BACKGROUND-COLOR'])) {
            $table['bgcolor'][-1] = $properties['BACKGROUND-COLOR'];
        } elseif (isset($properties['BACKGROUND'])) {
            $table['bgcolor'][-1] = $properties['BACKGROUND'];
		} elseif (isset($attr['BGCOLOR'])) {
			$table['bgcolor'][-1] = $attr['BGCOLOR'];
		}

		if (isset($properties['VERTICAL-ALIGN']) && array_key_exists(strtolower($properties['VERTICAL-ALIGN']), self::ALIGN)) {
			$table['va'] = self::ALIGN[strtolower($properties['VERTICAL-ALIGN'])];
		}
		if (isset($properties['TEXT-ALIGN']) && array_key_exists(strtolower($properties['TEXT-ALIGN']), self::ALIGN)) {
			$table['txta'] = self::ALIGN[strtolower($properties['TEXT-ALIGN'])];
		}

		if (!empty($properties['AUTOSIZE']) && $this->mpdf->tableLevel == 1) {
			$this->mpdf->shrink_this_table_to_fit = $properties['AUTOSIZE'];
			if ($this->mpdf->shrink_this_table_to_fit < 1) {
				$this->mpdf->shrink_this_table_to_fit = 0;
			}
		}
		if (!empty($properties['ROTATE']) && $this->mpdf->tableLevel == 1) {
			$this->mpdf->table_rotate = $properties['ROTATE'];
		}
		if (isset($properties['TOPNTAIL'])) {
			$table['topntail'] = $properties['TOPNTAIL'];
		}
		if (isset($properties['THEAD-UNDERLINE'])) {
			$table['thead-underline'] = $properties['THEAD-UNDERLINE'];
		}

		if (isset($properties['BORDER'])) {
			$bord = $this->mpdf->border_details($properties['BORDER']);
			if ($bord['s']) {
				$table['border'] = Border::ALL;
				$table['border_details']['R'] = $bord;
				$table['border_details']['L'] = $bord;
				$table['border_details']['T'] = $bord;
				$table['border_details']['B'] = $bord;
			}
		}
		if (isset($properties['BORDER-RIGHT'])) {
			if (isset($table['direction']) && $table['direction'] === 'rtl') {  // *OTL*
				$table['border_details']['R'] = $this->mpdf->border_details($properties['BORDER-LEFT']); // *OTL*
			} // *OTL*
			else { // *OTL*
				$table['border_details']['R'] = $this->mpdf->border_details($properties['BORDER-RIGHT']);
			} // *OTL*
			$this->mpdf->setBorder($table['border'], Border::RIGHT, $table['border_details']['R']['s']);
		}
		if (isset($properties['BORDER-LEFT'])) {
			if (isset($table['direction']) && $table['direction'] === 'rtl') {  // *OTL*
				$table['border_details']['L'] = $this->mpdf->border_details($properties['BORDER-RIGHT']); // *OTL*
			} // *OTL*
			else { // *OTL*
				$table['border_details']['L'] = $this->mpdf->border_details($properties['BORDER-LEFT']);
			} // *OTL*
			$this->mpdf->setBorder($table['border'], Border::LEFT, $table['border_details']['L']['s']);
		}
		if (isset($properties['BORDER-BOTTOM'])) {
			$table['border_details']['B'] = $this->mpdf->border_details($properties['BORDER-BOTTOM']);
			$this->mpdf->setBorder($table['border'], Border::BOTTOM, $table['border_details']['B']['s']);
		}
		if (isset($properties['BORDER-TOP'])) {
			$table['border_details']['T'] = $this->mpdf->border_details($properties['BORDER-TOP']);
			$this->mpdf->setBorder($table['border'], Border::TOP, $table['border_details']['T']['s']);
		}

		$this->mpdf->table_border_css_set = 0;
		if ($table['border']) {
			$this->mpdf->table_border_css_set = 1;
		}

		// mPDF 6
		if (!empty($properties['LANG'])) {
			if ($this->mpdf->autoLangToFont && !$this->mpdf->usingCoreFont) {
				if ($properties['LANG'] != $this->mpdf->default_lang && $properties['LANG'] !== 'UTF-8') {
					list ($coreSuitable, $mpdf_pdf_unifont) = $this->languageToFont->getLanguageOptions($properties['LANG'], $this->mpdf->useAdobeCJK);
					if ($mpdf_pdf_unifont) {
						$properties['FONT-FAMILY'] = $mpdf_pdf_unifont;
					}
				}
			}
			$this->mpdf->currentLang = $properties['LANG'];
		}


		if (isset($properties['FONT-FAMILY'])) {
			$this->mpdf->default_font = $properties['FONT-FAMILY'];
			$this->mpdf->SetFont($this->mpdf->default_font, '', 0, false);
		}
		$this->mpdf->base_table_properties['FONT-FAMILY'] = $this->mpdf->FontFamily;

		if (isset($properties['FONT-SIZE'])) {
			if ($this->mpdf->tableLevel > 1) {
				$tableFontSize = $this->sizeConverter->convert($this->mpdf->base_table_properties['FONT-SIZE']);
				$mmsize = $this->sizeConverter->convert($properties['FONT-SIZE'], $tableFontSize);
			} else {
				$mmsize = $this->sizeConverter->convert($properties['FONT-SIZE'], $this->mpdf->default_font_size / Mpdf::SCALE);
			}
			if ($mmsize) {
				$this->mpdf->default_font_size = $mmsize * Mpdf::SCALE;
				$this->mpdf->SetFontSize($this->mpdf->default_font_size, false);
			}
		}
		$this->mpdf->base_table_properties['FONT-SIZE'] = $this->mpdf->FontSize . 'mm';

		if (isset($properties['FONT-WEIGHT'])) {
			if (strtoupper($properties['FONT-WEIGHT']) === 'BOLD') {
				$this->mpdf->base_table_properties['FONT-WEIGHT'] = 'BOLD';
			}
		}
		if (isset($properties['FONT-STYLE'])) {
			if (strtoupper($properties['FONT-STYLE']) === 'ITALIC') {
				$this->mpdf->base_table_properties['FONT-STYLE'] = 'ITALIC';
			}
		}
		if (isset($properties['COLOR'])) {
			$this->mpdf->base_table_properties['COLOR'] = $properties['COLOR'];
		}
		if (isset($properties['FONT-KERNING'])) {
			$this->mpdf->base_table_properties['FONT-KERNING'] = $properties['FONT-KERNING'];
		}
		if (isset($properties['LETTER-SPACING'])) {
			$this->mpdf->base_table_properties['LETTER-SPACING'] = $properties['LETTER-SPACING'];
		}
		if (isset($properties['WORD-SPACING'])) {
			$this->mpdf->base_table_properties['WORD-SPACING'] = $properties['WORD-SPACING'];
		}
		// mPDF 6
		if (isset($properties['HYPHENS'])) {
			$this->mpdf->base_table_properties['HYPHENS'] = $properties['HYPHENS'];
		}
		if (!empty($properties['LINE-HEIGHT'])) {
			$table['cellLineHeight'] = $this->mpdf->fixLineheight($properties['LINE-HEIGHT']);
		} elseif ($this->mpdf->tableLevel == 1) {
			$table['cellLineHeight'] = $this->mpdf->blk[$this->mpdf->blklvl]['line_height'];
		}

		if (!empty($properties['LINE-STACKING-STRATEGY'])) {
			$table['cellLineStackingStrategy'] = strtolower($properties['LINE-STACKING-STRATEGY']);
		} elseif ($this->mpdf->tableLevel == 1 && isset($this->mpdf->blk[$this->mpdf->blklvl]['line_stacking_strategy'])) {
			$table['cellLineStackingStrategy'] = $this->mpdf->blk[$this->mpdf->blklvl]['line_stacking_strategy'];
		} else {
			$table['cellLineStackingStrategy'] = 'inline-line-height';
		}

		if (!empty($properties['LINE-STACKING-SHIFT'])) {
			$table['cellLineStackingShift'] = strtolower($properties['LINE-STACKING-SHIFT']);
		} elseif ($this->mpdf->tableLevel == 1 && isset($this->mpdf->blk[$this->mpdf->blklvl]['line_stacking_shift'])) {
			$table['cellLineStackingShift'] = $this->mpdf->blk[$this->mpdf->blklvl]['line_stacking_shift'];
		} else {
			$table['cellLineStackingShift'] = 'consider-shifts';
		}

		if (isset($properties['PADDING-LEFT'])) {
			$table['padding']['L'] = $this->sizeConverter->convert($properties['PADDING-LEFT'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}
		if (isset($properties['PADDING-RIGHT'])) {
			$table['padding']['R'] = $this->sizeConverter->convert($properties['PADDING-RIGHT'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}
		if (isset($properties['PADDING-TOP'])) {
			$table['padding']['T'] = $this->sizeConverter->convert($properties['PADDING-TOP'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}
		if (isset($properties['PADDING-BOTTOM'])) {
			$table['padding']['B'] = $this->sizeConverter->convert($properties['PADDING-BOTTOM'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}

		if (isset($properties['MARGIN-TOP'])) {
			if ($lastbottommargin) {
				$tmp = $this->sizeConverter->convert($properties['MARGIN-TOP'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
				if ($tmp > $lastbottommargin) {
					$properties['MARGIN-TOP'] = (int) $properties['MARGIN-TOP'] - $lastbottommargin;
				} else {
					$properties['MARGIN-TOP'] = 0;
				}
			}
			$table['margin']['T'] = $this->sizeConverter->convert($properties['MARGIN-TOP'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}

		if (isset($properties['MARGIN-BOTTOM'])) {
			$table['margin']['B'] = $this->sizeConverter->convert($properties['MARGIN-BOTTOM'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}
		if (isset($properties['MARGIN-LEFT'])) {
			$table['margin']['L'] = $this->sizeConverter->convert($properties['MARGIN-LEFT'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}

		if (isset($properties['MARGIN-RIGHT'])) {
			$table['margin']['R'] = $this->sizeConverter->convert($properties['MARGIN-RIGHT'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}
		if (isset($properties['MARGIN-LEFT'], $properties['MARGIN-RIGHT']) && strtolower($properties['MARGIN-LEFT']) === 'auto' && strtolower($properties['MARGIN-RIGHT']) === 'auto') {
			$table['a'] = 'C';
		} elseif (isset($properties['MARGIN-LEFT']) && strtolower($properties['MARGIN-LEFT']) === 'auto') {
			$table['a'] = 'R';
		} elseif (isset($properties['MARGIN-RIGHT']) && strtolower($properties['MARGIN-RIGHT']) === 'auto') {
			$table['a'] = 'L';
		}

		if (isset($properties['BORDER-COLLAPSE']) && strtoupper($properties['BORDER-COLLAPSE']) === 'SEPARATE') {
			$table['borders_separate'] = true;
		} else {
			$table['borders_separate'] = false;
		}

		// mPDF 5.7.3

		if (isset($properties['BORDER-SPACING-H'])) {
			$table['border_spacing_H'] = $this->sizeConverter->convert($properties['BORDER-SPACING-H'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}
		if (isset($properties['BORDER-SPACING-V'])) {
			$table['border_spacing_V'] = $this->sizeConverter->convert($properties['BORDER-SPACING-V'], $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
		}
		// mPDF 5.7.3
		if (!$table['borders_separate']) {
			$table['border_spacing_H'] = $table['border_spacing_V'] = 0;
		}

		if (isset($properties['EMPTY-CELLS'])) {
			$table['empty_cells'] = strtolower($properties['EMPTY-CELLS']);  // 'hide'  or 'show'
		} else {
			$table['empty_cells'] = '';
		}

		if (isset($properties['PAGE-BREAK-INSIDE']) && strtoupper($properties['PAGE-BREAK-INSIDE']) === 'AVOID' && $this->mpdf->tableLevel == 1 && !$this->mpdf->writingHTMLfooter) {
			$this->mpdf->table_keep_together = true;
		} elseif ($this->mpdf->tableLevel == 1) {
			$this->mpdf->table_keep_together = false;
		}
		if (isset($properties['PAGE-BREAK-AFTER']) && $this->mpdf->tableLevel == 1) {
			$table['page_break_after'] = strtoupper($properties['PAGE-BREAK-AFTER']);
		}

		/* -- BACKGROUNDS -- */
		if (isset($properties['BACKGROUND-GRADIENT']) && !$this->mpdf->kwt && !$this->mpdf->ColActive) {
			$table['gradient'] = $properties['BACKGROUND-GRADIENT'];
		}

		if (!empty($properties['BACKGROUND-IMAGE']) && !$this->mpdf->kwt && !$this->mpdf->ColActive) {
			$ret = $this->mpdf->SetBackground($properties, $this->mpdf->blk[$this->mpdf->blklvl]['inner_width']);
			if ($ret) {
				$table['background-image'] = $ret;
			}
		}
		/* -- END BACKGROUNDS -- */

		if (isset($properties['OVERFLOW'])) {
			$table['overflow'] = strtolower($properties['OVERFLOW']);  // 'hidden' 'wrap' or 'visible' or 'auto'
			if (($this->mpdf->ColActive || $this->mpdf->tableLevel > 1) && $table['overflow'] === 'visible') {
				unset($table['overflow']);
			}
		}

		if (isset($attr['CELLPADDING'])) {
			$table['cell_padding'] = $attr['CELLPADDING'];
		} else {
			$table['cell_padding'] = false;
		}

		if (isset($attr['BORDER']) && $attr['BORDER'] == '1') {
			$this->mpdf->table_border_attr_set = 1;
			$bord = $this->mpdf->border_details('#000000 1px solid');
			if ($bord['s']) {
				$table['border'] = Border::ALL;
				$table['border_details']['R'] = $bord;
				$table['border_details']['L'] = $bord;
				$table['border_details']['T'] = $bord;
				$table['border_details']['B'] = $bord;
			}
		} else {
			$this->mpdf->table_border_attr_set = 0;
		}

		if ($this->mpdf->tableLevel > 1) { // inherit table properties from cell in which nested
			//$this->mpdf->base_table_properties['FONT-KERNING'] = ($this->mpdf->textvar & TextVars::FC_KERNING);	// mPDF 6
			$this->mpdf->base_table_properties['LETTER-SPACING'] = $this->mpdf->lSpacingCSS;
			$this->mpdf->base_table_properties['WORD-SPACING'] = $this->mpdf->wSpacingCSS;
			// mPDF 6
			// Initialize variables to prevent undefined variable warnings
			$direction = '';
			$txta = '';
			$cellLineHeight = '';
			$cellLineStackingStrategy = '';
			$cellLineStackingShift = '';
			
			if (isset($this->mpdf->cell) && isset($this->mpdf->row) && isset($this->mpdf->col) && 
				isset($this->mpdf->cell[$this->mpdf->row]) && 
				isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col])) {
				
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['direction'])) {
					$direction = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['direction'];
				}
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['a'])) {
					$txta = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['a'];
				}
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineHeight'])) {
					$cellLineHeight = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineHeight'];
				}
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineStackingStrategy'])) {
					$cellLineStackingStrategy = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineStackingStrategy'];
				}
				if (isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineStackingShift'])) {
					$cellLineStackingShift = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['cellLineStackingShift'];
				}
			}
		}

		if ($w) {
			$maxwidth = $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'];
			if ($table['borders_separate']) {
				$tblblw = $table['margin']['L'] + $table['margin']['R'] + $table['border_details']['L']['w'] / 2 + $table['border_details']['R']['w'] / 2;
			} else {
				$tblblw = $table['margin']['L'] + $table['margin']['R'] + $table['max_cell_border_width']['L'] / 2 + $table['max_cell_border_width']['R'] / 2;
			}
			if (strpos($w, '%') && $this->mpdf->tableLevel == 1 && !$this->mpdf->ignore_table_percents) {
				// % needs to be of inner box without table margins etc.
				$maxwidth -= $tblblw;
				$wmm = $this->sizeConverter->convert($w, $maxwidth, $this->mpdf->FontSize, false);
				$table['w'] = $wmm + $tblblw;
			}
			if (strpos($w, '%') && $this->mpdf->tableLevel > 1 && !$this->mpdf->ignore_table_percents && $this->mpdf->keep_table_proportions) {
				$table['wpercent'] = (int) $w;  // makes 80% -> 80
			}
			if (!strpos($w, '%') && !$this->mpdf->ignore_table_widths) {
				$wmm = $this->sizeConverter->convert($w, $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'], $this->mpdf->FontSize, false);
				$table['w'] = $wmm + $tblblw;
			}
			if (!$this->mpdf->keep_table_proportions) {
				if (isset($table['w']) && $table['w'] > $this->mpdf->blk[$this->mpdf->blklvl]['inner_width']) {
					$table['w'] = $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'];
				}
			}
		}

		if (isset($attr['AUTOSIZE']) && $this->mpdf->tableLevel == 1) {
			$this->mpdf->shrink_this_table_to_fit = $attr['AUTOSIZE'];
			if ($this->mpdf->shrink_this_table_to_fit < 1) {
				$this->mpdf->shrink_this_table_to_fit = 1;
			}
		}
		if (isset($attr['ROTATE']) && $this->mpdf->tableLevel == 1) {
			$this->mpdf->table_rotate = $attr['ROTATE'];
		}

		//++++++++++++++++++++++++++++
		if ($this->mpdf->table_rotate) {
			$this->mpdf->tbrot_Links = [];
			$this->mpdf->tbrot_Annots = [];
			$this->mpdf->tbrotForms = [];
			$this->mpdf->tbrot_BMoutlines = [];
			$this->mpdf->tbrot_toc = [];
		}

		if ($this->mpdf->kwt) {
			if ($this->mpdf->table_rotate) {
				$this->mpdf->table_keep_together = true;
			}
			$this->mpdf->kwt = false;
			$this->mpdf->kwt_saved = true;
		}

		//++++++++++++++++++++++++++++
		$this->mpdf->plainCell_properties = [];
		unset($table);
	}

	public function close(&$ahtml, &$ihtml)
	{

		$this->mpdf->lastoptionaltag = '';
		unset($this->cssManager->tablecascadeCSS[$this->cssManager->tbCSSlvl]);
		$this->cssManager->tbCSSlvl--;
		$this->mpdf->ignorefollowingspaces = true; //Eliminate exceeding left-side spaces
		// mPDF 5.7.3
		// In case a colspan (on a row after first row) exceeded number of columns in table
		// Check if required array keys exist
		if (!isset($this->mpdf->tbctr[$this->mpdf->tableLevel])) {
			$this->mpdf->tbctr[$this->mpdf->tableLevel] = 0;
		}
		
		// Ensure table array is properly initialized
		if (!isset($this->mpdf->table[$this->mpdf->tableLevel]) || 
		    !isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]) ||
		    !isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nr'])) {
			// Skip processing if table structure is not properly defined
			$nr = 0;
		} else {
			$nr = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nr'];
		}
		
		for ($k = 0; $k < $nr; $k++) {
			// Initialize the row array if it doesn't exist
			if (!isset($this->mpdf->cell[$k])) {
				$this->mpdf->cell[$k] = [];
			}
			
			// Get number of columns, defaulting to 0 if not set
			$nc = 0;
			if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]) &&
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nc'])) {
				$nc = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nc'];
			}
			
			for ($l = 0; $l < $nc; $l++) {
				if (!isset($this->mpdf->cell[$k][$l])) {
					for ($n = $l - 1; $n >= 0; $n--) {
						if (isset($this->mpdf->cell[$k][$n]) && $this->mpdf->cell[$k][$n] != 0) {
							break;
						}
					}
					$this->mpdf->cell[$k][$l] = [
						'a' => 'C',
						'va' => 'M',
						'R' => false,
						'nowrap' => false,
						'bgcolor' => false,
						'padding' => ['L' => false, 'R' => false, 'T' => false, 'B' => false],
						'gradient' => false,
						's' => 0,
						'maxs' => 0,
						'textbuffer' => [],
						'dfs' => $this->mpdf->FontSize,
					];

					if (!$this->mpdf->simpleTables) {
						$this->mpdf->cell[$k][$l]['border'] = 0;
						$this->mpdf->cell[$k][$l]['border_details']['R'] = ['s' => 0, 'w' => 0, 'c' => false, 'style' => 'none', 'dom' => 0];
						$this->mpdf->cell[$k][$l]['border_details']['L'] = ['s' => 0, 'w' => 0, 'c' => false, 'style' => 'none', 'dom' => 0];
						$this->mpdf->cell[$k][$l]['border_details']['T'] = ['s' => 0, 'w' => 0, 'c' => false, 'style' => 'none', 'dom' => 0];
						$this->mpdf->cell[$k][$l]['border_details']['B'] = ['s' => 0, 'w' => 0, 'c' => false, 'style' => 'none', 'dom' => 0];
						$this->mpdf->cell[$k][$l]['border_details']['mbw'] = ['BL' => 0, 'BR' => 0, 'RT' => 0, 'RB' => 0, 'TL' => 0, 'TR' => 0, 'LT' => 0, 'LB' => 0];
						if ($this->mpdf->packTableData) {
							$this->mpdf->cell[$k][$l]['borderbin'] = $this->mpdf->_packCellBorder($this->mpdf->cell[$k][$l]);
							unset($this->mpdf->cell[$k][$l]['border'], $this->mpdf->cell[$k][$l]['border_details']);
						}
					}
				}
			}
		}
		$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['cells'] = $this->mpdf->cell;
		
		// Ensure 'nc' (number of columns) is set before using it
		$nc = 0;
		if (isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nc'])) {
			$nc = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nc'];
		}
		
		$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['wc'] = array_pad(
			[],
			$nc,
			['miw' => 0, 'maw' => 0]
		);
		
		// Ensure 'nr' (number of rows) is set before using it
		$nr = 0;
		if (isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nr'])) {
			$nr = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nr'];
		}
		
		$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['hr'] = array_pad(
			[],
			$nr,
			0
		);

		// Move table footer <tfoot> row to end of table
		if (isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['is_tfoot'])
			&& count($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['is_tfoot'])) {
			$tfrows = [];
			foreach ($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['is_tfoot'] as $r => $val) {
				if ($val) {
					$tfrows[] = $r;
				}
			}
			$temp = [];
			$temptf = [];
			foreach ($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['cells'] as $k => $row) {
				if (in_array($k, $tfrows)) {
					$temptf[] = $row;
				} else {
					$temp[] = $row;
				}
			}
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['is_tfoot'] = [];
			for ($i = count($temp); $i < (count($temp) + count($temptf)); $i++) {
				$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['is_tfoot'][$i] = true;
			}
			// Update nestedpos row references
			if (isset($this->mpdf->table[$this->mpdf->tableLevel + 1]) && count($this->mpdf->table[$this->mpdf->tableLevel + 1])) {
				foreach ($this->mpdf->table[$this->mpdf->tableLevel + 1] as $nid => $nested) {
					$this->mpdf->table[$this->mpdf->tableLevel + 1][$nid]['nestedpos'][0] -= count($temptf);
				}
			}
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['cells'] = array_merge($temp, $temptf);

			// Update other arays set on row number
			// [trbackground-images] [trgradients]
			$temptrbgi = [];
			$temptrbgg = [];
			$temptrbgc = [];
			if (isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['bgcolor'][-1])) {
				$temptrbgc[-1] = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['bgcolor'][-1];
			}
			
			// Get number of rows, defaulting to 0 if not set
			$nr = 0;
			if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]) &&
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nr'])) {
				$nr = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['nr'];
			}
			
			for ($k = 0; $k < $nr; $k++) {
				if (!in_array($k, $tfrows)) {
					$temptrbgi[] = isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trbackground-images'][$k])
						? $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trbackground-images'][$k]
						: null;
					$temptrbgg[] = isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trgradients'][$k])
						? $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trgradients'][$k]
						: null;
					$temptrbgc[] = isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['bgcolor'][$k])
						? $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['bgcolor'][$k]
						: null;
				}
			}
			
			// Reuse the same $nr variable for the second loop
			for ($k = 0; $k < $nr; $k++) {
				if (in_array($k, $tfrows)) {
					$temptrbgi[] = isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trbackground-images'][$k])
						? $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trbackground-images'][$k]
						: null;
					$temptrbgg[] = isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trgradients'][$k])
						? $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trgradients'][$k]
						: null;
					$temptrbgc[] = isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['bgcolor'][$k])
						? $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['bgcolor'][$k]
						: null;
				}
			}
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trbackground-images'] = $temptrbgi;
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['trgradients'] = $temptrbgg;
			$this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['bgcolor'] = $temptrbgc;
			// Should Update all other arays set on row number, but cell properties have been set so not needed
			// [bgcolor] [trborder-left] [trborder-right] [trborder-top] [trborder-bottom]
		}

		// Check if table direction is set to RTL
		if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
		    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]) && 
		    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['direction']) && 
		    $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['direction'] === 'rtl') {
			$this->mpdf->_reverseTableDir($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]);
		}

		// Fix Borders *********************************************
		// Only call _fixTableBorders if the table structure exists
		if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
		    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]])) {
			$this->mpdf->_fixTableBorders($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]);
		}

		if ($this->mpdf->ColActive) {
			$this->mpdf->table_rotate = 0;
		} // *COLUMNS*
		if ($this->mpdf->table_rotate <> 0) {
			$this->mpdf->tablebuffer = '';
			// Max width for rotated table
			$this->mpdf->tbrot_maxw = $this->mpdf->h - ($this->mpdf->y + $this->mpdf->bMargin + 1);
			$this->mpdf->tbrot_maxh = $this->mpdf->blk[$this->mpdf->blklvl]['inner_width'];  // Max width for rotated table
			
			// Set table alignment - if table['a'] is not set, use default alignment
			if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['a'])) {
				$this->mpdf->tbrot_align = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['a'];
			} else {
				$this->mpdf->tbrot_align = '';
			}
		}
		$this->mpdf->shrin_k = 1;

		if ($this->mpdf->shrink_tables_to_fit < 1) {
			$this->mpdf->shrink_tables_to_fit = 1;
		}
		if (!$this->mpdf->shrink_this_table_to_fit) {
			$this->mpdf->shrink_this_table_to_fit = $this->mpdf->shrink_tables_to_fit;
		}

		if ($this->mpdf->tableLevel > 1) {
			// deal with nested table

			// Only call _tableColumnWidth if the table structure exists
			if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]])) {
				$this->mpdf->_tableColumnWidth($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]], true);
			}

			// Initialize variables with default values
			$tmiw = 0;
			$tmaw = 0;
			$tl = 0;
			
			// Only access table properties if they exist
			if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]])) {
				$tmiw = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['miw'] ?? 0;
				$tmaw = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['maw'] ?? 0;
				$tl = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['tl'] ?? 0;
			}

			// Go down to lower table level
			$this->mpdf->tableLevel--;

			// Reset lower level table properties if they exist
			if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['baseProperties'])) {
				$this->mpdf->base_table_properties = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['baseProperties'];
				// mPDF 5.7.3
				$this->mpdf->default_font = $this->mpdf->base_table_properties['FONT-FAMILY'];
				$this->mpdf->SetFont($this->mpdf->default_font, '', 0, false);
				$this->mpdf->default_font_size = $this->sizeConverter->convert($this->mpdf->base_table_properties['FONT-SIZE']) * Mpdf::SCALE;
				$this->mpdf->SetFontSize($this->mpdf->default_font_size, false);
			}

			// Only access cells if they exist
			if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['cells'])) {
				$this->mpdf->cell = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['cells'];
				if (isset($this->mpdf->cell['PARENTCELL'])) {
					if ($this->mpdf->cell['PARENTCELL']) {
						$this->mpdf->restoreInlineProperties($this->mpdf->cell['PARENTCELL']);
					}
					unset($this->mpdf->cell['PARENTCELL']);
				}
			} else {
				$this->mpdf->cell = [];
			}
			
			// Only access row and col if they exist
			if (isset($this->mpdf->table[$this->mpdf->tableLevel]) && 
			    isset($this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]])) {
				$this->mpdf->row = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['currrow'] ?? 0;
				$this->mpdf->col = $this->mpdf->table[$this->mpdf->tableLevel][$this->mpdf->tbctr[$this->mpdf->tableLevel]]['currcol'] ?? 0;
			} else {
				$this->mpdf->row = 0;
				$this->mpdf->col = 0;
			}
			$objattr = [];
			$objattr['type'] = 'nestedtable';
			$objattr['nestedcontent'] = $this->mpdf->tbctr[$this->mpdf->tableLevel + 1];
			$objattr['table'] = $this->mpdf->tbctr[$this->mpdf->tableLevel];
			$objattr['row'] = $this->mpdf->row;
			$objattr['col'] = $this->mpdf->col;
			$objattr['level'] = $this->mpdf->tableLevel;
			$e = "\xbb\xa4\xactype=nestedtable,objattr=" . serialize($objattr) . "\xbb\xa4\xac";
			$this->mpdf->_saveCellTextBuffer($e);
			
			// Initialize 's' if it doesn't exist
			if (!isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['s'])) {
				$this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['s'] = 0;
			}
			
			$this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['s'] += $tl;
			
			if (!isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['maxs'])) {
				$this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['maxs'] = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['s'];
			} elseif ($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['maxs'] < $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['s']) {
				$this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['maxs'] = $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['s'];
			}
			$this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['s'] = 0; // reset
			if ((isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['nestedmaw']) && $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['nestedmaw'] < $tmaw)
				|| !isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['nestedmaw'])) {
				$this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['nestedmaw'] = $tmaw;
			}
			if ((isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['nestedmiw']) && $this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['nestedmiw'] < $tmiw)
				|| !isset($this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['nestedmiw'])) {
				$this->mpdf->cell[$this->mpdf->row][$this->mpdf->col]['nestedmiw'] = $tmiw;
			}
			$this->mpdf->tdbegin = true;
			$this->mpdf->nestedtablejustfinished = true;
			$this->mpdf->ignorefollowingspaces = true;
			return;
		}
		$this->mpdf->cMarginL = 0;
		$this->mpdf->cMarginR = 0;
		$this->mpdf->cMarginT = 0;
		$this->mpdf->cMarginB = 0;
		$this->mpdf->cellPaddingL = 0;
		$this->mpdf->cellPaddingR = 0;
		$this->mpdf->cellPaddingT = 0;
		$this->mpdf->cellPaddingB = 0;

		if (isset($this->mpdf->table[1][1]['overflow']) && $this->mpdf->table[1][1]['overflow'] === 'visible') {
			if ($this->mpdf->kwt || $this->mpdf->table_rotate || $this->mpdf->table_keep_together || $this->mpdf->ColActive) {
				$this->mpdf->kwt = false;
				$this->mpdf->table_rotate = 0;
				$this->mpdf->table_keep_together = false;
				//throw new \Mpdf\MpdfException("mPDF Warning: You cannot use CSS overflow:visible together with any of these functions:
				// 'Keep-with-table', rotated tables, page-break-inside:avoid, or columns");
			}
			$this->mpdf->_tableColumnWidth($this->mpdf->table[1][1], true);
			$this->mpdf->_tableWidth($this->mpdf->table[1][1]);
		} else {
			if (!$this->mpdf->kwt_saved) {
				$this->mpdf->kwt_height = 0;
			}

			list($check, $tablemiw) = $this->mpdf->_tableColumnWidth($this->mpdf->table[1][1], true);
			$save_table = $this->mpdf->table;
			$reset_to_minimum_width = false;
			$added_page = false;

			if ($check > 1) {
				if ($check > $this->mpdf->shrink_this_table_to_fit && $this->mpdf->table_rotate) {
					if ($this->mpdf->y != $this->mpdf->tMargin) {
						$this->mpdf->AddPage($this->mpdf->CurOrientation);
						$this->mpdf->kwt_moved = true;
					}
					$added_page = true;
					$this->mpdf->tbrot_maxw = $this->mpdf->h - ($this->mpdf->y + $this->mpdf->bMargin + 5) - $this->mpdf->kwt_height;
					//$check = $tablemiw/$this->mpdf->tbrot_maxw; 	// undo any shrink
					$check = 1;  // undo any shrink
				}
				$reset_to_minimum_width = true;
			}

			if ($reset_to_minimum_width) {
				$this->mpdf->shrin_k = $check;

				$this->mpdf->default_font_size /= $this->mpdf->shrin_k;
				$this->mpdf->SetFontSize($this->mpdf->default_font_size, false);

				$this->mpdf->shrinkTable($this->mpdf->table[1][1], $this->mpdf->shrin_k);

				$this->mpdf->_tableColumnWidth($this->mpdf->table[1][1]); // repeat
				// Starting at $this->mpdf->innermostTableLevel
				// Shrink table values - and redo columnWidth
				for ($lvl = 2; $lvl <= $this->mpdf->innermostTableLevel; $lvl++) {
					for ($nid = 1; $nid <= $this->mpdf->tbctr[$lvl]; $nid++) {
						$this->mpdf->shrinkTable($this->mpdf->table[$lvl][$nid], $this->mpdf->shrin_k);
						$this->mpdf->_tableColumnWidth($this->mpdf->table[$lvl][$nid]);
					}
				}
			}

			// Set table cell widths for top level table
			// Use $shrin_k to resize but don't change again
			$this->mpdf->SetLineHeight('', $this->mpdf->table[1][1]['cellLineHeight'] ?? 0);

			// Top level table
			$this->mpdf->_tableWidth($this->mpdf->table[1][1]);
		}

		// Now work through any nested tables setting child table[w'] = parent cell['w']
		// Now do nested tables _tableWidth
		for ($lvl = 2; $lvl <= $this->mpdf->innermostTableLevel; $lvl++) {
			for ($nid = 1; $nid <= $this->mpdf->tbctr[$lvl]; $nid++) {
				// HERE set child table width = cell width

				list($parentrow, $parentcol, $parentnid) = $this->mpdf->table[$lvl][$nid]['nestedpos'];

				$c = & $this->mpdf->table[$lvl - 1][$parentnid]['cells'][$parentrow][$parentcol];

				if (isset($c['colspan']) && $c['colspan'] > 1) {
					$parentwidth = 0;
					for ($cs = 0; $cs < $c['colspan']; $cs++) {
						$parentwidth += $this->mpdf->table[$lvl - 1][$parentnid]['wc'][$parentcol + $cs] ?? 0;
					}
				} else {
					$parentwidth = $this->mpdf->table[$lvl - 1][$parentnid]['wc'][$parentcol] ?? 0;
				}

				//$parentwidth -= ALLOW FOR PADDING ETC. in parent cell
				if (!$this->mpdf->simpleTables) {
					if ($this->mpdf->packTableData) {
						list($bt, $br, $bb, $bl) = $this->mpdf->_getBorderWidths($c['borderbin'] ?? '');
					} else {
						$br = $c['border_details']['R']['w'] ?? 0;
						$bl = $c['border_details']['L']['w'] ?? 0;
					}
					$paddingL = $c['padding']['L'] ?? 0;
					$paddingR = $c['padding']['R'] ?? 0;
					if ($this->mpdf->table[$lvl - 1][$parentnid]['borders_separate'] ?? false) {
						$parentwidth -= $br + $bl + $paddingL + $paddingR + ($this->mpdf->table[$lvl - 1][$parentnid]['border_spacing_H'] ?? 0);
					} else {
						$parentwidth -= $br / 2 + $bl / 2 + $paddingL + $paddingR;
					}
				} elseif ($this->mpdf->simpleTables) {
					$paddingL = $c['padding']['L'] ?? 0;
					$paddingR = $c['padding']['R'] ?? 0;
					$simpleBorders = $this->mpdf->table[$lvl - 1][$parentnid]['simple']['border_details'] ?? null;
					if ($simpleBorders && ($this->mpdf->table[$lvl - 1][$parentnid]['borders_separate'] ?? false)) {
						$parentwidth -= ($simpleBorders['L']['w'] ?? 0)
							+ ($simpleBorders['R']['w'] ?? 0) + $paddingL
							+ $paddingR + ($this->mpdf->table[$lvl - 1][$parentnid]['border_spacing_H'] ?? 0);
					} elseif ($simpleBorders) {
						$parentwidth -= ($simpleBorders['L']['w'] ?? 0) / 2
							+ ($simpleBorders['R']['w'] ?? 0) / 2 + $paddingL + $paddingR;
					}
				}
				if (!empty($this->mpdf->table[$lvl][$nid]['wpercent']) && $lvl > 1) {
					$this->mpdf->table[$lvl][$nid]['w'] = $parentwidth;
				} elseif ($parentwidth > ($this->mpdf->table[$lvl][$nid]['maw'] ?? 0)) {
					$this->mpdf->table[$lvl][$nid]['w'] = $this->mpdf->table[$lvl][$nid]['maw'];
				} else {
					$this->mpdf->table[$lvl][$nid]['w'] = $parentwidth;
				}
				unset($c);
				$this->mpdf->_tableWidth($this->mpdf->table[$lvl][$nid]);
			}
		}

		// Starting at $this->mpdf->innermostTableLevel
		// Cascade back up nested tables: setting heights back up the tree
		for ($lvl = $this->mpdf->innermostTableLevel; $lvl > 0; $lvl--) {
			for ($nid = 1; $nid <= $this->mpdf->tbctr[$lvl]; $nid++) {
				list($tableheight, $maxrowheight, $fullpage, $remainingpage, $maxfirstrowheight) = $this->mpdf->_tableHeight($this->mpdf->table[$lvl][$nid]);
			}
		}

		// Initialize variables with default values in case they weren't set in the loop
		$tableheight = $tableheight ?? 0;
		$fullpage = $fullpage ?? 0;
		$remainingpage = $remainingpage ?? 0;
		$maxfirstrowheight = $maxfirstrowheight ?? 0;
		$maxrowheight = $maxrowheight ?? 0;

		if (($this->mpdf->table[1][1]['overflow'] ?? '') === 'visible') {
			if ($maxrowheight > $fullpage) {
				throw new \Mpdf\MpdfException('mPDF Warning: A Table row is greater than available height. You cannot use CSS overflow:visible');
			}
			if ($maxfirstrowheight > $remainingpage) {
				$this->mpdf->AddPage($this->mpdf->CurOrientation);
			}
			$r = 0;
			$c = 0;
			$p = 0;
			$y = 0;
			$finished = false;
			while (!$finished) {
				list($finished, $r, $c, $p, $y, $y0) = $this->mpdf->_tableWrite($this->mpdf->table[1][1], true, $r, $c, $p, $y);
				if (!$finished) {
					$this->mpdf->AddPage($this->mpdf->CurOrientation);
					// If printed something on first spread, set same y
					if ($r == 0 && $y0 > -1) {
						$this->mpdf->y = $y0;
					}
				}
			}
		} else {
			$recalculate = 1;
			$forcerecalc = false;
			// Initialize iteration counter
			$iteration = 1;
			// RESIZING ALGORITHM
			if ($maxrowheight > $fullpage) {
				$recalculate = $this->tbsqrt($maxrowheight / $fullpage, 1);
				$forcerecalc = true;
			} elseif ($this->mpdf->table_rotate) { // NB $remainingpage == $fullpage == the width of the page
				if ($tableheight > $remainingpage) {
					// If can fit on remainder of page whilst respecting autsize value..
					if (($this->mpdf->shrin_k * $this->tbsqrt($tableheight / $remainingpage, 1)) <= $this->mpdf->shrink_this_table_to_fit) {
						$recalculate = $this->tbsqrt($tableheight / $remainingpage, 1);
					} elseif (!$added_page) {
						if ($this->mpdf->y != $this->mpdf->tMargin) {
							$this->mpdf->AddPage($this->mpdf->CurOrientation);
							$this->mpdf->kwt_moved = true;
						}
						$added_page = true;
						$this->mpdf->tbrot_maxw = $this->mpdf->h - ($this->mpdf->y + $this->mpdf->bMargin + 5) - $this->mpdf->kwt_height;
						// 0.001 to force it to recalculate
						$recalculate = (1 / $this->mpdf->shrin_k) + 0.001;  // undo any shrink
					}
				} else {
					$recalculate = 1;
				}
			} elseif ($this->mpdf->table_keep_together || (($this->mpdf->table[1][1]['nr'] ?? 0) == 1 && !$this->mpdf->writingHTMLfooter)) {
				if ($tableheight > $fullpage) {
					if (($this->mpdf->shrin_k * $this->tbsqrt($tableheight / $fullpage, 1)) <= $this->mpdf->shrink_this_table_to_fit) {
						$recalculate = $this->tbsqrt($tableheight / $fullpage, 1);
					} elseif ($this->mpdf->tableMinSizePriority) {
						$this->mpdf->table_keep_together = false;
						$recalculate = 1.001;
					} else {
						if ($this->mpdf->y != $this->mpdf->tMargin) {
							$this->mpdf->AddPage($this->mpdf->CurOrientation);
							$this->mpdf->kwt_moved = true;
						}
						$added_page = true;
						$this->mpdf->tbrot_maxw = $this->mpdf->h - ($this->mpdf->y + $this->mpdf->bMargin + 5) - $this->mpdf->kwt_height;
						$recalculate = $this->tbsqrt($tableheight / $fullpage, 1);
					}
				} elseif ($tableheight > $remainingpage) {
					// If can fit on remainder of page whilst respecting autsize value..
					if (($this->mpdf->shrin_k * $this->tbsqrt($tableheight / $remainingpage, 1)) <= $this->mpdf->shrink_this_table_to_fit) {
						$recalculate = $this->tbsqrt($tableheight / $remainingpage, 1);
					} else {
						if ($this->mpdf->y != $this->mpdf->tMargin) {
							// mPDF 6
							if ($this->mpdf->AcceptPageBreak()) {
								$this->mpdf->AddPage($this->mpdf->CurOrientation);
							} elseif ($this->mpdf->ColActive && $tableheight > (($this->mpdf->h - $this->mpdf->bMargin) - $this->mpdf->y0)) {
								$this->mpdf->AddPage($this->mpdf->CurOrientation);
							}
							$this->mpdf->kwt_moved = true;
						}
						$added_page = true;
						$this->mpdf->tbrot_maxw = $this->mpdf->h - ($this->mpdf->y + $this->mpdf->bMargin + 5) - $this->mpdf->kwt_height;
						$recalculate = 1.001;
					}
				} else {
					$recalculate = 1;
				}
			} else {
				$recalculate = 1;
			}

			if ($recalculate > $this->mpdf->shrink_this_table_to_fit && !$forcerecalc) {
				$recalculate = $this->mpdf->shrink_this_table_to_fit;
			}

			$iteration = 1;

			// RECALCULATE
			while ($recalculate <> 1) {
				$this->mpdf->shrin_k1 = $recalculate;
				$this->mpdf->shrin_k *= $recalculate;
				$this->mpdf->default_font_size /= $this->mpdf->shrin_k1;
				$this->mpdf->SetFontSize($this->mpdf->default_font_size, false);
				$this->mpdf->SetLineHeight('', $this->mpdf->table[1][1]['cellLineHeight'] ?? 0);
				$this->mpdf->table = $save_table;
				if ($this->mpdf->shrin_k <> 1) {
					$this->mpdf->shrinkTable($this->mpdf->table[1][1], $this->mpdf->shrin_k);
				}
				$this->mpdf->_tableColumnWidth($this->mpdf->table[1][1]); // repeat
				// Starting at $this->mpdf->innermostTableLevel
				// Shrink table values - and redo columnWidth
				for ($lvl = 2; $lvl <= $this->mpdf->innermostTableLevel; $lvl++) {
					for ($nid = 1; $nid <= $this->mpdf->tbctr[$lvl]; $nid++) {
						if ($this->mpdf->shrin_k <> 1) {
							$this->mpdf->shrinkTable($this->mpdf->table[$lvl][$nid], $this->mpdf->shrin_k);
						}
						$this->mpdf->_tableColumnWidth($this->mpdf->table[$lvl][$nid]);
					}
				}
				// Set table cell widths for top level table
				// Top level table
				$this->mpdf->_tableWidth($this->mpdf->table[1][1]);

				// Now work through any nested tables setting child table[w'] = parent cell['w']
				// Now do nested tables _tableWidth
				for ($lvl = 2; $lvl <= $this->mpdf->innermostTableLevel; $lvl++) {
					for ($nid = 1; $nid <= $this->mpdf->tbctr[$lvl]; $nid++) {
						// HERE set child table width = cell width

						list($parentrow, $parentcol, $parentnid) = $this->mpdf->table[$lvl][$nid]['nestedpos'];
						$c = & $this->mpdf->table[$lvl - 1][$parentnid]['cells'][$parentrow][$parentcol];

						if (isset($c['colspan']) && $c['colspan'] > 1) {
							$parentwidth = 0;
							for ($cs = 0; $cs < $c['colspan']; $cs++) {
								$parentwidth += $this->mpdf->table[$lvl - 1][$parentnid]['wc'][$parentcol + $cs] ?? 0;
							}
						} else {
							$parentwidth = $this->mpdf->table[$lvl - 1][$parentnid]['wc'][$parentcol] ?? 0;
						}

						//$parentwidth -= ALLOW FOR PADDING ETC.in parent cell
						if (!$this->mpdf->simpleTables) {
							if ($this->mpdf->packTableData) {
								list($bt, $br, $bb, $bl) = $this->mpdf->_getBorderWidths($c['borderbin'] ?? '');
							} else {
								$br = $c['border_details']['R']['w'] ?? 0;
								$bl = $c['border_details']['L']['w'] ?? 0;
							}
							$paddingL = $c['padding']['L'] ?? 0;
							$paddingR = $c['padding']['R'] ?? 0;
							if ($this->mpdf->table[$lvl - 1][$parentnid]['borders_separate'] ?? false) {
								$parentwidth -= $br + $bl + $paddingL + $paddingR + ($this->mpdf->table[$lvl - 1][$parentnid]['border_spacing_H'] ?? 0);
							} else {
								$parentwidth -= $br / 2 + $bl / 2 + $paddingL + $paddingR;
							}
						} elseif ($this->mpdf->simpleTables) {
							$paddingL = $c['padding']['L'] ?? 0;
							$paddingR = $c['padding']['R'] ?? 0;
							$simpleBorders = $this->mpdf->table[$lvl - 1][$parentnid]['simple']['border_details'] ?? null;
							if ($simpleBorders && ($this->mpdf->table[$lvl - 1][$parentnid]['borders_separate'] ?? false)) {
								$parentwidth -= ($simpleBorders['L']['w'] ?? 0)
									+ ($simpleBorders['R']['w'] ?? 0) + $paddingL
									+ $paddingR + ($this->mpdf->table[$lvl - 1][$parentnid]['border_spacing_H'] ?? 0);
							} elseif ($simpleBorders) {
								$parentwidth -= ($simpleBorders['L']['w'] ?? 0) / 2
									+ ($simpleBorders['R']['w'] ?? 0) / 2 + $paddingL + $paddingR;
							}
						}
						if (!empty($this->mpdf->table[$lvl][$nid]['wpercent']) && $lvl > 1) {
							$this->mpdf->table[$lvl][$nid]['w'] = $parentwidth;
						} elseif ($parentwidth > ($this->mpdf->table[$lvl][$nid]['maw'] ?? 0)) {
							$this->mpdf->table[$lvl][$nid]['w'] = $this->mpdf->table[$lvl][$nid]['maw'];
						} else {
							$this->mpdf->table[$lvl][$nid]['w'] = $parentwidth;
						}
						unset($c);
						$this->mpdf->_tableWidth($this->mpdf->table[$lvl][$nid]);
					}
				}

				// Starting at $this->mpdf->innermostTableLevel
				// Cascade back up nested tables: setting heights back up the tree
				for ($lvl = $this->mpdf->innermostTableLevel; $lvl > 0; $lvl--) {
					for ($nid = 1; $nid <= $this->mpdf->tbctr[$lvl]; $nid++) {
						list($tableheight, $maxrowheight, $fullpage, $remainingpage, $maxfirstrowheight) = $this->mpdf->_tableHeight($this->mpdf->table[$lvl][$nid]);
					}
				}

				// Initialize variables with default values in case they weren't set in the loop
				$tableheight = $tableheight ?? 0;
				$fullpage = $fullpage ?? 0;
				$remainingpage = $remainingpage ?? 0;
				$maxfirstrowheight = $maxfirstrowheight ?? 0;
				$maxrowheight = $maxrowheight ?? 0;

				// RESIZING ALGORITHM

				if ($maxrowheight > $fullpage) {
					$recalculate = $this->tbsqrt($maxrowheight / $fullpage, $iteration);
					$iteration++;
				} elseif ($this->mpdf->table_rotate && $tableheight > $remainingpage && !$added_page) {
					// If can fit on remainder of page whilst respecting autosize value..
					if (($this->mpdf->shrin_k * $this->tbsqrt($tableheight / $remainingpage, $iteration)) <= $this->mpdf->shrink_this_table_to_fit) {
						$recalculate = $this->tbsqrt($tableheight / $remainingpage, $iteration);
						$iteration++;
					} else {
						if (!$added_page) {
							$this->mpdf->AddPage($this->mpdf->CurOrientation);
							$added_page = true;
							$this->mpdf->kwt_moved = true;
							$this->mpdf->tbrot_maxw = $this->mpdf->h - ($this->mpdf->y + $this->mpdf->bMargin + 5) - $this->mpdf->kwt_height;
						}
						// 0.001 to force it to recalculate
						$recalculate = (1 / $this->mpdf->shrin_k) + 0.001;  // undo any shrink
					}
				} elseif ($this->mpdf->table_keep_together || (($this->mpdf->table[1][1]['nr'] ?? 0) == 1 && !$this->mpdf->writingHTMLfooter)) {
					if ($tableheight > $fullpage) {
						if (($this->mpdf->shrin_k * $this->tbsqrt($tableheight / $fullpage, $iteration)) <= $this->mpdf->shrink_this_table_to_fit) {
							$recalculate = $this->tbsqrt($tableheight / $fullpage, $iteration);
							$iteration++;
						} elseif ($this->mpdf->tableMinSizePriority) {
							$this->mpdf->table_keep_together = false;
							$recalculate = (1 / $this->mpdf->shrin_k) + 0.001;
						} else {
							if (!$added_page && $this->mpdf->y != $this->mpdf->tMargin) {
								$this->mpdf->AddPage($this->mpdf->CurOrientation);
								$added_page = true;
								$this->mpdf->kwt_moved = true;
								$this->mpdf->tbrot_maxw = $this->mpdf->h - ($this->mpdf->y + $this->mpdf->bMargin + 5) - $this->mpdf->kwt_height;
							}
							$recalculate = $this->tbsqrt($tableheight / $fullpage, $iteration);
							$iteration++;
						}
					} elseif ($tableheight > $remainingpage) {
						// If can fit on remainder of page whilst respecting autosize value..
						if (($this->mpdf->shrin_k * $this->tbsqrt($tableheight / $remainingpage, $iteration)) <= $this->mpdf->shrink_this_table_to_fit) {
							$recalculate = $this->tbsqrt($tableheight / $remainingpage, $iteration);
							$iteration++;
						} else {
							if (!$added_page) {
								// mPDF 6
								if ($this->mpdf->AcceptPageBreak()) {
									$this->mpdf->AddPage($this->mpdf->CurOrientation);
								} elseif ($this->mpdf->ColActive && $tableheight > (($this->mpdf->h - $this->mpdf->bMargin) - $this->mpdf->y0)) {
									$this->mpdf->AddPage($this->mpdf->CurOrientation);
								}
								$added_page = true;
								$this->mpdf->kwt_moved = true;
								$this->mpdf->tbrot_maxw = $this->mpdf->h - ($this->mpdf->y + $this->mpdf->bMargin + 5) - $this->mpdf->kwt_height;
							}

							//$recalculate = $this->tbsqrt($tableheight / $fullpage, $iteration); $iteration++;
							$recalculate = (1 / $this->mpdf->shrin_k) + 0.001;  // undo any shrink
						}
					} else {
						$recalculate = 1;
					}
				} else {
					$recalculate = 1;
				}
			}

			if ($maxfirstrowheight > $remainingpage && !$added_page && !$this->mpdf->table_rotate && !$this->mpdf->ColActive
				&& !$this->mpdf->table_keep_together && !$this->mpdf->writingHTMLheader && !$this->mpdf->writingHTMLfooter) {
				$this->mpdf->AddPage($this->mpdf->CurOrientation);
				$this->mpdf->kwt_moved = true;
			}

			// keep-with-table: if page has advanced, print out buffer now, else done in fn. _Tablewrite()
			if ($this->mpdf->kwt_saved && $this->mpdf->kwt_moved) {
				$this->mpdf->printkwtbuffer();
				$this->mpdf->kwt_moved = false;
				$this->mpdf->kwt_saved = false;
			}

			// Recursively writes all tables starting at top level
			$this->mpdf->_tableWrite($this->mpdf->table[1][1]);

			if ($this->mpdf->table_rotate && $this->mpdf->tablebuffer) {
				$this->mpdf->PageBreakTrigger = $this->mpdf->h - $this->mpdf->bMargin;
				$save_tr = $this->mpdf->table_rotate;
				$save_y = $this->mpdf->y;
				$this->mpdf->table_rotate = 0;
				$this->mpdf->y = $this->mpdf->tbrot_y0;
				$h = $this->mpdf->tbrot_w;
				$this->mpdf->DivLn($h, $this->mpdf->blklvl);

				$this->mpdf->table_rotate = $save_tr;
				$this->mpdf->y = $save_y;

				$this->mpdf->printtablebuffer();
			}
			$this->mpdf->table_rotate = 0;
		}


		$this->mpdf->x = $this->mpdf->lMargin + $this->mpdf->blk[$this->mpdf->blklvl]['outer_left_margin'];

		$this->mpdf->maxPosR = max($this->mpdf->maxPosR, $this->mpdf->x + ($this->mpdf->table[1][1]['w'] ?? 0));

		$this->mpdf->blockjustfinished = true;
		$this->mpdf->lastblockbottommargin = $this->mpdf->table[1][1]['margin']['B'] ?? 0;
		//Reset values

		$page_break_after = '';
		if (isset($this->mpdf->table[1][1]['page_break_after'])) {
			$page_break_after = $this->mpdf->table[1][1]['page_break_after'];
		}

		// Keep-with-table
		$this->mpdf->kwt = false;
		$this->mpdf->kwt_y0 = 0;
		$this->mpdf->kwt_x0 = 0;
		$this->mpdf->kwt_height = 0;
		$this->mpdf->kwt_buffer = [];
		$this->mpdf->kwt_Links = [];
		$this->mpdf->kwt_Annots = [];
		$this->mpdf->kwt_moved = false;
		$this->mpdf->kwt_saved = false;

		$this->mpdf->kwt_Reference = [];
		$this->mpdf->kwt_BMoutlines = [];
		$this->mpdf->kwt_toc = [];

		$this->mpdf->shrin_k = 1;
		$this->mpdf->shrink_this_table_to_fit = 0;

		$this->mpdf->table = []; //array
		$this->mpdf->tableLevel = 0;
		$this->mpdf->tbctr = [];
		$this->mpdf->innermostTableLevel = 0;
		$this->cssManager->tbCSSlvl = 0;
		$this->cssManager->tablecascadeCSS = [];

		$this->mpdf->cell = []; //array

		$this->mpdf->col = -1; //int
		$this->mpdf->row = -1; //int

		$this->mpdf->Reset();

		$this->mpdf->cellPaddingL = 0;
		$this->mpdf->cellPaddingT = 0;
		$this->mpdf->cellPaddingR = 0;
		$this->mpdf->cellPaddingB = 0;
		$this->mpdf->cMarginL = 0;
		$this->mpdf->cMarginT = 0;
		$this->mpdf->cMarginR = 0;
		$this->mpdf->cMarginB = 0;
		$this->mpdf->default_font_size = $this->mpdf->original_default_font_size;
		$this->mpdf->default_font = $this->mpdf->original_default_font;
		$this->mpdf->SetFontSize($this->mpdf->default_font_size, false);
		$this->mpdf->SetFont($this->mpdf->default_font, '', 0, false);
		$this->mpdf->SetLineHeight();

		if (isset($this->mpdf->blk[$this->mpdf->blklvl]['InlineProperties'])) {
			$this->mpdf->restoreInlineProperties($this->mpdf->blk[$this->mpdf->blklvl]['InlineProperties']);
		}

		if ($page_break_after) {
			$save_blklvl = $this->mpdf->blklvl;
			$save_blk = $this->mpdf->blk;
			$save_silp = $this->mpdf->saveInlineProperties();
			$save_ilp = $this->mpdf->InlineProperties;
			$save_bflp = $this->mpdf->InlineBDF;
			$save_bflpc = $this->mpdf->InlineBDFctr; // mPDF 6
			// mPDF 6 pagebreaktype
			$startpage = $this->mpdf->page;
			$pagebreaktype = $this->mpdf->defaultPagebreakType;
			if ($this->mpdf->ColActive) {
				$pagebreaktype = 'cloneall';
			}

			// mPDF 6 pagebreaktype
			$this->mpdf->_preForcedPagebreak($pagebreaktype);

			if ($page_break_after === 'RIGHT') {
				$this->mpdf->AddPage($this->mpdf->CurOrientation, 'NEXT-ODD');
			} elseif ($page_break_after === 'LEFT') {
				$this->mpdf->AddPage($this->mpdf->CurOrientation, 'NEXT-EVEN');
			} else {
				$this->mpdf->AddPage($this->mpdf->CurOrientation);
			}

			// mPDF 6 pagebreaktype
			$this->mpdf->_postForcedPagebreak($pagebreaktype, $startpage, $save_blk, $save_blklvl);

			$this->mpdf->InlineProperties = $save_ilp;
			$this->mpdf->InlineBDF = $save_bflp;
			$this->mpdf->InlineBDFctr = $save_bflpc; // mPDF 6
			$this->mpdf->restoreInlineProperties($save_silp);
		}
	}

	/**
	 * This function determines the shrink factor when resizing tables
	 * val is the table_height / page_height_available
	 * returns a scaling factor used as $shrin_k to resize the table
	 * Overcompensating will be quicker but may unnecessarily shrink table too much
	 * Undercompensating means it will reiterate more times (taking more processing time)
	 */
	private function tbsqrt($val, $iteration = 3)
	{
		// Alters number of iterations until it returns $val itself - Must be > 2
		$k = 4;

		// Probably best guess and most accurate
		if ($iteration === 1) {
			return sqrt($val);
		}

		// Faster than using sqrt (because it won't undercompensate), and gives reasonable results
		// return 1 + (($val - 1) / 2);
		$x = 2 - (($iteration - 2) / ($k - 2));

		if ($x === 0) {
			$ret = $val + 0.00001;
		} elseif ($x < 0) {
			$ret = 1 + ( pow(2, ($iteration - 2 - $k)) / 1000 );
		} else {
			$ret = 1 + (($val - 1) / $x);
		}

		return $ret;
	}

}
