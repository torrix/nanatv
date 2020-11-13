<?php
require('fpdf.php');

date_default_timezone_set('UTC');

$stations = [
    ['channel' => 1, 'station' => 'BBC1 North West', 'rtid' => 98],
    ['channel' => 2, 'station' => 'BBC2 North West', 'rtid' => 109],
    ['channel' => 3, 'station' => 'ITV1 Granada', 'rtid' => 29],
    ['channel' => 4, 'station' => 'Channel 4', 'rtid' => 132],
    ['channel' => 5, 'station' => 'Five', 'rtid' => 134],
    ['channel' => 6, 'station' => 'ITV2', 'rtid' => 185],
    ['channel' => 7, 'station' => 'BBC3', 'rtid' => 45],
    ['channel' => 9, 'station' => 'BBC4', 'rtid' => 47],
    ['channel' => 10, 'station' => 'ITV3', 'rtid' => 1859],
    ['channel' => 11, 'station' => 'Sky3', 'rtid' => 1963],
    ['channel' => 12, 'station' => 'Yesterday', 'rtid' => 801],
    ['channel' => 13, 'station' => 'Channel 4+1', 'rtid' => 2047],
    ['channel' => 14, 'station' => 'More4', 'rtid' => 1959],
    ['channel' => 15, 'station' => 'Film4', 'rtid' => 160],
    ['channel' => 18, 'station' => '4Music', 'rtid' => 1544],
    ['channel' => 19, 'station' => 'Dave', 'rtid' => 2050],
    ['channel' => 20, 'station' => 'Virgin 1', 'rtid' => 2049],
    ['channel' => 21, 'station' => 'TMF: The Music Factory', 'rtid' => 1501],
    ['channel' => 24, 'station' => 'ITV4', 'rtid' => 1961],
    ['channel' => 25, 'station' => 'Dave ja vu', 'rtid' => 2052],
    ['channel' => 28, 'station' => 'E4', 'rtid' => 158],
    ['channel' => 29, 'station' => 'E4 +1', 'rtid' => 1161],
    ['channel' => 30, 'station' => 'Fiver', 'rtid' => 2062],
    ['channel' => 31, 'station' => 'Five USA', 'rtid' => 2008],
    ['channel' => 33, 'station' => 'ITV2 +1', 'rtid' => 1990],
    ['channel' => 35, 'station' => 'Virgin 1 +1', 'rtid' => 2169],
    ['channel' => 70, 'station' => 'CBBC', 'rtid' => 482],
    ['channel' => 71, 'station' => 'CBeebies', 'rtid' => 483],
    ['channel' => 72, 'station' => 'CITV', 'rtid' => 1981],
    ['channel' => 80, 'station' => 'BBC News', 'rtid' => 48],
    ['channel' => 81, 'station' => 'BBC Parliament', 'rtid' => 49],
    ['channel' => 82, 'station' => 'Sky News', 'rtid' => 256],
    ['channel' => 83, 'station' => 'Sky Sports News', 'rtid' => 300],
    ['channel' => 84, 'station' => 'CNN', 'rtid' => 126],
    ['channel' => 88, 'station' => 'Teachers\' TV (digital terrestrial)', 'rtid' => 1956]
];

foreach ($stations as $station) {
    $txt_station = trim($station['station']);

    $data = file('http://xmltv.radiotimes.com/xmltv/' . $station['rtid'] . '.dat');

    array_shift($data);
    array_shift($data);

    $listings = [];
    foreach ($data as $datum) {

        $text = explode('~', $datum);

        $txt_date = $text[19];

        $listings[$txt_date][$txt_station] .= $text[20] . ' ' . $text[0];
        if ($text[2] && $text[2] != $text[0]) {
            $listings[$txt_date][$txt_station] .= ' (' . $text[2] . ')';
        }
        $listings[$txt_date][$txt_station] .= "\r\n";
    }
}

class PDF extends FPDF
{

    function NbLines($w, $txt)
    {
        //Computes the number of lines a MultiCell of width w will take
        $cw =& $this->CurrentFont['cw'];
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s    = str_replace("\r", '', $txt);
        $nb   = strlen($s);
        if ($nb > 0 and $s[$nb - 1] == "\n") {
            $nb--;
        }
        $sep = -1;
        $i   = 0;
        $j   = 0;
        $l   = 0;
        $nl  = 1;
        while ($i < $nb) {
            $c = $s[$i];
            if ($c == "\n") {
                $i++;
                $sep = -1;
                $j   = $i;
                $l   = 0;
                $nl++;
                continue;
            }
            if ($c == ' ') {
                $sep = $i;
            }
            $l += $cw[$c];
            if ($l > $wmax) {
                if ($sep == -1) {
                    if ($i == $j) {
                        $i++;
                    }
                } else {
                    $i = $sep + 1;
                }
                $sep = -1;
                $j   = $i;
                $l   = 0;
                $nl++;
            } else {
                $i++;
            }
        }
        return $nl;
    }

    function rectCell($w, $h, $txt)
    {
        $this->Rect($this->getX(), $this->getY(), $w, $h, 'F');
        $font_size = $this->FontSizePt;
        $this->SetFontSize(100);
        while ($this->NbLines($w, $txt) > $h / $this->FontSize) {
            $this->SetFontSize($this->FontSizePt - 0.1);
        }
        # TODO: EXPLODE FIRST AND ZEBRA COLOUR LINES - ALSO ADD DIVIDER LINE
        $this->Multicell($w, $this->FontSize, $txt, 0, 'L');
        $this->SetFontSize($font_size);
    }

}

$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->SetDisplayMode('fullpage');
$pdf->SetMargins(0, 0, 0);
$pdf->SetAutoPageBreak(false);

$width  = 104.7;
$height = 272.3;

foreach ($listings as $kdate => $date) {
    if (preg_match('/^\s*(\d\d?)[^\w](\d\d?)[^\w](\d{1,4}\s*$)/', $kdate, $match)) {
        $kdate = $match[2] . '/' . $match[1] . '/' . $match[3];
    }
    if (date('l', strtotime(trim($kdate))) != 'Saturday') {
        continue;
    }
    foreach ($date as $kchannel => $channels) {
        if (($i % 2) == 0) {
            $pdf->AddPage();
            $pdf->setLeftMargin(0);
            $pdf->SetX(0);
            $pdf->SetY(0);

            # DATE
            $pdf->SetFont('Arial', 'B', 24);
            $pdf->SetFillColor(255, 0, 0);
            $pdf->SetTextColor(255);
            $pdf->MultiCell(0, 12, date('l jS F', strtotime(trim($kdate))), 0, 'L', true);
            $pdf->SetX(0);
            $pdf->SetY(0);
            $pdf->MultiCell(0, 12, trim('Page ' . $pdf->PageNo()), 0, 'R', false);
            $top = $pdf->getY();

            # CHANNEL
            $pdf->SetFont('Arial', 'B', 24);
            $pdf->SetFillColor(0);
            $pdf->SetTextColor(255);
            $pdf->MultiCell(0, 12, $kchannel, 0, 'L', true);
        } else {
            $pdf->setY($top);
            $pdf->setLeftMargin($width);
            # CHANNEL
            $pdf->SetFont('Arial', 'B', 24);
            $pdf->SetFillColor(0);
            $pdf->SetTextColor(255);
            $pdf->MultiCell(0, 12, $kchannel, 0, 'R', false);
        }
        $pdf->SetTextColor(0);
        $pdf->SetFont('Arial', '', 8);
        $pdf->SetFillColor(255);
        $pdf->rectCell($width, $height, $channels);
        $i++;
    }
    $i = 0;
}
$pdf->Output();
