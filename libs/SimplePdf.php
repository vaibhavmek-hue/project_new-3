<?php
/**
 * SimplePdf
 * ----------
 * A tiny, dependency-free PDF document builder.
 *
 * Writes raw PDF 1.4 syntax by hand (no Composer / TCPDF / dompdf
 * required), the same way SimpleDocx hand-writes .docx XML. This
 * keeps the "Download Report" feature working on a plain
 * shared-hosting PHP setup with no extra extensions or libraries.
 *
 * The public method names intentionally match SimpleDocx so
 * download_report.php only needs to swap which class it builds.
 *
 * Usage:
 *   $doc = new SimplePdf();
 *   $doc->addHeading('Overview');
 *   $doc->addParagraph('Some text...');
 *   $doc->addKeyValueTable(['Client' => 'Acme Inc', 'Status' => 'Active']);
 *   $doc->addTable(['Name', 'Status'], [['Login page', 'Done']]);
 *   $doc->output('report.pdf');   // sends headers + streams the file inline
 *   // or: $bytes = $doc->build(); file_put_contents('x.pdf', $bytes);
 */
class SimplePdf
{
    const PAGE_W = 595.28; // A4 width in points
    const PAGE_H = 841.89; // A4 height in points
    const MARGIN = 50;

    const COLOR_PRIMARY   = [0.184, 0.329, 0.588]; // #2F5496
    const COLOR_MUTED     = [0.424, 0.459, 0.490]; // #6C757D
    const COLOR_HEADER_BG = [0.867, 0.922, 0.969]; // #DDEBF7
    const COLOR_BORDER    = [0.851, 0.851, 0.851]; // #D9D9D9
    const COLOR_LABEL_BG  = [0.949, 0.949, 0.949]; // #F2F2F2
    const COLOR_BLACK     = [0, 0, 0];

    /** Standard Helvetica glyph widths (per 1000 em units), ASCII 32-126. */
    private static $WIDTHS = [
        32=>278,33=>278,34=>355,35=>556,36=>556,37=>889,38=>667,39=>191,40=>333,41=>333,
        42=>389,43=>584,44=>278,45=>333,46=>278,47=>278,
        48=>556,49=>556,50=>556,51=>556,52=>556,53=>556,54=>556,55=>556,56=>556,57=>556,
        58=>278,59=>278,60=>584,61=>584,62=>584,63=>556,64=>1015,
        65=>667,66=>667,67=>722,68=>722,69=>667,70=>611,71=>778,72=>722,73=>278,74=>500,
        75=>667,76=>556,77=>833,78=>722,79=>778,80=>667,81=>778,82=>722,83=>667,84=>611,
        85=>722,86=>667,87=>944,88=>667,89=>667,90=>611,
        91=>278,92=>278,93=>278,94=>469,95=>556,96=>333,
        97=>556,98=>556,99=>500,100=>556,101=>556,102=>278,103=>556,104=>556,105=>222,106=>222,
        107=>500,108=>222,109=>833,110=>556,111=>556,112=>556,113=>556,114=>333,115=>500,116=>278,
        117=>556,118=>500,119=>722,120=>500,121=>500,122=>500,
        123=>334,124=>260,125=>334,126=>584,
    ];

    /** @var string[] Finished page content streams */
    private $pages = [];

    /** @var string Content stream currently being written to */
    private $curStream = '';

    /** @var float Current vertical cursor (PDF y-coordinate, distance from bottom) */
    private $y;

    /** @var float Usable content width between the left/right margins */
    private $contentWidth;

    /** @var array[] Finished pages' fillable-field definitions, parallel to $pages */
    private $pageFields = [];

    /** @var array[] Fillable-field definitions queued on the page being built */
    private $curPageFields = [];

    /** @var int Running counter used only to keep internal warnings away from name clashes */
    private $fieldCounter = 0;

    /** @var array[] Registered images: id => ['data' => raw JPEG bytes, 'w' => px width, 'h' => px height] */
    private $images = [];

    /** @var int[] Image ids referenced on the page being built */
    private $curPageImageIds = [];

    /** @var int[][] Finished pages' referenced image ids, parallel to $pages */
    private $pageImageIds = [];

    /** @var float Bottom-of-page boundary used by ensureSpace(); resets to self::MARGIN on each new page */
    private $bottomMargin;

    /** @var string Text stamped at the bottom of pages (empty = no footer) */
    private $footerText = '';

    /** @var bool When true (default), the very first page is not stamped with $footerText */
    private $footerSkipFirstPage = true;

    public function __construct()
    {
        $this->contentWidth = self::PAGE_W - 2 * self::MARGIN;
        $this->y = self::PAGE_H - self::MARGIN;
        $this->bottomMargin = self::MARGIN;
    }

    // ---------------------------------------------------------------
    // Low-level layout helpers
    // ---------------------------------------------------------------

    private function newPage(): void
    {
        $this->stampFooterIfNeeded();
        $this->pages[] = $this->curStream;
        $this->pageFields[] = $this->curPageFields;
        $this->pageImageIds[] = $this->curPageImageIds;
        $this->curStream = '';
        $this->curPageFields = [];
        $this->curPageImageIds = [];
        $this->y = self::PAGE_H - self::MARGIN;
        $this->bottomMargin = self::MARGIN;
    }

    private function ensureSpace(float $height): void
    {
        if ($this->y - $height < $this->bottomMargin) {
            $this->newPage();
        }
    }

    /** Stamp $footerText (if set) at the bottom of the page currently being built. */
    private function stampFooterIfNeeded(): void
    {
        if ($this->footerText === '') {
            return;
        }

        $pageIndex = count($this->pages); // index this page will have once pushed
        if ($pageIndex === 0 && $this->footerSkipFirstPage) {
            return;
        }

        $size = 8;
        $w = $this->stringWidth($this->footerText, $size, false);
        $x = (self::PAGE_W - $w) / 2;
        $this->emit($this->textOp($x, 25, $this->footerText, 'F1', $size, self::COLOR_MUTED));
    }

    private function emit(string $s): void
    {
        $this->curStream .= $s;
    }

    private function stringWidth(string $text, float $size, bool $bold = false): float
    {
        $conv = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
        if ($conv === false) {
            $conv = $text;
        }
        $w = 0;
        $len = strlen($conv);
        for ($i = 0; $i < $len; $i++) {
            $code = ord($conv[$i]);
            $w += self::$WIDTHS[$code] ?? 556;
        }
        $width = $w / 1000 * $size;
        return $bold ? $width * 1.06 : $width;
    }

    /** Word-wrap text to fit within $maxWidth points at the given size. */
    private function wrapText(string $text, float $maxWidth, float $size, bool $bold = false): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text));
        if ($text === '') {
            return [''];
        }

        $words = explode(' ', $text);
        $lines = [];
        $current = '';

        foreach ($words as $word) {
            // Hard-split any single word wider than the column itself.
            if ($this->stringWidth($word, $size, $bold) > $maxWidth) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }
                $chunk = '';
                foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) as $ch) {
                    $test = $chunk . $ch;
                    if ($chunk !== '' && $this->stringWidth($test, $size, $bold) > $maxWidth) {
                        $lines[] = $chunk;
                        $chunk = $ch;
                    } else {
                        $chunk = $test;
                    }
                }
                $current = $chunk;
                continue;
            }

            $test = $current === '' ? $word : $current . ' ' . $word;
            if ($this->stringWidth($test, $size, $bold) <= $maxWidth) {
                $current = $test;
            } else {
                $lines[] = $current;
                $current = $word;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines ?: [''];
    }

    private function esc(string $text): string
    {
        $conv = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
        if ($conv === false) {
            $conv = $text;
        }
        $conv = str_replace(["\\", "(", ")"], ["\\\\", "\\(", "\\)"], $conv);
        return preg_replace('/[\r\n]+/', ' ', $conv);
    }

    private function textOp(float $x, float $y, string $text, string $font, float $size, array $rgb): string
    {
        return sprintf(
            "%.3f %.3f %.3f rg BT /%s %.2f Tf %.2f %.2f Td (%s) Tj ET\n",
            $rgb[0], $rgb[1], $rgb[2], $font, $size, $x, $y, $this->esc($text)
        );
    }

    private function rectOp(float $x, float $y, float $w, float $h, ?array $fillRgb = null, ?array $strokeRgb = null, float $lineWidth = 0.5): string
    {
        $s = '';
        if ($fillRgb !== null) {
            $s .= sprintf("%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n", $fillRgb[0], $fillRgb[1], $fillRgb[2], $x, $y, $w, $h);
        }
        if ($strokeRgb !== null) {
            $s .= sprintf("%.3f %.3f %.3f RG %.2f w %.2f %.2f %.2f %.2f re S\n", $strokeRgb[0], $strokeRgb[1], $strokeRgb[2], $lineWidth, $x, $y, $w, $h);
        }
        return $s;
    }

    private function lineOp(float $x1, float $y1, float $x2, float $y2, array $rgb, float $lineWidth = 0.75): string
    {
        return sprintf("%.3f %.3f %.3f RG %.2f w %.2f %.2f m %.2f %.2f l S\n", $rgb[0], $rgb[1], $rgb[2], $lineWidth, $x1, $y1, $x2, $y2);
    }

    /** Draw a block of wrapped text at the current cursor, then advance it. */
    private function drawWrapped(string $text, float $size, bool $bold, array $rgb, string $align = 'left', float $spacingAfter = 8): void
    {
        $font = $bold ? 'F2' : 'F1';
        $lineHeight = $size * 1.3;
        $lines = $this->wrapText($text, $this->contentWidth, $size, $bold);

        foreach ($lines as $line) {
            $this->ensureSpace($lineHeight);
            $baseline = $this->y - $size * 0.85;

            if ($align === 'center') {
                $w = $this->stringWidth($line, $size, $bold);
                $x = self::MARGIN + ($this->contentWidth - $w) / 2;
            } else {
                $x = self::MARGIN;
            }

            $this->emit($this->textOp($x, $baseline, $line, $font, $size, $rgb));
            $this->y -= $lineHeight;
        }

        $this->y -= $spacingAfter;
    }

    // ---------------------------------------------------------------
    // Content builders (mirrors SimpleDocx's API)
    // ---------------------------------------------------------------

    public function addTitle(string $text): void
    {
        $this->drawWrapped($text, 22, true, self::COLOR_PRIMARY, 'center', 10);
    }

    public function addSubtitle(string $text): void
    {
        $this->drawWrapped($text, 11, false, self::COLOR_MUTED, 'center', 14);
    }

    public function addHeading(string $text, int $level = 1): void
    {
        $size = $level === 1 ? 15 : ($level === 2 ? 13 : 11);
        $lineHeight = $size * 1.3;

        $this->ensureSpace($lineHeight + 12);
        $baseline = $this->y - $size * 0.85;
        $this->emit($this->textOp(self::MARGIN, $baseline, $text, 'F2', $size, self::COLOR_PRIMARY));
        $this->y -= $lineHeight;

        $this->emit($this->lineOp(self::MARGIN, $this->y + 3, self::MARGIN + $this->contentWidth, $this->y + 3, self::COLOR_PRIMARY, 1));
        $this->y -= 10;
    }

    public function addParagraph(string $text, bool $muted = false): void
    {
        $this->drawWrapped($text, 11, false, $muted ? self::COLOR_MUTED : self::COLOR_BLACK, 'left', 10);
    }

    public function addBullet(string $text): void
    {
        $this->drawWrapped('-  ' . $text, 11, false, self::COLOR_BLACK, 'left', 4);
    }

    public function addSpacer(): void
    {
        $this->y -= 8;
    }

    public function addPageBreak(): void
    {
        $this->newPage();
    }

    /** Two-column "label: value" table, e.g. project meta fields. */
    public function addKeyValueTable(array $rows): void
    {
        $labelW = round($this->contentWidth * 0.3);
        $valueW = $this->contentWidth - $labelW;
        $pad = 4;
        $size = 10;
        $lineHeight = $size * 1.3;

        foreach ($rows as $label => $value) {
            $labelLines = $this->wrapText((string)$label, $labelW - 2 * $pad, $size, true);
            $valueLines = $this->wrapText((string)$value, $valueW - 2 * $pad, $size, false);
            $n = max(count($labelLines), count($valueLines));
            $rowH = $n * $lineHeight + 2 * $pad;

            $this->ensureSpace($rowH);
            $topY = $this->y;

            $this->emit($this->rectOp(self::MARGIN, $topY - $rowH, $labelW, $rowH, self::COLOR_LABEL_BG, self::COLOR_BORDER, 0.5));
            $this->emit($this->rectOp(self::MARGIN + $labelW, $topY - $rowH, $valueW, $rowH, null, self::COLOR_BORDER, 0.5));

            foreach ($labelLines as $i => $line) {
                $baseline = $topY - $pad - $i * $lineHeight - $size * 0.85;
                $this->emit($this->textOp(self::MARGIN + $pad, $baseline, $line, 'F2', $size, self::COLOR_BLACK));
            }
            foreach ($valueLines as $i => $line) {
                $baseline = $topY - $pad - $i * $lineHeight - $size * 0.85;
                $this->emit($this->textOp(self::MARGIN + $labelW + $pad, $baseline, $line, 'F1', $size, self::COLOR_BLACK));
            }

            $this->y = $topY - $rowH;
        }

        $this->y -= 10;
    }

    /**
     * Multi-column data table with a shaded header row. Repeats the
     * header row automatically if the table spills onto a new page.
     *
     * Columns are equal width by default. Two ways to change that:
     *  - Pass $colWidths: relative weights, one per column, e.g.
     *    [0.08, 0.46, 0.46] for a narrow first column. They're
     *    normalized automatically, so [1, 6, 6] works just as well.
     *  - Leave $colWidths empty and a header that looks like a
     *    row-number column ("Sr. No.", "Sr No", "SR No.", "#", ...)
     *    is auto-narrowed and the saved space is redistributed to
     *    the other columns.
     *
     * @param string[] $headers
     * @param string[][] $rows
     * @param float[] $colWidths Optional relative column-width weights (same length as $headers).
     */
    public function addTable(array $headers, array $rows, array $colWidths = []): void
    {
        $colCount = max(1, count($headers));
        $colW = $this->resolveColumnWidths($headers, $colCount, $colWidths);
        $colX = [];
        $acc = self::MARGIN;
        for ($c = 0; $c < $colCount; $c++) {
            $colX[$c] = $acc;
            $acc += $colW[$c];
        }
        $pad = 4;
        $size = 9;
        $lineHeight = $size * 1.3;

        $drawHeaderRow = function () use ($headers, $colCount, $colW, $colX, $pad, $size, $lineHeight) {
            $wrapped = [];
            $maxLines = 1;
            foreach ($headers as $c => $h) {
                $w = $this->wrapText((string)$h, $colW[$c] - 2 * $pad, $size, true);
                $wrapped[] = $w;
                $maxLines = max($maxLines, count($w));
            }
            $rowH = $maxLines * $lineHeight + 2 * $pad;
            $this->ensureSpace($rowH);
            $topY = $this->y;

            $this->emit($this->rectOp(self::MARGIN, $topY - $rowH, $this->contentWidth, $rowH, self::COLOR_HEADER_BG, null));
            for ($c = 0; $c < $colCount; $c++) {
                $x = $colX[$c];
                $this->emit($this->rectOp($x, $topY - $rowH, $colW[$c], $rowH, null, self::COLOR_BORDER, 0.5));
                foreach ($wrapped[$c] as $i => $line) {
                    $baseline = $topY - $pad - $i * $lineHeight - $size * 0.85;
                    $this->emit($this->textOp($x + $pad, $baseline, $line, 'F2', $size, self::COLOR_PRIMARY));
                }
            }
            $this->y = $topY - $rowH;
        };

        $drawHeaderRow();

        if (empty($rows)) {
            $rowH = $lineHeight + 2 * $pad;
            $this->ensureSpace($rowH);
            $topY = $this->y;
            $this->emit($this->rectOp(self::MARGIN, $topY - $rowH, $this->contentWidth, $rowH, null, self::COLOR_BORDER, 0.5));
            $msg = 'No data available.';
            $w = $this->stringWidth($msg, $size, false);
            $x = self::MARGIN + ($this->contentWidth - $w) / 2;
            $baseline = $topY - $pad - $size * 0.85;
            $this->emit($this->textOp($x, $baseline, $msg, 'F1', $size, self::COLOR_MUTED));
            $this->y = $topY - $rowH;
        } else {
            foreach ($rows as $row) {
                $wrapped = [];
                $maxLines = 1;
                foreach ($row as $c => $cell) {
                    $w = $this->wrapText((string)$cell, ($colW[$c] ?? $colW[$colCount - 1]) - 2 * $pad, $size, false);
                    $wrapped[] = $w;
                    $maxLines = max($maxLines, count($w));
                }
                $rowH = $maxLines * $lineHeight + 2 * $pad;

                if ($this->y - $rowH < $this->bottomMargin) {
                    $this->newPage();
                    $drawHeaderRow();
                }

                $topY = $this->y;
                for ($c = 0; $c < $colCount; $c++) {
                    $x = $colX[$c];
                    $w = $colW[$c];
                    $this->emit($this->rectOp($x, $topY - $rowH, $w, $rowH, null, self::COLOR_BORDER, 0.5));
                    $cellLines = $wrapped[$c] ?? [''];
                    foreach ($cellLines as $i => $line) {
                        $baseline = $topY - $pad - $i * $lineHeight - $size * 0.85;
                        $this->emit($this->textOp($x + $pad, $baseline, $line, 'F1', $size, self::COLOR_BLACK));
                    }
                }
                $this->y = $topY - $rowH;
            }
        }

        $this->y -= 12;
    }

    /**
     * Work out each column's width in points. Honors an explicit
     * $colWidths weight list when given; otherwise auto-narrows a
     * leading "Sr. No." / "#" style column and splits the rest evenly.
     * @return float[] widths in points, one per column, summing to $this->contentWidth
     */
    private function resolveColumnWidths(array $headers, int $colCount, array $colWidths): array
    {
        if (!empty($colWidths)) {
            $weights = array_values($colWidths);
            while (count($weights) < $colCount) {
                $weights[] = 1;
            }
            $total = array_sum($weights) ?: $colCount;
            $widths = [];
            foreach (range(0, $colCount - 1) as $c) {
                $widths[$c] = $this->contentWidth * ($weights[$c] / $total);
            }
            return $widths;
        }

        // Auto-detect a row-number column so it doesn't eat as much
        // room as "Notes" / "Page Link" / "Operations" style columns.
        $srPattern = '/^\s*(sr\.?\s*no\.?|s\.?\s*no\.?|#)\s*$/i';
        $srCols = [];
        foreach ($headers as $c => $h) {
            if (preg_match($srPattern, (string)$h)) {
                $srCols[] = $c;
            }
        }

        if (empty($srCols) || $colCount < 2) {
            $w = $this->contentWidth / $colCount;
            return array_fill(0, $colCount, $w);
        }

        $srWidth = max(28, min(40, $this->contentWidth * 0.07));
        $remainingCount = $colCount - count($srCols);
        $remainingWidth = ($this->contentWidth - $srWidth * count($srCols)) / max(1, $remainingCount);

        $widths = [];
        for ($c = 0; $c < $colCount; $c++) {
            $widths[$c] = in_array($c, $srCols, true) ? $srWidth : $remainingWidth;
        }
        return $widths;
    }

    // ---------------------------------------------------------------
    // Letterhead / business-letter builders
    // ---------------------------------------------------------------

    /** Company letterhead: big company name + tagline, reg number top-right, rule underneath. */
    public function addLetterhead(string $companyName, string $tagline = '', string $regLabel = '', string $regNo = ''): void
    {
        $this->ensureSpace(60);

        $baseline = $this->y - 20 * 0.85;
        $this->emit($this->textOp(self::MARGIN, $baseline, $companyName, 'F2', 20, self::COLOR_PRIMARY));

        if ($regNo !== '') {
            $label = ($regLabel !== '' ? $regLabel : 'Reg. No.') . ': ' . $regNo;
            $w = $this->stringWidth($label, 10, true);
            $this->emit($this->textOp(self::MARGIN + $this->contentWidth - $w, $baseline + 4, $label, 'F2', 10, self::COLOR_BLACK));
        }
        $this->y -= 20 * 1.3;

        if ($tagline !== '') {
            $baseline = $this->y - 9 * 0.85;
            $this->emit($this->textOp(self::MARGIN, $baseline, $tagline, 'F1', 9, self::COLOR_MUTED));
            $this->y -= 9 * 1.3;
        }

        $this->y -= 4;
        $this->emit($this->lineOp(self::MARGIN, $this->y, self::MARGIN + $this->contentWidth, $this->y, self::COLOR_PRIMARY, 1.5));
        $this->y -= 16;
    }

    /** Single "Label: value" line, e.g. "Date: 20/08/2026" */
    public function addLabelLine(string $label, string $value): void
    {
        $size = 11;
        $this->ensureSpace($size * 1.3);
        $baseline = $this->y - $size * 0.85;
        $this->emit($this->textOp(self::MARGIN, $baseline, $label, 'F2', $size, self::COLOR_BLACK));
        $lw = $this->stringWidth($label, $size, true);
        $this->emit($this->textOp(self::MARGIN + $lw, $baseline, $value, 'F1', $size, self::COLOR_BLACK));
        $this->y -= $size * 1.3 + 6;
    }

    /** "To," / "From," block with a bold label line and plain lines under it. */
    public function addAddressBlock(string $label, array $lines): void
    {
        $size = 11;
        $this->ensureSpace($size * 1.3);
        $baseline = $this->y - $size * 0.85;
        $this->emit($this->textOp(self::MARGIN, $baseline, $label, 'F2', $size, self::COLOR_BLACK));
        $this->y -= $size * 1.3 + 2;

        foreach ($lines as $line) {
            $line = trim((string)$line);
            if ($line === '') {
                continue;
            }
            $this->ensureSpace($size * 1.3);
            $baseline = $this->y - $size * 0.85;
            $this->emit($this->textOp(self::MARGIN, $baseline, $line, 'F1', $size, self::COLOR_BLACK));
            $this->y -= $size * 1.3 + 2;
        }

        $this->y -= 10;
    }

    /** Bold "Subject: ..." line. */
    public function addSubjectLine(string $text): void
    {
        $this->drawWrapped('Subject: ' . $text, 11, true, self::COLOR_BLACK, 'left', 12);
    }

    /** Signature block at the end of the letter; first line is bold. */
    public function addSignature(array $lines): void
    {
        $this->y -= 8;

        foreach ($lines as $i => $line) {
            $line = trim((string)$line);
            if ($line === '') {
                continue;
            }
            $this->drawWrapped($line, 11, $i === 0, self::COLOR_BLACK, 'left', 2);
        }
    }

    // ---------------------------------------------------------------
    // Images / letterhead background / footer
    // ---------------------------------------------------------------

    /**
     * Register a baseline JPEG file so it can be drawn with drawImage().
     * Returns an image id to pass to drawImage(). Only JPEG is supported
     * (embedded as-is via /DCTDecode, no re-encoding needed).
     *
     * @throws \RuntimeException if the file can't be read or isn't a JPEG.
     */
    public function registerImage(string $path): int
    {
        if (!is_readable($path)) {
            throw new \RuntimeException("SimplePdf: image not readable: $path");
        }

        $info = @getimagesize($path);
        if ($info === false || $info[2] !== IMAGETYPE_JPEG) {
            throw new \RuntimeException("SimplePdf: only JPEG images are supported: $path");
        }

        $data = file_get_contents($path);
        $id = count($this->images);
        $this->images[$id] = ['data' => $data, 'w' => $info[0], 'h' => $info[1]];
        return $id;
    }

    /** Draw a previously registered image at absolute page coordinates (bottom-left origin). */
    public function drawImage(int $id, float $x, float $y, float $w, float $h): void
    {
        if (!isset($this->images[$id])) {
            return;
        }
        $this->curPageImageIds[$id] = true;
        $this->emit(sprintf("q %.3f 0 0 %.3f %.3f %.3f cm /Im%d Do Q\n", $w, $h, $x, $y, $id));
    }

    /** Draw a registered image stretched to fill the entire current page (e.g. a letterhead background). */
    public function drawFullPageBackground(int $id): void
    {
        $this->drawImage($id, 0, 0, self::PAGE_W, self::PAGE_H);
    }

    /** Move the layout cursor directly (e.g. to clear a letterhead graphic before writing text). */
    public function setCursorY(float $y): void
    {
        $this->y = $y;
    }

    /** Raise (or restore) how close to the bottom edge content is allowed to reach on the CURRENT page only. */
    public function setBottomMargin(float $margin): void
    {
        $this->bottomMargin = $margin;
    }

    /**
     * Stamp $text, centered, near the bottom of every page from here on.
     * @param bool $skipFirstPage Skip the page currently being built (typical when
     *                            page 1 already has its own graphical footer/letterhead).
     */
    public function setFooterText(string $text, bool $skipFirstPage = true): void
    {
        $this->footerText = $text;
        $this->footerSkipFirstPage = $skipFirstPage;
    }

    /**
     * Reserve a block on the page as a real, fillable PDF form field
     * (an AcroForm text field) instead of drawing static text. The user
     * can click straight into this box in any PDF viewer that supports
     * forms (Adobe Acrobat/Reader, Chrome, Firefox, Preview, etc.) and
     * type directly, rather than editing the value somewhere else first.
     *
     * Draws a faint box (so it's visually obvious it's editable) and
     * advances the cursor the same way addParagraph()/addTable() do, so
     * it can be dropped into the normal top-to-bottom flow of a page.
     *
     * @param string $name   Unique field name (e.g. 'custom_subject'). Must
     *                       be unique across the whole document.
     * @param string $value  Pre-filled value shown in the field.
     * @param int    $lines  How many lines tall to make the box. 1 = a
     *                       single-line field; >1 sets the PDF Multiline
     *                       flag so viewers wrap text inside it.
     * @param bool   $bold   Use the bold font for text typed into the field.
     * @param float  $size   Font size used inside the field.
     */
    public function addTextField(string $name, string $value = '', int $lines = 1, bool $bold = false, float $size = 11): void
    {
        $lineHeight = $size * 1.3;
        $pad = 4;
        $boxH = $lines * $lineHeight + 2 * $pad;

        $this->ensureSpace($boxH);
        $topY = $this->y;
        $bottomY = $topY - $boxH;

        // Faint fill + border so the field reads as "editable" on the page.
        $this->emit($this->rectOp(
            self::MARGIN,
            $bottomY,
            $this->contentWidth,
            $boxH,
            [0.976, 0.984, 0.996],
            self::COLOR_BORDER,
            0.75
        ));

        $this->fieldCounter++;

        $this->curPageFields[] = [
            'name'      => $name,
            'value'     => $value,
            'rect'      => [
                self::MARGIN + 1,
                $bottomY + 1,
                self::MARGIN + $this->contentWidth - 1,
                $topY - 1,
            ],
            'multiline' => $lines > 1,
            'bold'      => $bold,
            'size'      => $size,
        ];

        $this->y = $bottomY - 10;
    }

    /**
     * Build the visible appearance (/AP /N) of a text field so the value
     * shows in every PDF viewer, including ones that ignore NeedAppearances.
     */
    private function buildFieldAppearance(array $field, float $w, float $h): string
    {
        $size = (float)$field['size'];
        $font = $field['bold'] ? 'F2' : 'F1';
        $lineHeight = $size * 1.3;
        $pad = 3;

        $paragraphs = preg_split('/\r\n|\r|\n/', (string)$field['value']);
        $lines = [];
        foreach ($paragraphs as $para) {
            foreach ($this->wrapText($para, $w - 2 * $pad, $size, (bool)$field['bold']) as $l) {
                $lines[] = $l;
            }
        }

        $stream = "/Tx BMC\nq\n";
        foreach ($lines as $i => $line) {
            if ($line === '') {
                continue;
            }
            $baseline = $h - $pad - $size * 0.85 - $i * $lineHeight;
            if ($baseline < 0) {
                break;
            }
            $stream .= sprintf("BT 0 g /%s %.2f Tf %.2f %.2f Td (%s) Tj ET\n", $font, $size, $pad, $baseline, $this->esc($line));
        }
        $stream .= "Q\nEMC";

        return $stream;
    }

    // ---------------------------------------------------------------
    // Output
    // ---------------------------------------------------------------

    /** Build the PDF binary and return it as a string. */
    public function build(): string
    {
        if ($this->curStream !== '' || empty($this->pages)) {
            $this->stampFooterIfNeeded();
            $this->pages[] = $this->curStream;
            $this->pageFields[] = $this->curPageFields;
            $this->pageImageIds[] = $this->curPageImageIds;
        }

        $pageCount = count($this->pages);

        $objects = [];
        $kids = [];
        for ($k = 0; $k < $pageCount; $k++) {
            $kids[] = (5 + 2 * $k) . ' 0 R';
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        // -----------------------------------------------------------
        // Image XObjects (registerImage()/drawImage() calls), if any.
        // Numbered right after the page/content objects so the
        // AcroForm block below (if used) can simply continue counting.
        // -----------------------------------------------------------

        $imageCount = count($this->images);
        $imageBaseObjNum = 5 + 2 * $pageCount;
        $imageObjNums = [];
        $imagePayloads = []; // objNum => ['w'=>,'h'=>,'data'=>]
        $formStreams = [];   // objNum => ['w'=>,'h'=>,'data'=>] (field appearance streams)

        foreach ($this->images as $id => $img) {
            $objNum = $imageBaseObjNum + $id;
            $imageObjNums[$id] = $objNum;
            $imagePayloads[$objNum] = $img;
            $objects[$objNum] = true; // placeholder so this object number counts toward $numObjects below
        }

        for ($k = 0; $k < $pageCount; $k++) {
            $pageObjNum = 5 + 2 * $k;
            $contentObjNum = 6 + 2 * $k;

            $xobjPart = '';
            $usedIds = array_keys($this->pageImageIds[$k] ?? []);
            if (!empty($usedIds)) {
                $entries = [];
                foreach ($usedIds as $imgId) {
                    $entries[] = "/Im$imgId " . $imageObjNums[$imgId] . ' 0 R';
                }
                $xobjPart = ' /XObject << ' . implode(' ', $entries) . ' >>';
            }

            $objects[$pageObjNum] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 ' . self::PAGE_W . ' ' . self::PAGE_H . ']'
                . ' /Resources << /Font << /F1 3 0 R /F2 4 0 R >>' . $xobjPart . ' >> /Contents ' . $contentObjNum . ' 0 R >>';
            $objects[$contentObjNum] = "STREAM::" . $this->pages[$k];
        }

        // -----------------------------------------------------------
        // AcroForm fillable fields (addTextField() calls), if any.
        // Each field becomes its own Widget annotation object, listed
        // on its page's /Annots and referenced from the document's
        // /AcroForm dictionary so PDF viewers treat them as real,
        // editable form fields (not just drawn text).
        // -----------------------------------------------------------

        $allFields = [];
        foreach ($this->pageFields as $pageIndex => $fields) {
            foreach ($fields as $field) {
                $field['pageIndex'] = $pageIndex;
                $allFields[] = $field;
            }
        }

        if (!empty($allFields)) {
            $acroFormObjNum = $imageBaseObjNum + $imageCount;
            $fieldObjNums = [];

            foreach ($allFields as $i => $field) {
                $fieldObjNums[] = $acroFormObjNum + 1 + $i;
            }

            $annotsByPage = array_fill(0, $pageCount, []);
            foreach ($allFields as $i => $field) {
                $annotsByPage[$field['pageIndex']][] = $fieldObjNums[$i];
            }

            for ($k = 0; $k < $pageCount; $k++) {
                if (empty($annotsByPage[$k])) {
                    continue;
                }
                $pageObjNum = 5 + 2 * $k;
                $annotRefs = implode(' ', array_map(fn($n) => "$n 0 R", $annotsByPage[$k]));
                $objects[$pageObjNum] = preg_replace('/\s*>>\s*$/', '', $objects[$pageObjNum])
                    . ' /Annots [' . $annotRefs . '] >>';
            }

            $apBaseObjNum = $acroFormObjNum + 1 + count($allFields);

            foreach ($allFields as $i => $field) {
                $objNum = $fieldObjNums[$i];
                $apObjNum = $apBaseObjNum + $i;
                $rectW = $field['rect'][2] - $field['rect'][0];
                $rectH = $field['rect'][3] - $field['rect'][1];
                $formStreams[$apObjNum] = [
                    'w'    => $rectW,
                    'h'    => $rectH,
                    'data' => $this->buildFieldAppearance($field, $rectW, $rectH),
                ];
                $objects[$apObjNum] = true;
                $rect = $field['rect'];
                $daFont = $field['bold'] ? 'F2' : 'F1';
                $da = sprintf('0 g /%s %.2f Tf', $daFont, $field['size']);
                $flags = $field['multiline'] ? 4096 : 0;
                $pageObjNum = 5 + 2 * $field['pageIndex'];

                $objects[$objNum] = '<< /Type /Annot /Subtype /Widget /FT /Tx'
                    . ' /Rect [' . sprintf('%.2f %.2f %.2f %.2f', $rect[0], $rect[1], $rect[2], $rect[3]) . ']'
                    . ' /F 4'
                    . ' /Ff ' . $flags
                    . ' /T (' . $this->esc($field['name']) . ')'
                    . ' /V (' . $this->esc($field['value']) . ')'
                    . ' /DA (' . $da . ')'
                    . ' /AP << /N ' . $apObjNum . ' 0 R >>'
                    . ' /MK << /BC [0.85 0.85 0.85] >>'
                    . ' /P ' . $pageObjNum . ' 0 R'
                    . ' >>';
            }

            $acroFieldRefs = implode(' ', array_map(fn($n) => "$n 0 R", $fieldObjNums));
            $objects[$acroFormObjNum] = '<< /Fields [' . $acroFieldRefs . ']'
                . ' /DA (0 g /F1 11 Tf)'
                . ' /DR << /Font << /F1 3 0 R /F2 4 0 R >> >>'
                . ' /NeedAppearances true'
                . ' >>';

            $objects[1] = '<< /Type /Catalog /Pages 2 0 R /AcroForm ' . $acroFormObjNum . ' 0 R >>';
        }

        $out = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $numObjects = max(array_keys($objects));
        $offsets = [];

        for ($i = 1; $i <= $numObjects; $i++) {
            $offsets[$i] = strlen($out);

            if (isset($imagePayloads[$i])) {
                $img = $imagePayloads[$i];
                $out .= "$i 0 obj\n<< /Type /XObject /Subtype /Image /Width " . $img['w']
                    . ' /Height ' . $img['h'] . ' /ColorSpace /DeviceRGB /BitsPerComponent 8'
                    . ' /Filter /DCTDecode /Length ' . strlen($img['data']) . " >>\nstream\n"
                    . $img['data'] . "\nendstream\nendobj\n";
                continue;
            }

            if (isset($formStreams[$i])) {
                $f = $formStreams[$i];
                $out .= "$i 0 obj\n<< /Type /XObject /Subtype /Form /BBox [0 0 " . sprintf('%.2f %.2f', $f['w'], $f['h'])
                    . '] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Length ' . strlen($f['data']) . " >>\nstream\n"
                    . $f['data'] . "\nendstream\nendobj\n";
                continue;
            }

            $body = $objects[$i] ?? '<< >>';

            if (strpos($body, 'STREAM::') === 0) {
                $data = substr($body, 8);
                $out .= "$i 0 obj\n<< /Length " . strlen($data) . " >>\nstream\n" . $data . "endstream\nendobj\n";
            } else {
                $out .= "$i 0 obj\n" . $body . "\nendobj\n";
            }
        }

        $xrefStart = strlen($out);
        $out .= "xref\n0 " . ($numObjects + 1) . "\n";
        $out .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $numObjects; $i++) {
            $out .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $out .= "trailer\n<< /Size " . ($numObjects + 1) . " /Root 1 0 R >>\nstartxref\n$xrefStart\n%%EOF";

        return $out;
    }

    /**
     * Build and stream the file to the browser.
     * @param bool $inline When true, opens in the browser tab (e.g. Chrome's
     *                      PDF viewer) instead of triggering a download.
     */
    public function output(string $filename, bool $inline = false): void
    {
        $bytes = $this->build();
        if (ob_get_level()) {
            ob_end_clean();
        }
        $disposition = $inline ? 'inline' : 'attachment';
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $disposition . '; filename="' . $this->sanitizeFilename($filename) . '"');
        header('Content-Length: ' . strlen($bytes));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $bytes;
    }

    private function sanitizeFilename(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9 _\-\.]/', '', $name);
        return $name !== '' ? $name : 'report.pdf';
    }
}
