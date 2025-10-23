<?php
/*******************************************************************************
* FPDF - Minimal Version for Turkish Support                                   *
*******************************************************************************/

if(!class_exists('FPDF')) {
class FPDF {
    protected $page;
    protected $n;
    protected $offsets;
    protected $buffer;
    protected $pages;
    protected $state;
    protected $compress;
    protected $k;
    protected $DefOrientation;
    protected $CurOrientation;
    protected $StdPageSizes;
    protected $DefPageSize;
    protected $CurPageSize;
    protected $CurRotation;
    protected $PageInfo;
    protected $wPt, $hPt;
    protected $w, $h;
    protected $lMargin;
    protected $tMargin;
    protected $rMargin;
    protected $bMargin;
    protected $cMargin;
    protected $x, $y;
    protected $lasth;
    protected $LineWidth;
    protected $fontpath;
    protected $CoreFonts;
    protected $fonts;
    protected $FontFiles;
    protected $encodings;
    protected $cmaps;
    protected $FontFamily;
    protected $FontStyle;
    protected $underline;
    protected $CurrentFont;
    protected $FontSizePt;
    protected $FontSize;
    protected $DrawColor;
    protected $FillColor;
    protected $TextColor;
    protected $ColorFlag;
    protected $WithAlpha;
    protected $ws;
    protected $images;
    protected $PageLinks;
    protected $links;
    protected $AutoPageBreak;
    protected $PageBreakTrigger;
    protected $InHeader;
    protected $InFooter;
    protected $AliasNbPages;
    protected $ZoomMode;
    protected $LayoutMode;
    protected $metadata;
    protected $PDFVersion;

    function __construct($orientation='P', $unit='mm', $size='A4') {
        $this->page = 0;
        $this->n = 2;
        $this->buffer = '';
        $this->pages = array();
        $this->PageInfo = array();
        $this->fonts = array();
        $this->FontFiles = array();
        $this->encodings = array();
        $this->cmaps = array();
        $this->images = array();
        $this->links = array();
        $this->InHeader = false;
        $this->InFooter = false;
        $this->lasth = 0;
        $this->FontFamily = '';
        $this->FontStyle = '';
        $this->FontSizePt = 12;
        $this->underline = false;
        $this->DrawColor = '0 G';
        $this->FillColor = '0 g';
        $this->TextColor = '0 g';
        $this->ColorFlag = false;
        $this->WithAlpha = false;
        $this->ws = 0;
        $this->DefOrientation = strtoupper($orientation) == 'L' ? 'L' : 'P';
        $this->CurOrientation = $this->DefOrientation;
        $this->StdPageSizes = array('a3'=>array(841.89,1190.55), 'a4'=>array(595.28,841.89), 'a5'=>array(420.94,595.28),
            'letter'=>array(612,792), 'legal'=>array(612,1008));
        
        // Önce k değerini tanımla
        if($unit=='pt')
            $this->k = 1;
        elseif($unit=='mm')
            $this->k = 72/25.4;
        elseif($unit=='cm')
            $this->k = 72/2.54;
        elseif($unit=='in')
            $this->k = 72;
        else
            $this->Error('Incorrect unit: '.$unit);
        
        // Sonra size'ı hesapla
        $size = $this->_getpagesize($size);
        $this->DefPageSize = $size;
        $this->CurPageSize = $size;
        $this->CurRotation = 0;
        $this->PageInfo = array();
        $this->lMargin = 28.35/$this->k;
        $this->tMargin = 28.35/$this->k;
        $this->rMargin = 28.35/$this->k;
        $this->bMargin = 28.35/$this->k;
        $this->cMargin = $this->lMargin/10;
        $this->LineWidth = .567/$this->k;
        $this->AutoPageBreak = true;
        $this->PageBreakTrigger = ($size[1]-2*28.35)/$this->k;
        $this->state = 0;
        $this->compress = true;
        $this->PDFVersion = '1.3';
    }

    function SetMargins($left, $top, $right=null) {
        $this->lMargin = $left;
        $this->tMargin = $top;
        if($right===null)
            $right = $left;
        $this->rMargin = $right;
    }

    function SetLeftMargin($margin) {
        $this->lMargin = $margin;
        if($this->page>0 && $this->x<$margin)
            $this->x = $margin;
    }

    function SetTopMargin($margin) {
        $this->tMargin = $margin;
    }

    function SetRightMargin($margin) {
        $this->rMargin = $margin;
    }

    function SetAutoPageBreak($auto, $margin=0) {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h-$margin;
    }

    function AddPage($orientation='', $size='', $rotation=0) {
        if($this->state==3)
            $this->Error('The document is closed');
        $family = $this->FontFamily;
        $style = $this->FontStyle.($this->underline ? 'U' : '');
        $fontsize = $this->FontSizePt;
        $lw = $this->LineWidth;
        $dc = $this->DrawColor;
        $fc = $this->FillColor;
        $tc = $this->TextColor;
        $cf = $this->ColorFlag;
        if($this->page>0) {
            $this->pages[$this->page] = $this->buffer;
            $this->buffer = '';
        }
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        if($orientation=='')
            $orientation = $this->DefOrientation;
        else
            $orientation = strtoupper($orientation[0]);
        if($size=='')
            $size = $this->DefPageSize;
        else
            $size = $this->_getpagesize($size);
        if($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) {
            if($orientation=='P') {
                $this->w = $size[0];
                $this->h = $size[1];
            } else {
                $this->w = $size[1];
                $this->h = $size[0];
            }
            $this->wPt = $this->w*$this->k;
            $this->hPt = $this->h*$this->k;
            $this->PageBreakTrigger = $this->h-$this->bMargin;
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;
        }
        if($orientation!=$this->DefOrientation || $size[0]!=$this->DefPageSize[0] || $size[1]!=$this->DefPageSize[1])
            $this->PageInfo[$this->page]['size'] = array($this->wPt, $this->hPt);
        if($rotation!=0) {
            if($rotation%90!=0)
                $this->Error('Incorrect rotation value: '.$rotation);
            $this->CurRotation = $rotation;
            $this->PageInfo[$this->page]['rotation'] = $rotation;
        }
        if($family)
            $this->SetFont($family, $style, $fontsize);
        $this->LineWidth = $lw;
        $this->DrawColor = $dc;
        if($dc!='0 G')
            $this->_out($dc);
        $this->FillColor = $fc;
        if($fc!='0 g')
            $this->_out($fc);
        $this->TextColor = $tc;
        $this->ColorFlag = $cf;
    }

    function SetFont($family, $style='', $size=0) {
        if($family=='')
            $family = $this->FontFamily;
        else
            $family = strtolower($family);
        $style = strtoupper($style);
        if(strpos($style,'U')!==false) {
            $this->underline = true;
            $style = str_replace('U','',$style);
        } else
            $this->underline = false;
        if($style=='IB')
            $style = 'BI';
        if($size==0)
            $size = $this->FontSizePt;
        if($this->FontFamily==$family && $this->FontStyle==$style && $this->FontSizePt==$size)
            return;
        $fontkey = $family.$style;
        if(!isset($this->fonts[$fontkey])) {
            if($family=='arial')
                $family = 'helvetica';
            if(in_array($family, array('courier','helvetica','times','symbol','zapfdingbats'))) {
                $this->fonts[$fontkey] = array('i'=>$this->n+1, 'type'=>'core', 'name'=>$this->_getcorefontname($family.$style));
                $this->n++;
            } else
                $this->Error('Undefined font: '.$family.' '.$style);
        }
        $this->FontFamily = $family;
        $this->FontStyle = $style;
        $this->FontSizePt = $size;
        $this->FontSize = $size/$this->k;
        $this->CurrentFont = &$this->fonts[$fontkey];
        
        // Initialize font metrics if not set
        if(!isset($this->CurrentFont['cw'])) {
            $cw = array();
            for($i=0; $i<=255; $i++)
                $cw[chr($i)] = 600;
            $this->CurrentFont['cw'] = $cw;
            $this->CurrentFont['up'] = -100;
            $this->CurrentFont['ut'] = 50;
        }
        
        if($this->page>0)
            $this->_out(sprintf('BT /F%d %.2F Tf ET', $this->CurrentFont['i'], $this->FontSizePt));
    }

    function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link='') {
        $k = $this->k;
        if($this->y+$h>$this->PageBreakTrigger && !$this->InHeader && !$this->InFooter && $this->AcceptPageBreak()) {
            $x = $this->x;
            $ws = $this->ws;
            if($ws>0) {
                $this->ws = 0;
                $this->_out('0 Tw');
            }
            $this->AddPage($this->CurOrientation, $this->CurPageSize, $this->CurRotation);
            $this->x = $x;
            if($ws>0) {
                $this->ws = $ws;
                $this->_out(sprintf('%.3F Tw', $ws*$k));
            }
        }
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        $s = '';
        if($fill || $border==1) {
            if($fill)
                $op = ($border==1) ? 'B' : 'f';
            else
                $op = 'S';
            $s = sprintf('%.2F %.2F %.2F %.2F re %s ', $this->x*$k, ($this->h-$this->y)*$k, $w*$k, -$h*$k, $op);
        }
        if(is_string($border)) {
            $x = $this->x;
            $y = $this->y;
            if(strpos($border,'L')!==false)
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, $x*$k, ($this->h-($y+$h))*$k);
            if(strpos($border,'T')!==false)
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-$y)*$k);
            if(strpos($border,'R')!==false)
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', ($x+$w)*$k, ($this->h-$y)*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
            if(strpos($border,'B')!==false)
                $s .= sprintf('%.2F %.2F m %.2F %.2F l S ', $x*$k, ($this->h-($y+$h))*$k, ($x+$w)*$k, ($this->h-($y+$h))*$k);
        }
        if($txt!=='') {
            if(!isset($this->CurrentFont))
                $this->Error('No font has been set');
            if($align=='R')
                $dx = $w-$this->cMargin-$this->GetStringWidth($txt);
            elseif($align=='C')
                $dx = ($w-$this->GetStringWidth($txt))/2;
            else
                $dx = $this->cMargin;
            if($this->ColorFlag)
                $s .= 'q '.$this->TextColor.' ';
            $s .= sprintf('BT %.2F %.2F Td (%s) Tj ET', ($this->x+$dx)*$k, ($this->h-($this->y+.5*$h+.3*$this->FontSize))*$k, $this->_escape($txt));
            if($this->underline)
                $s .= ' '.$this->_dounderline($this->x+$dx, $this->y+.5*$h+.3*$this->FontSize, $txt);
            if($this->ColorFlag)
                $s .= ' Q';
            if($link)
                $this->Link($this->x+$dx, $this->y+.5*$h-.5*$this->FontSize, $this->GetStringWidth($txt), $this->FontSize, $link);
        }
        if($s)
            $this->_out($s);
        $this->lasth = $h;
        if($ln>0) {
            $this->y += $h;
            if($ln==1)
                $this->x = $this->lMargin;
        } else
            $this->x += $w;
    }

    function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false) {
        if(!isset($this->CurrentFont))
            $this->Error('No font has been set');
        $cw = &$this->CurrentFont['cw'];
        if($w==0)
            $w = $this->w-$this->rMargin-$this->x;
        
        // Basit yaklaşım: Metni satırlara böl ve her satırı Cell ile yaz
        $lines = explode("\n", $txt);
        foreach($lines as $line) {
            $this->Cell($w, $h, $line, $border, 1, $align, $fill);
        }
    }

    function Ln($h=null) {
        $this->x = $this->lMargin;
        if($h===null)
            $this->y += $this->lasth;
        else
            $this->y += $h;
    }

    function GetStringWidth($s) {
        if(!isset($this->CurrentFont))
            $this->Error('No font has been set');
        $w = 0;
        $l = strlen($s);
        $cw = &$this->CurrentFont['cw'];
        for($i=0; $i<$l; $i++)
            $w += $cw[$s[$i]];
        return $w*$this->FontSize/1000;
    }

    function GetY() {
        return $this->y;
    }

    function GetX() {
        return $this->x;
    }

    function SetY($y, $resetX=true) {
        if($resetX)
            $this->x = $this->lMargin;
        $this->y = $y;
    }

    function SetX($x) {
        $this->x = $x;
    }

    function Rect($x, $y, $w, $h, $style='') {
        if($style=='F')
            $op = 'f';
        elseif($style=='FD' || $style=='DF')
            $op = 'B';
        else
            $op = 'S';
        $this->_out(sprintf('%.2F %.2F %.2F %.2F re %s', $x*$this->k, ($this->h-$y)*$this->k, $w*$this->k, -$h*$this->k, $op));
    }

    function SetLineWidth($width) {
        $this->LineWidth = $width;
        if($this->page>0)
            $this->_out(sprintf('%.2F w', $width*$this->k));
    }

    function SetDrawColor($r, $g=null, $b=null) {
        if(($r==0 && $g==0 && $b==0) || $g===null)
            $this->DrawColor = sprintf('%.3F G', $r/255);
        else
            $this->DrawColor = sprintf('%.3F %.3F %.3F RG', $r/255, $g/255, $b/255);
        if($this->page>0)
            $this->_out($this->DrawColor);
    }

    function SetFillColor($r, $g=null, $b=null) {
        if(($r==0 && $g==0 && $b==0) || $g===null)
            $this->FillColor = sprintf('%.3F g', $r/255);
        else
            $this->FillColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor!=$this->TextColor);
        if($this->page>0)
            $this->_out($this->FillColor);
    }

    function SetTextColor($r, $g=null, $b=null) {
        if(($r==0 && $g==0 && $b==0) || $g===null)
            $this->TextColor = sprintf('%.3F g', $r/255);
        else
            $this->TextColor = sprintf('%.3F %.3F %.3F rg', $r/255, $g/255, $b/255);
        $this->ColorFlag = ($this->FillColor!=$this->TextColor);
    }

    function Output($dest='', $name='', $isUTF8=false) {
        if($this->state<3)
            $this->Close();
        if($dest=='') {
            if($name=='') {
                $name = 'doc.pdf';
                $dest = 'I';
            } else
                $dest = 'F';
        }
        if($isUTF8)
            $name = $this->_UTF8toUTF16($name);
        switch(strtoupper($dest)) {
            case 'I':
                $this->_checkoutput();
                if(PHP_SAPI!='cli') {
                    header('Content-Type: application/pdf');
                    header('Content-Disposition: inline; filename="'.$name.'"');
                    header('Cache-Control: private, max-age=0, must-revalidate');
                    header('Pragma: public');
                }
                echo $this->buffer;
                break;
            case 'D':
                $this->_checkoutput();
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="'.$name.'"');
                header('Cache-Control: private, max-age=0, must-revalidate');
                header('Pragma: public');
                echo $this->buffer;
                break;
            case 'F':
                if(!file_put_contents($name, $this->buffer))
                    $this->Error('Unable to create output file: '.$name);
                break;
            case 'S':
                return $this->buffer;
            default:
                $this->Error('Incorrect output destination: '.$dest);
        }
        return '';
    }

    protected function _getpagesize($size) {
        if(is_string($size)) {
            $size = strtolower($size);
            if(!isset($this->StdPageSizes[$size]))
                $this->Error('Unknown page size: '.$size);
            $a = $this->StdPageSizes[$size];
            return array($a[0]/$this->k, $a[1]/$this->k);
        } else {
            if($size[0]>$size[1])
                return array($size[1], $size[0]);
            else
                return $size;
        }
    }

    protected function _beginpage($orientation, $size, $rotation) {
        $this->page++;
        $this->pages[$this->page] = '';
        $this->state = 2;
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontFamily = '';
        if(!$orientation)
            $orientation = $this->DefOrientation;
        else {
            $orientation = strtoupper($orientation[0]);
            if($orientation!=$this->DefOrientation)
                $this->OrientationChanges[$this->page] = true;
        }
        if(!$size)
            $size = $this->DefPageSize;
        else
            $size = $this->_getpagesize($size);
        if($orientation!=$this->CurOrientation || $size[0]!=$this->CurPageSize[0] || $size[1]!=$this->CurPageSize[1]) {
            if($orientation=='P') {
                $this->w = $size[0];
                $this->h = $size[1];
            } else {
                $this->w = $size[1];
                $this->h = $size[0];
            }
            $this->wPt = $this->w*$this->k;
            $this->hPt = $this->h*$this->k;
            $this->PageBreakTrigger = $this->h-$this->bMargin;
            $this->CurOrientation = $orientation;
            $this->CurPageSize = $size;
        }
        if($orientation!=$this->DefOrientation || $size[0]!=$this->DefPageSize[0] || $size[1]!=$this->DefPageSize[1])
            $this->PageInfo[$this->page]['size'] = array($this->wPt, $this->hPt);
        if($rotation!=0) {
            if($rotation%90!=0)
                $this->Error('Incorrect rotation value: '.$rotation);
            $this->CurRotation = $rotation;
            $this->PageInfo[$this->page]['rotation'] = $rotation;
        }
    }

    protected function _endpage() {
        $this->state = 1;
    }

    protected function _escape($s) {
        $s = str_replace('\\', '\\\\', $s);
        $s = str_replace('(', '\\(', $s);
        $s = str_replace(')', '\\)', $s);
        $s = str_replace("\r", '\\r', $s);
        return $s;
    }

    protected function _dounderline($x, $y, $txt) {
        $up = $this->CurrentFont['up'];
        $ut = $this->CurrentFont['ut'];
        $w = $this->GetStringWidth($txt)+$this->ws*substr_count($txt,' ');
        return sprintf('%.2F %.2F %.2F %.2F re f', $x*$this->k, ($this->h-($y-$up/1000*$this->FontSize))*$this->k, $w*$this->k, -$ut/1000*$this->FontSizePt);
    }

    protected function _getcorefontname($family) {
        static $table = array(
            'courier'=>'Courier', 'courierB'=>'Courier-Bold', 'courierI'=>'Courier-Oblique', 'courierBI'=>'Courier-BoldOblique',
            'helvetica'=>'Helvetica', 'helveticaB'=>'Helvetica-Bold', 'helveticaI'=>'Helvetica-Oblique', 'helveticaBI'=>'Helvetica-BoldOblique',
            'times'=>'Times-Roman', 'timesB'=>'Times-Bold', 'timesI'=>'Times-Italic', 'timesBI'=>'Times-BoldItalic',
            'symbol'=>'Symbol', 'zapfdingbats'=>'ZapfDingbats'
        );
        return $table[$family];
    }

    protected function _checkoutput() {
        // Output kontrolünü basitleştir
        if(ob_get_length()) {
            ob_clean();
        }
    }

    protected function _putresources() {
        $this->_putfonts();
        $this->_putimages();
        $this->offsets = array();
        $this->offsets[2] = strlen($this->buffer);
        $this->_put('2 0 obj');
        $this->_put('<<');
        $this->_putpages();
        $this->_put('>>');
        $this->_put('endobj');
    }

    protected function _putfonts() {
        foreach($this->fonts as $font) {
            if(isset($font['type']) && $font['type']=='core') {
                $this->n++;
                $this->offsets[$this->n] = strlen($this->buffer);
                $this->_put($this->n.' 0 obj');
                $this->_put('<</Type /Font');
                $this->_put('/BaseFont /'.$font['name']);
                $this->_put('/Subtype /Type1');
                if($font['name']!='Symbol' && $font['name']!='ZapfDingbats')
                    $this->_put('/Encoding /WinAnsiEncoding');
                $this->_put('>>');
                $this->_put('endobj');
            }
        }
    }

    protected function _putimages() {
        // Minimal implementation
    }

    protected function _putpages() {
        $nb = $this->page;
        $this->_put('/Type /Pages');
        $kids = '/Kids [';
        for($i=1; $i<=$nb; $i++)
            $kids .= (3+2*($i-1)).' 0 R ';
        $this->_put($kids.']');
        $this->_put('/Count '.$nb);
        if($this->DefOrientation=='P') {
            $w = $this->DefPageSize[0];
            $h = $this->DefPageSize[1];
        } else {
            $w = $this->DefPageSize[1];
            $h = $this->DefPageSize[0];
        }
        $this->_put(sprintf('/MediaBox [0 0 %.2F %.2F]', $w*$this->k, $h*$this->k));
    }

    protected function _putpage($n) {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_put($this->n.' 0 obj');
        $this->_put('<</Type /Page');
        $this->_put('/Parent 2 0 R');
        if(isset($this->PageInfo[$n]['size']))
            $this->_put(sprintf('/MediaBox [0 0 %.2F %.2F]', $this->PageInfo[$n]['size'][0], $this->PageInfo[$n]['size'][1]));
        if(isset($this->PageInfo[$n]['rotation']))
            $this->_put('/Rotate '.$this->PageInfo[$n]['rotation']);
        $this->_put('/Resources <<');
        $this->_put('/ProcSet [/PDF /Text]');
        $this->_put('/Font <<');
        foreach($this->fonts as $font)
            $this->_put('/F'.$font['i'].' '.$font['i'].' 0 R');
        $this->_put('>>');
        $this->_put('>>');
        $this->_put('/Contents '.($this->n+1).' 0 R>>');
        $this->_put('endobj');
        $this->n++;
        $p = $this->pages[$n];
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_put($this->n.' 0 obj');
        $this->_put('<</Length '.strlen($p).'>>');
        $this->_putstream($p);
        $this->_put('endobj');
    }

    protected function _putstream($s) {
        $this->_put('stream');
        $this->_put($s);
        $this->_put('endstream');
    }

    protected function _out($s) {
        if($this->state==2)
            $this->pages[$this->page] .= $s."\n";
        else
            $this->buffer .= $s."\n";
    }

    protected function _put($s) {
        $this->buffer .= $s."\n";
    }

    function Error($msg) {
        throw new Exception('FPDF error: '.$msg);
    }

    function Close() {
        if($this->state==3)
            return;
        if($this->page==0)
            $this->AddPage();
        $this->_endpage();
        $this->state = 3;
        $this->_beginDoc();
        $this->_putresources();
        for($n=1; $n<=$this->page; $n++)
            $this->_putpage($n);
        $this->_putinfo();
        $this->_putcatalog();
        $o = strlen($this->buffer);
        $this->_put('xref');
        $this->_put('0 '.($this->n+1));
        $this->_put('0000000000 65535 f ');
        for($i=1; $i<=$this->n; $i++)
            $this->_put(sprintf('%010d 00000 n ', isset($this->offsets[$i]) ? $this->offsets[$i] : 0));
        $this->_put('trailer');
        $this->_put('<<');
        $this->_put('/Size '.($this->n+1));
        $this->_put('/Root 1 0 R');
        $this->_put('/Info '.($this->n).' 0 R');
        $this->_put('>>');
        $this->_put('startxref');
        $this->_put($o);
        $this->_put('%%EOF');
    }

    protected function _beginDoc() {
        $this->_put('%PDF-'.$this->PDFVersion);
    }

    protected function _putinfo() {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_put($this->n.' 0 obj');
        $this->_put('<<');
        $this->_put('/Producer (FPDF)');
        $this->_put('/CreationDate (D:'.@date('YmdHis').')');
        $this->_put('>>');
        $this->_put('endobj');
    }

    protected function _putcatalog() {
        $this->n++;
        $this->offsets[$this->n] = strlen($this->buffer);
        $this->_put('1 0 obj');
        $this->_put('<<');
        $this->_put('/Type /Catalog');
        $this->_put('/Pages 2 0 R');
        $this->_put('>>');
        $this->_put('endobj');
    }

    function AcceptPageBreak() {
        return $this->AutoPageBreak;
    }

    function Link($x, $y, $w, $h, $link) {
        // Minimal implementation
    }
}
}

