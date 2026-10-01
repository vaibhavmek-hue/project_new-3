<?php
/**
 * SimpleDocx
 * -----------
 * A tiny, dependency-free Word (.docx) document builder.
 *
 * .docx files are just a ZIP archive containing a handful of XML parts.
 * This class writes exactly those parts by hand (no Composer / PHPWord
 * required), which keeps the "Download Report" feature working on a
 * plain shared-hosting PHP setup with the built-in `zip` extension only.
 *
 * Usage:
 *   $doc = new SimpleDocx();
 *   $doc->addTitle('Project Report');
 *   $doc->addHeading('Overview');
 *   $doc->addParagraph('Some text...');
 *   $doc->addKeyValueTable(['Client' => 'Acme Inc', 'Status' => 'Active']);
 *   $doc->addTable(['Name', 'Status'], [['Login page', 'Done']]);
 *   $doc->output('report.docx');   // sends headers + streams the file
 *   // or: $bytes = $doc->build(); file_put_contents('x.docx', $bytes);
 */
class SimpleDocx
{
    /** @var string Accumulated <w:body> XML content */
    private $body = '';

    /** Colors used for simple styling (hex, no '#') */
    const COLOR_PRIMARY = '2F5496';
    const COLOR_MUTED    = '6C757D';
    const COLOR_HEADER_BG = 'DDEBF7';

    // ---------------------------------------------------------------
    // Content builders
    // ---------------------------------------------------------------

    public function addTitle(string $text): void
    {
        $this->body .= '<w:p><w:pPr><w:spacing w:after="120"/><w:jc w:val="center"/></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:sz w:val="44"/><w:color w:val="' . self::COLOR_PRIMARY . '"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addSubtitle(string $text): void
    {
        $this->body .= '<w:p><w:pPr><w:spacing w:after="360"/><w:jc w:val="center"/></w:pPr>'
            . '<w:r><w:rPr><w:i/><w:sz w:val="22"/><w:color w:val="' . self::COLOR_MUTED . '"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addHeading(string $text, int $level = 1): void
    {
        $size = $level === 1 ? '30' : ($level === 2 ? '26' : '22');
        $this->body .= '<w:p><w:pPr><w:keepNext/><w:spacing w:before="280" w:after="140"/>'
            . '<w:pBdr><w:bottom w:val="single" w:sz="6" w:space="4" w:color="' . self::COLOR_PRIMARY . '"/></w:pBdr></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:sz w:val="' . $size . '"/><w:color w:val="' . self::COLOR_PRIMARY . '"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addParagraph(string $text, bool $muted = false): void
    {
        $color = $muted ? self::COLOR_MUTED : '000000';
        $this->body .= '<w:p><w:pPr><w:spacing w:after="160"/></w:pPr>'
            . '<w:r><w:rPr><w:sz w:val="22"/><w:color w:val="' . $color . '"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addBullet(string $text): void
    {
        $this->body .= '<w:p><w:pPr><w:numPr><w:ilvl w:val="0"/><w:numId w:val="1"/></w:numPr>'
            . '<w:spacing w:after="60"/></w:pPr>'
            . '<w:r><w:rPr><w:sz w:val="22"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    public function addSpacer(): void
    {
        $this->body .= '<w:p><w:pPr><w:spacing w:after="60"/></w:pPr></w:p>';
    }

    public function addPageBreak(): void
    {
        $this->body .= '<w:p><w:r><w:br w:type="page"/></w:r></w:p>';
    }

    /** Two-column "label: value" table, e.g. project meta fields. */
    public function addKeyValueTable(array $rows): void
    {
        $tbl = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/>'
            . '<w:tblBorders>'
            . '<w:top w:val="single" w:sz="4" w:color="D9D9D9"/>'
            . '<w:bottom w:val="single" w:sz="4" w:color="D9D9D9"/>'
            . '<w:insideH w:val="single" w:sz="4" w:color="D9D9D9"/>'
            . '</w:tblBorders></w:tblPr>'
            . '<w:tblGrid><w:gridCol w:w="2800"/><w:gridCol w:w="6200"/></w:tblGrid>';

        $total = count($rows);
        $i = 0;
        foreach ($rows as $label => $value) {
            $i++;
            // keepNext (except on the very last row) chains every row to the
            // one below it, so Word treats the whole table as one atomic
            // block: if it doesn't fit, the entire table moves to the next
            // page instead of splitting mid-table.
            $keepNext = $i < $total ? '<w:keepNext/>' : '';
            $tbl .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>'
                . '<w:tc><w:tcPr><w:tcW w:w="2800" w:type="dxa"/><w:shd w:val="clear" w:fill="F2F2F2"/></w:tcPr>'
                . '<w:p><w:pPr>' . $keepNext . '</w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/></w:rPr><w:t xml:space="preserve">' . $this->esc((string)$label) . '</w:t></w:r></w:p></w:tc>'
                . '<w:tc><w:tcPr><w:tcW w:w="6200" w:type="dxa"/></w:tcPr>'
                . '<w:p><w:pPr>' . $keepNext . '</w:pPr><w:r><w:rPr><w:sz w:val="20"/></w:rPr><w:t xml:space="preserve">' . $this->esc((string)$value) . '</w:t></w:r></w:p></w:tc>'
                . '</w:tr>';
        }
        $tbl .= '</w:tbl>';
        $this->body .= $tbl . '<w:p><w:pPr><w:spacing w:after="160"/></w:pPr></w:p>';
    }

    /**
     * Multi-column data table with a shaded header row.
     * @param string[] $headers
     * @param string[][] $rows
     */
    public function addTable(array $headers, array $rows): void
    {
        $colCount = count($headers);
        $colWidth = intdiv(9000, max(1, $colCount));

        $tbl = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/>'
            . '<w:tblBorders>'
            . '<w:top w:val="single" w:sz="4" w:color="D9D9D9"/>'
            . '<w:bottom w:val="single" w:sz="4" w:color="D9D9D9"/>'
            . '<w:left w:val="single" w:sz="4" w:color="D9D9D9"/>'
            . '<w:right w:val="single" w:sz="4" w:color="D9D9D9"/>'
            . '<w:insideH w:val="single" w:sz="4" w:color="D9D9D9"/>'
            . '<w:insideV w:val="single" w:sz="4" w:color="D9D9D9"/>'
            . '</w:tblBorders></w:tblPr><w:tblGrid>';
        for ($i = 0; $i < $colCount; $i++) {
            $tbl .= '<w:gridCol w:w="' . $colWidth . '"/>';
        }
        $tbl .= '</w:tblGrid>';

        // Header row. keepNext binds it to the first data row (or to the
        // "no data" row) so it can never be stranded alone at a page break;
        // cantSplit stops Word from cutting the row's content in half.
        // (No tblHeader here — the header row is not repeated on
        // continuation pages, matching the reference report.)
        $tbl .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>';
        foreach ($headers as $h) {
            $tbl .= '<w:tc><w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/><w:shd w:val="clear" w:fill="' . self::COLOR_HEADER_BG . '"/></w:tcPr>'
                . '<w:p><w:pPr><w:keepNext/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val="20"/><w:color w:val="' . self::COLOR_PRIMARY . '"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $this->esc((string)$h) . '</w:t></w:r></w:p></w:tc>';
        }
        $tbl .= '</w:tr>';

        // Data rows. Every row (other than the last) keeps itself with the
        // row after it via keepNext, and cantSplit keeps a row's own content
        // from being cut across a page. Chained together, this makes Word
        // treat the whole table as a single unit for pagination: if it
        // doesn't fit where it starts, the entire table moves to the next
        // page instead of splitting midway through.
        if (empty($rows)) {
            $tbl .= '<w:tr><w:tc><w:tcPr><w:tcW w:w="9000" w:type="dxa"/><w:gridSpan w:val="' . $colCount . '"/></w:tcPr>'
                . '<w:p><w:pPr><w:jc w:val="center"/></w:pPr><w:r><w:rPr><w:i/><w:sz w:val="20"/><w:color w:val="' . self::COLOR_MUTED . '"/></w:rPr>'
                . '<w:t xml:space="preserve">No data available.</w:t></w:r></w:p></w:tc></w:tr>';
        } else {
            $total = count($rows);
            $i = 0;
            foreach ($rows as $row) {
                $i++;
                $keepNext = $i < $total ? '<w:keepNext/>' : '';
                $tbl .= '<w:tr><w:trPr><w:cantSplit/></w:trPr>';
                foreach ($row as $cell) {
                    $tbl .= '<w:tc><w:tcPr><w:tcW w:w="' . $colWidth . '" w:type="dxa"/></w:tcPr>'
                        . '<w:p><w:pPr>' . $keepNext . '</w:pPr><w:r><w:rPr><w:sz w:val="20"/></w:rPr><w:t xml:space="preserve">' . $this->esc((string)$cell) . '</w:t></w:r></w:p></w:tc>';
                }
                $tbl .= '</w:tr>';
            }
        }
        $tbl .= '</w:tbl>';
        $this->body .= $tbl . '<w:p><w:pPr><w:spacing w:after="160"/></w:pPr></w:p>';
    }

    // ---------------------------------------------------------------
    // Letterhead / business-letter builders
    // ---------------------------------------------------------------

    /**
     * Company letterhead: big company name + tagline on the left,
     * an optional registration number on the right, and a colored
     * rule underneath. Mirrors a printed letterhead like the
     * Ekatta Innovators LLP sample ("DIN No.: AAN-9912" top-right).
     */
    public function addLetterhead(
        string $companyName,
        string $tagline = '',
        string $regLabel = '',
        string $regNo = ''
    ): void {
        $tbl = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders>'
            . '<w:top w:val="none"/><w:left w:val="none"/><w:bottom w:val="none"/><w:right w:val="none"/>'
            . '<w:insideH w:val="none"/><w:insideV w:val="none"/></w:tblBorders></w:tblPr>'
            . '<w:tblGrid><w:gridCol w:w="6300"/><w:gridCol w:w="2700"/></w:tblGrid><w:tr>'
            . '<w:tc><w:tcPr><w:tcW w:w="6300" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>'
            . '<w:p><w:r><w:rPr><w:b/><w:sz w:val="36"/><w:color w:val="' . self::COLOR_PRIMARY . '"/></w:rPr>'
            . '<w:t xml:space="preserve">' . $this->esc($companyName) . '</w:t></w:r></w:p>';

        if ($tagline !== '') {
            $tbl .= '<w:p><w:r><w:rPr><w:i/><w:sz w:val="18"/><w:color w:val="' . self::COLOR_MUTED . '"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $this->esc($tagline) . '</w:t></w:r></w:p>';
        }

        $tbl .= '</w:tc><w:tc><w:tcPr><w:tcW w:w="2700" w:type="dxa"/><w:vAlign w:val="center"/></w:tcPr>'
            . '<w:p><w:pPr><w:jc w:val="right"/></w:pPr>';

        if ($regNo !== '') {
            $tbl .= '<w:r><w:rPr><w:b/><w:sz w:val="18"/></w:rPr>'
                . '<w:t xml:space="preserve">' . $this->esc(($regLabel !== '' ? $regLabel : 'Reg. No.') . ': ' . $regNo) . '</w:t></w:r>';
        }

        $tbl .= '</w:p></w:tc></w:tr></w:tbl>';

        $this->body .= $tbl
            . '<w:p><w:pPr><w:spacing w:before="80" w:after="240"/>'
            . '<w:pBdr><w:bottom w:val="single" w:sz="12" w:space="4" w:color="' . self::COLOR_PRIMARY . '"/></w:pBdr></w:pPr></w:p>';
    }

    /** Single "Label: value" line, e.g. "Date: 20/08/2026" */
    public function addLabelLine(string $label, string $value): void
    {
        $this->body .= '<w:p><w:pPr><w:spacing w:after="160"/></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t xml:space="preserve">' . $this->esc($label) . '</w:t></w:r>'
            . '<w:r><w:rPr><w:sz w:val="22"/></w:rPr><w:t xml:space="preserve">' . $this->esc($value) . '</w:t></w:r>'
            . '</w:p>';
    }

    /** "To," / "From," block with a bold label line and plain lines under it. */
    public function addAddressBlock(string $label, array $lines): void
    {
        $this->body .= '<w:p><w:pPr><w:spacing w:after="20"/></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t xml:space="preserve">' . $this->esc($label) . '</w:t></w:r></w:p>';

        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '') {
                continue;
            }
            $this->body .= '<w:p><w:pPr><w:spacing w:after="20"/></w:pPr>'
                . '<w:r><w:rPr><w:sz w:val="22"/></w:rPr><w:t xml:space="preserve">' . $this->esc($line) . '</w:t></w:r></w:p>';
        }

        $this->body .= '<w:p><w:pPr><w:spacing w:after="200"/></w:pPr></w:p>';
    }

    /** Bold "Subject: ..." line. */
    public function addSubjectLine(string $text): void
    {
        $this->body .= '<w:p><w:pPr><w:spacing w:after="200"/></w:pPr>'
            . '<w:r><w:rPr><w:b/><w:sz w:val="22"/></w:rPr><w:t xml:space="preserve">Subject: ' . $this->esc($text) . '</w:t></w:r></w:p>';
    }

    /** Signature block at the end of the letter; first line is bold. */
    public function addSignature(array $lines): void
    {
        $this->addSpacer();

        foreach ($lines as $i => $line) {
            $line = trim((string)$line);
            if ($line === '') {
                continue;
            }
            $rPr = $i === 0 ? '<w:b/><w:sz w:val="22"/>' : '<w:sz w:val="22"/>';
            $this->body .= '<w:p><w:pPr><w:spacing w:after="20"/></w:pPr>'
                . '<w:r><w:rPr>' . $rPr . '</w:rPr><w:t xml:space="preserve">' . $this->esc($line) . '</w:t></w:r></w:p>';
        }
    }

    // ---------------------------------------------------------------
    // Output
    // ---------------------------------------------------------------

    /** Build the .docx binary and return it as a string. */
    public function build(): string
    {
        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:body>' . $this->body
            . '<w:sectPr>'
            . '<w:pgSz w:w="11906" w:h="16838"/>'
            . '<w:pgMar w:top="1134" w:right="1134" w:bottom="1134" w:left="1134" w:header="708" w:footer="708" w:gutter="0"/>'
            . '</w:sectPr>'
            . '</w:body></w:document>';

        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            . '<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            . '<Override PartName="/word/numbering.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '</Types>';

        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';

        $docRels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering" Target="numbering.xml"/>'
            . '</Relationships>';

        $styles = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:docDefaults><w:rPrDefault><w:rPr>'
            . '<w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/><w:sz w:val="22"/>'
            . '</w:rPr></w:rPrDefault></w:docDefaults>'
            . '<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            . '</w:styles>';

        $numbering = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<w:numbering xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            . '<w:abstractNum w:abstractNumId="0">'
            . '<w:lvl w:ilvl="0"><w:start w:val="1"/><w:numFmt w:val="bullet"/><w:lvlText w:val="&#8226;"/>'
            . '<w:pPr><w:ind w:left="720" w:hanging="360"/></w:pPr>'
            . '<w:rPr><w:rFonts w:ascii="Symbol" w:hAnsi="Symbol" w:hint="default"/></w:rPr></w:lvl>'
            . '</w:abstractNum>'
            . '<w:num w:numId="1"><w:abstractNumId w:val="0"/></w:num>'
            . '</w:numbering>';

        $now = gmdate('Y-m-d\TH:i:s\Z');
        $core = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" '
            . 'xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:title>Project Report</dc:title><dc:creator>Project Dashboard</dc:creator>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created>'
            . '<dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';

        $app = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties">'
            . '<Application>Project Dashboard</Application></Properties>';

        $tmpFile = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive();
        $zip->open($tmpFile, ZipArchive::OVERWRITE);
        $zip->addEmptyDir('_rels');
        $zip->addEmptyDir('word');
        $zip->addEmptyDir('word/_rels');
        $zip->addEmptyDir('docProps');
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $rels);
        $zip->addFromString('word/document.xml', $document);
        $zip->addFromString('word/styles.xml', $styles);
        $zip->addFromString('word/numbering.xml', $numbering);
        $zip->addFromString('word/_rels/document.xml.rels', $docRels);
        $zip->addFromString('docProps/core.xml', $core);
        $zip->addFromString('docProps/app.xml', $app);
        $zip->close();

        $bytes = file_get_contents($tmpFile);
        @unlink($tmpFile);
        return $bytes;
    }

    /** Build and stream the file straight to the browser as a download. */
    public function output(string $filename): void
    {
        $bytes = $this->build();
        if (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Disposition: attachment; filename="' . $this->sanitizeFilename($filename) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        echo $bytes;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9 _\-\.]/', '', $name);
        return $name !== '' ? $name : 'report.docx';
    }

    private function esc(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
