<?php
/**
 * download_report.php
 * --------------------
 * Generates a PDF project report styled as a business
 * letter (letterhead, Date / To / From / Subject, a short covering
 * note, work-item counters, and a signed closing) — the same
 * layout as a printed client report, filled in with this
 * project's own data.
 */

require_once __DIR__ . '/common/auth_check.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/libs/SimplePdf.php';


// ===============================================================
// CHECK SimplePdf
// ===============================================================

if (!class_exists('SimplePdf')) {
    die('Error: SimplePdf class not found. Check libs/SimplePdf.php');
}


// ===============================================================
// LETTERHEAD / "FROM" COMPANY SETTINGS
// ---------------------------------------------------------------
// Edit these to match your own company's details. They print at
// the top and bottom of every report, the same way Ekatta
// Innovators LLP's own details appear on their sample letter.
// (These aren't in the database yet — feel free to move them into
// a settings table later; for now this is the one place to change
// them.)
// ===============================================================

const COMPANY_NAME      = 'Ekatta Innovators LLP';
const COMPANY_TAGLINE   = 'You Dream it, We Build it';
const COMPANY_REG_LABEL = 'DIN No.';
const COMPANY_REG_NO    = 'AAN-9912';   // leave blank to hide
const COMPANY_ADDRESS   = 'Aurangabad, 431001';
const COMPANY_PHONE     = '+91 9765874888';
const COMPANY_EMAIL     = '';

// Full-page letterhead background (logo, DIN box, watermark, footer
// contact bar) drawn behind page 1 only — same graphic as the sample
// letter. Continuation pages get the plain-text footer below instead.
const COMPANY_LOGO_PATH   = __DIR__ . '/assets/img/report-letterhead-bg.jpg';
const COMPANY_FOOTER_TEXT = 'www.ekatta.in   Service@ekatta.in';


// ===============================================================
// HELPER FUNCTIONS
// ===============================================================

function fmt_date($date): string
{
    if (empty($date)) {
        return '-';
    }

    $timestamp = strtotime($date);

    if ($timestamp === false) {
        return '-';
    }

    return date('M d, Y', $timestamp);
}


/** Letter-style date, e.g. "20/08/2026" (matches the sample letterhead). */
function fmt_date_slash($date): string
{
    $timestamp = empty($date) ? time() : strtotime($date);

    if ($timestamp === false) {
        $timestamp = time();
    }

    return date('d/m/Y', $timestamp);
}


/** "MMM YYYY" label for a date, used to describe the report period. */
function fmt_month_year($date): string
{
    $timestamp = empty($date) ? time() : strtotime($date);

    if ($timestamp === false) {
        $timestamp = time();
    }

    return date('F Y', $timestamp);
}


function work_summary(array $rows): array
{
    $total = count($rows);
    $done = 0;

    foreach ($rows as $row) {
        if (($row['status'] ?? '') === 'Developed') {
            $done++;
        }
    }

    return [$total, $done];
}


/**
 * Count how many rows fall inside a period, based on a date column.
 * $periodStart is exclusive (null means "from the beginning of time"),
 * $periodEnd is inclusive.
 */
function period_count(
    array $rows,
    string $dateColumn,
    ?string $periodStart,
    string $periodEnd,
    ?string $statusFilter = null
): int {

    $startTs = $periodStart ? strtotime($periodStart) : null;
    $endTs = strtotime($periodEnd) ?: time();
    $count = 0;

    foreach ($rows as $row) {

        if ($statusFilter !== null && ($row['status'] ?? '') !== $statusFilter) {
            continue;
        }

        $raw = $row[$dateColumn] ?? null;

        if (empty($raw)) {
            continue;
        }

        $ts = strtotime($raw);

        if ($ts === false) {
            continue;
        }

        if ($startTs !== null && $ts <= $startTs) {
            continue;
        }

        if ($ts > $endTs) {
            continue;
        }

        $count++;
    }

    return $count;
}


/**
 * Turn rows from app_work / dashboard_work / website_work into the same
 * shape as the report-section tables (page_name, page_link, created_date),
 * keeping only rows whose status is in $statuses. This lets work entered on
 * the App / Dashboard / Website work pages show up in the report even when
 * nothing was typed into the separate "Report Sections" screen.
 */
function work_as_section_rows(array $rows, array $statuses): array
{
    $out = [];

    foreach ($rows as $row) {
        if (!in_array($row['status'] ?? '', $statuses, true)) {
            continue;
        }

        $out[] = [
            'page_name'    => $row['page_name'] ?? ($row['screen_name'] ?? '-'),
            'page_link'    => '',
            'created_date' => $row['created_date'] ?? ($row['created_at'] ?? null),
        ];
    }

    return $out;
}


function fetch_work_rows(
    mysqli $conn,
    string $table,
    int $projectId
): array {

    $allowedTables = [
        'app_work',
        'dashboard_work',
        'website_work'
    ];

    if (!in_array($table, $allowedTables, true)) {
        return [];
    }

    $rows = [];

    $sql = "
        SELECT *
        FROM `$table`
        WHERE project_id = ?
        ORDER BY id ASC
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $projectId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($result && ($row = $result->fetch_assoc())) {
        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}


/**
 * Fetch every row for one of the "full report" detail tables
 * (team members, Figma pages, page links, operations lists,
 * database tables, API list, changes log), ordered the way they
 * were entered in reportsections.php.
 */
function fetch_section_rows(
    mysqli $conn,
    string $table,
    int $projectId
): array {

    $allowedTables = [
        'project_team_members',
        'figma_pages',
        'website_page_links',
        'website_pages_designed',
        'dashboard_pages_designed',
        'dashboard_page_links',
        'app_pages_designed',
        'app_page_links',
        'dashboard_operations',
        'website_operations',
        'app_operations',
        'database_tables',
        'api_list',
        'changes_log',
    ];

    if (!in_array($table, $allowedTables, true)) {
        return [];
    }

    $rows = [];

    $stmt = $conn->prepare("SELECT * FROM `$table` WHERE project_id = ? ORDER BY sort_order ASC, id ASC");

    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $projectId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($result && ($row = $result->fetch_assoc())) {
        $rows[] = $row;
    }

    $stmt->close();

    return $rows;
}


// ===============================================================
// GET PARAMETERS
// ===============================================================

$report_id = isset($_GET['report_id'])
    ? (int)$_GET['report_id']
    : 0;

$project_id = isset($_GET['project_id'])
    ? (int)$_GET['project_id']
    : 0;

// When ?view=1 is present, open inline in the browser tab instead of
// forcing a download (used by the "View" button).
$view_mode = isset($_GET['view']) && $_GET['view'] == '1';


// ===============================================================
// GET REPORT
// ===============================================================

$report = null;

if ($report_id > 0) {

    $stmt = $conn->prepare("
        SELECT *
        FROM project_reports
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $report_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $report = $result->fetch_assoc();
    }

    $stmt->close();

    if (!$report) {
        http_response_code(404);
        die("Report not found.");
    }

    $project_id = (int)$report['project_id'];
}


// ===============================================================
// VALIDATE PROJECT
// ===============================================================

if ($project_id <= 0) {
    http_response_code(400);
    die("Missing project.");
}


// ===============================================================
// GET PROJECT
// ===============================================================

$stmt = $conn->prepare("
    SELECT *
    FROM projects
    WHERE id = ?
    LIMIT 1
");

$stmt->bind_param("i", $project_id);
$stmt->execute();

$result = $stmt->get_result();

if (!$result || $result->num_rows === 0) {
    $stmt->close();

    http_response_code(404);
    die("Project not found.");
}

$project = $result->fetch_assoc();

$stmt->close();


// ===============================================================
// GET CLIENT
// ===============================================================

$client = null;


// First try client_id
if (!empty($project['client_id'])) {

    $stmt = $conn->prepare("
        SELECT *
        FROM clients
        WHERE id = ?
        LIMIT 1
    ");

    $stmt->bind_param("i", $project['client_id']);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $client = $result->fetch_assoc();
    }

    $stmt->close();
}


// Fallback to client_name
if (!$client && !empty($project['client_name'])) {

    $stmt = $conn->prepare("
        SELECT *
        FROM clients
        WHERE client_name = ?
        LIMIT 1
    ");

    $stmt->bind_param("s", $project['client_name']);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $client = $result->fetch_assoc();
    }

    $stmt->close();
}


// ===============================================================
// GET TECHNOLOGIES
// ===============================================================

$technologies = [];

$stmt = $conn->prepare("
    SELECT *
    FROM technologies
    WHERE project_id = ?
    ORDER BY id ASC
");

$stmt->bind_param("i", $project_id);
$stmt->execute();

$result = $stmt->get_result();

while ($result && ($row = $result->fetch_assoc())) {
    $technologies[] = $row;
}

$stmt->close();


// ===============================================================
// GET WORK
// ===============================================================

$appWork = fetch_work_rows(
    $conn,
    'app_work',
    $project_id
);

$dashboardWork = fetch_work_rows(
    $conn,
    'dashboard_work',
    $project_id
);

$websiteWork = fetch_work_rows(
    $conn,
    'website_work',
    $project_id
);


// ===============================================================
// GET FULL-REPORT SECTION DATA (team, Figma pages, page links,
// operations lists, database tables, API list, changes log)
// ===============================================================

$teamMembers        = fetch_section_rows($conn, 'project_team_members', $project_id);
$figmaPages         = fetch_section_rows($conn, 'figma_pages', $project_id);
$websitePagesDesigned = fetch_section_rows($conn, 'website_pages_designed', $project_id);
$websitePageLinks   = fetch_section_rows($conn, 'website_page_links', $project_id);
$dashboardDesigned  = fetch_section_rows($conn, 'dashboard_pages_designed', $project_id);
$dashboardPageLinks = fetch_section_rows($conn, 'dashboard_page_links', $project_id);
$appPagesDesigned   = fetch_section_rows($conn, 'app_pages_designed', $project_id);
$appPageLinks       = fetch_section_rows($conn, 'app_page_links', $project_id);
$dashboardOps       = fetch_section_rows($conn, 'dashboard_operations', $project_id);
$websiteOps         = fetch_section_rows($conn, 'website_operations', $project_id);
$appOps             = fetch_section_rows($conn, 'app_operations', $project_id);
$databaseTables     = fetch_section_rows($conn, 'database_tables', $project_id);
$apiList            = fetch_section_rows($conn, 'api_list', $project_id);
$changesLog         = fetch_section_rows($conn, 'changes_log', $project_id);


// ---------------------------------------------------------------
// Fall back to the App / Dashboard / Website work lists when the
// matching report-section list is empty, so the report isn't blank.
// ---------------------------------------------------------------

if (empty($figmaPages) && empty($websitePagesDesigned)) {
    $websitePagesDesigned = work_as_section_rows($websiteWork, ['Designed', 'Developed']);
}
if (empty($websitePageLinks)) {
    $websitePageLinks = work_as_section_rows($websiteWork, ['Developed']);
}
if (empty($dashboardDesigned)) {
    $dashboardDesigned = work_as_section_rows($dashboardWork, ['Designed', 'Developed']);
}
if (empty($dashboardPageLinks)) {
    $dashboardPageLinks = work_as_section_rows($dashboardWork, ['Developed']);
}
if (empty($appPagesDesigned)) {
    $appPagesDesigned = work_as_section_rows($appWork, ['Designed', 'Developed']);
}
if (empty($appPageLinks)) {
    $appPageLinks = work_as_section_rows($appWork, ['Developed']);
}

// "Website Design Work" counter uses Figma pages; if there are none,
// use the designed website pages instead.
$websiteDesignList = !empty($figmaPages) ? $figmaPages : $websitePagesDesigned;


// ===============================================================
// GET ALL REPORTS FOR THIS PROJECT (used for report history +
// working out the previous report date, i.e. the start of "this"
// reporting period)
// ===============================================================

$allReports = [];

$stmt = $conn->prepare("
    SELECT *
    FROM project_reports
    WHERE project_id = ?
    ORDER BY report_date ASC, created_at ASC
");

$stmt->bind_param("i", $project_id);
$stmt->execute();

$result = $stmt->get_result();

while ($result && ($row = $result->fetch_assoc())) {
    $allReports[] = $row;
}

$stmt->close();


// ===============================================================
// WORK OUT THE REPORT PERIOD
// periodEnd   = this report's date, or today for a full project report
// periodStart = the previous report's date, or the project start date
//               if there isn't one
// ===============================================================

$periodEnd = $report['report_date'] ?? date('Y-m-d');
$periodStart = $project['start_date'] ?? null;

if ($report) {
    foreach ($allReports as $row) {
        if ((int)$row['id'] === (int)$report['id']) {
            break;
        }
        $periodStart = $row['report_date'] ?: $periodStart;
    }
}


// ===============================================================
// CREATE DOCUMENT
// ===============================================================

$doc = new SimplePdf();


// ===============================================================
// LETTERHEAD
// ---------------------------------------------------------------
// Page 1 gets the full branded letterhead graphic (logo, DIN box,
// watermark, footer contact bar) as a background image, the same
// way it appears on the printed sample letter. If the file is
// missing, fall back to the plain text letterhead so the report
// still generates.
// ===============================================================

if (is_readable(COMPANY_LOGO_PATH)) {
    $logoImageId = $doc->registerImage(COMPANY_LOGO_PATH);
    $doc->drawFullPageBackground($logoImageId);

    // Clear the header graphic (logo + DIN box + rule) before writing
    // any letter text, and keep the footer contact bar clear at the
    // bottom of this page only.
    $doc->setCursorY(700);
    $doc->setBottomMargin(95);
} else {
    $doc->addLetterhead(
        COMPANY_NAME,
        COMPANY_TAGLINE,
        COMPANY_REG_LABEL,
        COMPANY_REG_NO
    );
}

// Plain-text footer for every page after page 1 (page 1 already
// shows the site/email in its letterhead graphic's footer bar).
$doc->setFooterText(COMPANY_FOOTER_TEXT, true);

$doc->addLabelLine(
    'Date: ',
    fmt_date_slash($report['report_date'] ?? null)
);


// ===============================================================
// TO / FROM
// ===============================================================

$toLines = [];

if ($client) {
    $toLines[] = trim(
        ($client['contact_person'] ?? '') .
        (!empty($client['designation']) ? ', ' . $client['designation'] : '')
    );
    $toLines[] = $client['organization_name'] ?? '';
    $toLines[] = trim(
        ($client['address'] ?? '') . ' ' .
        ($client['state'] ?? '') . ' ' .
        ($client['pin_code'] ?? '')
    );
} else {
    $toLines[] = $project['client_name'] ?? '-';
}

$doc->addAddressBlock('To,', $toLines);

$fromLines = [];

if (!empty($_SESSION['user_name'])) {
    $fromLines[] = $_SESSION['user_name'];
}

$fromLines[] = COMPANY_NAME;

if (COMPANY_ADDRESS !== '') {
    $fromLines[] = COMPANY_ADDRESS;
}

$doc->addAddressBlock('From,', $fromLines);


// ===============================================================
// SUBJECT + COVERING NOTE
// (uses the custom text saved via edit_report.php, if any,
// otherwise falls back to the auto-generated wording)
// ===============================================================

$subjectText = trim($report['custom_subject'] ?? '') !== ''
    ? $report['custom_subject']
    : (($project['project_name'] ?? 'Project') . ' - Project Work Report');

$clientOrg = $client['organization_name']
    ?? ($project['client_name'] ?? 'your organization');

$autoIntro = $report
    ? (
        'This report covers the work carried out on the ' .
        ($project['project_name'] ?? 'project') . ' for ' . $clientOrg .
        ' from ' . fmt_month_year($periodStart) . ' to ' .
        fmt_month_year($periodEnd) . '. We appreciate your continued ' .
        'trust and partnership, and are glad to share a summary of ' .
        'the work completed in this period below.'
    )
    : (
        'We are pleased to share this project work report for the ' .
        ($project['project_name'] ?? 'project') . ', prepared for ' .
        $clientOrg . '. We take pride in the quality of our work, and ' .
        'believe it will continue to benefit your organization.'
    );

$introText = trim($report['custom_intro'] ?? '') !== ''
    ? $report['custom_intro']
    : $autoIntro;

if ($report) {
    // This PDF is tied to a saved report row, so its text can be
    // edited directly here as real, fillable PDF fields (rather than
    // on a separate project page) — open this PDF in Acrobat/Reader,
    // Chrome, Firefox, or Preview and type straight into the boxes.
    // Note: edits made in the PDF only live in that PDF file; they
    // don't sync back into the app unless the filled PDF is re-imported.
    $doc->addLabelLine('Subject: ', '');
    $doc->addTextField('custom_subject', $subjectText, 1, true, 11);

    $doc->addParagraph('Respected Sir/Madam,');

    $doc->addTextField('custom_intro', $introText, 5, false, 11);
} else {
    // No saved report row to attach edits to — fall back to plain text.
    $doc->addSubjectLine($subjectText);
    $doc->addParagraph('Respected Sir/Madam,');
    $doc->addParagraph($introText);
}


// ===============================================================
// PERIOD WORK COUNTERS  (mirrors the sample's "Updated Counters" table)
// ===============================================================


$doc->addHeading(
    ($report ? fmt_month_year($periodStart) . ' to ' . fmt_month_year($periodEnd) : 'This Period') .
    ' - Updated Counters'
);

$periodRows = [
    ['1', 'Website Design Work',              (string)period_count($websiteDesignList, 'created_date', $periodStart, $periodEnd)],
    ['2', 'Website Development Pages',        (string)period_count($websitePageLinks, 'created_date', $periodStart, $periodEnd)],
    ['3', 'Database Management System',       (string)period_count($databaseTables, 'created_date', $periodStart, $periodEnd)],
    ['4', 'Dashboard Design',                 (string)period_count($dashboardDesigned, 'created_date', $periodStart, $periodEnd)],
    ['5', 'Dashboard Development',            (string)period_count($dashboardPageLinks, 'created_date', $periodStart, $periodEnd)],
    ['6', 'App Design',                       (string)period_count($appPagesDesigned, 'created_date', $periodStart, $periodEnd)],
    ['7', 'App Development',                  (string)period_count($appPageLinks, 'created_date', $periodStart, $periodEnd)],
    ['8', 'API Creation & Integration',       (string)period_count($apiList, 'created_date', $periodStart, $periodEnd)],
    ['9', 'Operation And Functionality',      (string)(
        period_count($dashboardOps, 'created_date', $periodStart, $periodEnd) +
        period_count($websiteOps, 'created_date', $periodStart, $periodEnd) +
        period_count($appOps, 'created_date', $periodStart, $periodEnd)
    )],
    ['10', 'Changes/Suggestions/Modifications', (string)period_count($changesLog, 'change_date', $periodStart, $periodEnd)],
];

$doc->addTable(
    ['Sr. No.', 'Project Work / Item', 'No. of Work Items'],
    $periodRows
);


// ===============================================================
// OVERALL WORK COUNTERS  (mirrors the sample's "Overall Work Counters")
// ===============================================================

$doc->addHeading('Overall Work Counters');

$overallRows = [
    ['1', 'Website Design',               (string)count($websiteDesignList)],
    ['2', 'Website Development',          (string)count($websitePageLinks)],
    ['3', 'Dashboard Design',             (string)count($dashboardDesigned)],
    ['4', 'Dashboard Development',        (string)count($dashboardPageLinks)],
    ['5', 'App Design',                   (string)count($appPagesDesigned)],
    ['6', 'App Development',              (string)count($appPageLinks)],
    ['7', 'Operation And Functionality',  (string)(count($dashboardOps) + count($websiteOps) + count($appOps))],
    ['8', 'Database Management System',   (string)count($databaseTables)],
    ['9', 'API Creation & Integration',   (string)count($apiList)],
    ['10', 'Changes',                     (string)count($changesLog)],
];

$doc->addTable(
    ['Sr. No.', 'Project Work / Item', 'No. of Work Items'],
    $overallRows
);


// ===============================================================
// CHANGES / SUGGESTIONS / MODIFICATIONS
// ===============================================================

if (!empty($changesLog)) {
    $doc->addHeading('Changes/Suggestions/Modifications');

    $rows = [];
    $sr = 1;
    foreach ($changesLog as $row) {
        $rows[] = [
            (string)$sr++,
            fmt_date_slash($row['change_date'] ?? null),
            $row['page'] ?? '-',
            $row['section'] ?? '-',
            $row['note'] ?? '-',
        ];
    }

    $doc->addTable(['SR No', 'Date', 'Page', 'Section', 'Note/suggestion'], $rows, [1, 1.5, 1.8, 1.8, 5]);
}


// ===============================================================
// DASHBOARD - OPERATIONS LIST
// ===============================================================

if (!empty($dashboardOps)) {
    $doc->addHeading('Dashboard - Operations List');

    $rows = [];
    $sr = 1;
    foreach ($dashboardOps as $row) {
        $rows[] = [(string)$sr++, $row['page'] ?? '-', $row['section'] ?? '-', $row['operation'] ?? '-'];
    }

    $doc->addTable(['Sr No', 'Page', 'Section', 'Operations'], $rows, [1, 2, 2, 4]);
}


// ===============================================================
// PROJECT TEAM MEMBERS
// ===============================================================

if (!empty($teamMembers)) {
    $doc->addHeading('Project Team Members');

    $rows = [];
    $sr = 1;
    foreach ($teamMembers as $row) {
        $rows[] = [(string)$sr++, $row['name'] ?? '-', $row['role'] ?? '-'];
    }

    $doc->addTable(['Sr. No.', 'Project Team Members', 'Role'], $rows);
}


// ===============================================================
// FIGMA DESIGN WORK
// ===============================================================

if (!empty($figmaPages)) {
    $doc->addHeading('Figma Design Work');

    $rows = [];
    $sr = 1;
    foreach ($figmaPages as $row) {
        $rows[] = [(string)$sr++, $row['page_name'] ?? '-'];
    }

    $doc->addTable(['Sr No', 'Page'], $rows);
}


// ===============================================================
// WEBSITE PAGES - DESIGNED
// ===============================================================

if (!empty($websitePagesDesigned)) {
    $doc->addHeading('Website Pages - Designed');

    $rows = [];
    $sr = 1;
    foreach ($websitePagesDesigned as $row) {
        $rows[] = [(string)$sr++, $row['page_name'] ?? '-'];
    }

    $doc->addTable(['Sr No', 'Page'], $rows);
}


// ===============================================================
// WEBSITE PAGES - DEVELOPED (with live links)
// ===============================================================

if (!empty($websitePageLinks)) {
    $doc->addHeading('Website Pages - Developed');

    $rows = [];
    $sr = 1;
    foreach ($websitePageLinks as $row) {
        $rows[] = [(string)$sr++, $row['page_name'] ?? '-', ($row['page_link'] ?? '') !== '' ? $row['page_link'] : '-'];
    }

    $doc->addTable(['Sr. No.', 'Page', 'Page Link'], $rows, [1, 3, 4]);
}


// ===============================================================
// DASHBOARD PAGES - DESIGNED
// ===============================================================

if (!empty($dashboardDesigned)) {
    $doc->addHeading('Dashboard Pages - Designed');

    $rows = [];
    $sr = 1;
    foreach ($dashboardDesigned as $row) {
        $rows[] = [(string)$sr++, $row['page_name'] ?? '-'];
    }

    $doc->addTable(['Sr. No.', 'Page'], $rows);
}


// ===============================================================
// DASHBOARD PAGES - DEVELOPED (with live links)
// ===============================================================

if (!empty($dashboardPageLinks)) {
    $doc->addHeading('Dashboard Pages - Developed');

    $rows = [];
    $sr = 1;
    foreach ($dashboardPageLinks as $row) {
        $rows[] = [(string)$sr++, $row['page_name'] ?? '-', ($row['page_link'] ?? '') !== '' ? $row['page_link'] : '-'];
    }

    $doc->addTable(['Sr. No.', 'Page', 'Page Link'], $rows, [1, 3, 4]);
}


// ===============================================================
// APP PAGES - DESIGNED
// ===============================================================

if (!empty($appPagesDesigned)) {
    $doc->addHeading('App Pages - Designed');

    $rows = [];
    $sr = 1;
    foreach ($appPagesDesigned as $row) {
        $rows[] = [(string)$sr++, $row['page_name'] ?? '-'];
    }

    $doc->addTable(['Sr No', 'Screen'], $rows);
}


// ===============================================================
// APP PAGES - DEVELOPED (with live links)
// ===============================================================

if (!empty($appPageLinks)) {
    $doc->addHeading('App Pages - Developed');

    $rows = [];
    $sr = 1;
    foreach ($appPageLinks as $row) {
        $rows[] = [(string)$sr++, $row['page_name'] ?? '-', ($row['page_link'] ?? '') !== '' ? $row['page_link'] : '-'];
    }

    $doc->addTable(['Sr. No.', 'Screen', 'Page Link'], $rows, [1, 3, 4]);
}


// ===============================================================
// WEBSITE - OPERATIONS LIST
// ===============================================================

if (!empty($websiteOps)) {
    $doc->addHeading('Website - Operations List');

    $rows = [];
    $sr = 1;
    foreach ($websiteOps as $row) {
        $rows[] = [(string)$sr++, $row['section'] ?? '-', $row['sub_section'] ?? '-', $row['operation'] ?? '-'];
    }

    $doc->addTable(['Sr no', 'Section', 'Sub-section', 'Operation'], $rows, [1, 2, 2, 4]);
}


// ===============================================================
// APP - OPERATIONS LIST
// ===============================================================

if (!empty($appOps)) {
    $doc->addHeading('App - Operations List');

    $rows = [];
    $sr = 1;
    foreach ($appOps as $row) {
        $rows[] = [(string)$sr++, $row['screen'] ?? '-', $row['section'] ?? '-', $row['operation'] ?? '-'];
    }

    $doc->addTable(['Sr No', 'Screen', 'Section', 'Operations'], $rows, [1, 2, 2, 4]);
}


// ===============================================================
// DATABASE TABLES
// ===============================================================

if (!empty($databaseTables)) {
    $doc->addHeading('Database tables');

    $rows = [];
    $sr = 1;
    foreach ($databaseTables as $row) {
        $rows[] = [(string)$sr++, $row['table_name'] ?? '-'];
    }

    $doc->addTable(['Sr No', 'Table Name'], $rows);
}


// ===============================================================
// API CREATION AND INTEGRATION
// ===============================================================

if (!empty($apiList)) {
    $doc->addHeading('API Creation and Integration');

    $rows = [];
    $sr = 1;
    foreach ($apiList as $row) {
        $rows[] = [
            (string)$sr++,
            $row['api_name'] ?? '-',
            !empty($row['created_flag']) ? 'Created' : 'Pending',
            !empty($row['integrated_flag']) ? 'Integrated' : 'Pending',
            $row['status'] ?? '-',
        ];
    }

    $doc->addTable(['Sr. No.', 'API Name', 'API Creation', 'API Integration', 'Status'], $rows);
}


// ===============================================================
// PROJECT OVERVIEW
// ===============================================================

$doc->addHeading('Project Overview');

$doc->addKeyValueTable([

    'Project Name' =>
        $project['project_name'] ?? '-',

    'Client' =>
        $clientOrg,

    'Status' =>
        $project['status'] ?? '-',

    'Progress' =>
        ((int)($project['progress'] ?? 0)) . '%',

    'Start Date' =>
        fmt_date($project['start_date'] ?? null),

    'End Date' =>
        fmt_date($project['end_date'] ?? null),

]);

$description = trim(
    $project['description'] ?? ''
);

if ($description !== '') {
    $doc->addParagraph($description);
}


// ===============================================================
// APP SCREENS
// ===============================================================

$doc->addHeading('App Screens', 2);

$rows = [];
$sr = 1;

foreach ($appWork as $row) {

    $rows[] = [
        (string)$sr++,
        $row['screen_name'] ?? '-',
        $row['status'] ?? '-',
        fmt_date($row['created_date'] ?? null),
        fmt_date($row['end_date'] ?? null)
    ];
}

$doc->addTable(
    ['Sr. No.', 'Screen Name', 'Status', 'Created', 'End Date'],
    $rows
);


// ===============================================================
// DASHBOARD PAGES
// ===============================================================

$doc->addHeading('Dashboard Pages', 2);

$rows = [];
$sr = 1;

foreach ($dashboardWork as $row) {

    $rows[] = [
        (string)$sr++,
        $row['page_name'] ?? '-',
        $row['status'] ?? '-',
        fmt_date($row['created_date'] ?? null),
        fmt_date($row['end_date'] ?? null)
    ];
}

$doc->addTable(
    ['Sr. No.', 'Page Name', 'Status', 'Created', 'End Date'],
    $rows
);


// ===============================================================
// WEBSITE PAGES
// ===============================================================

$doc->addHeading('Website Pages', 2);

$rows = [];
$sr = 1;

foreach ($websiteWork as $row) {

    $rows[] = [
        (string)$sr++,
        $row['page_name'] ?? '-',
        $row['status'] ?? '-',
        fmt_date($row['created_date'] ?? null),
        fmt_date($row['end_date'] ?? null)
    ];
}

$doc->addTable(
    ['Sr. No.', 'Page Name', 'Status', 'Created', 'End Date'],
    $rows
);


// ===============================================================
// TECHNOLOGY STACK
// ===============================================================

if (!empty($technologies)) {

    $doc->addHeading('Technology Stack');

    $techRows = [];
    $sr = 1;

    foreach ($technologies as $technology) {

        $techRows[] = [
            (string)$sr++,
            $technology['tech_type'] ?? '-',
            $technology['name'] ?? '-',
            $technology['version'] ?? '-'
        ];
    }

    $doc->addTable(
        ['Sr. No.', 'Type', 'Name', 'Version'],
        $techRows
    );
}


// ===============================================================
// REPORT HISTORY
// ===============================================================

if (!empty($allReports)) {

    $doc->addHeading('Report History');

    $rows = [];
    $sr = 1;

    // Show most recent first
    foreach (array_reverse($allReports) as $row) {

        $name = $row['report_name'] ?? '-';

        if (
            $report &&
            isset($row['id']) &&
            $row['id'] == $report['id']
        ) {
            $name .= '  (this report)';
        }

        $rows[] = [
            (string)$sr++,
            $name,
            $row['report_type'] ?? '-',
            fmt_date($row['report_date'] ?? null)
        ];
    }

    $doc->addTable(
        ['Sr. No.', 'Report Name', 'Type', 'Date'],
        $rows
    );
}


// ===============================================================
// CLOSING
// (uses the custom text saved via edit_report.php, if any)
// ===============================================================

$autoClosing = 'Once again, we extend our heartfelt gratitude for your trust and ' .
    'continued collaboration. We look forward to your prompt attention ' .
    'to this matter and to serving you in the future.' . "\n\n" .
    'Thank you for choosing ' . COMPANY_NAME . '.';

$closingText = trim($report['custom_closing'] ?? '') !== ''
    ? $report['custom_closing']
    : $autoClosing;

if ($report) {
    $doc->addTextField('custom_closing', $closingText, 4, false, 11);
} else {
    $doc->addParagraph($closingText);
}

$signatureLines = [
    'Thank you & Regards',
    $_SESSION['user_name'] ?? '',
    COMPANY_NAME,
];

if (COMPANY_PHONE !== '') {
    $signatureLines[] = COMPANY_PHONE;
}

if (COMPANY_EMAIL !== '') {
    $signatureLines[] = COMPANY_EMAIL;
}

$doc->addSignature($signatureLines);


// ===============================================================
// OUTPUT PDF
// ===============================================================

$fileBase = preg_replace(
    '/[^A-Za-z0-9]+/',
    '_',
    $report
        ? ($report['report_name'] ?? 'project_report')
        : ($project['project_name'] ?? 'project_report')
);

$fileBase = trim($fileBase, '_');

if (!$fileBase) {
    $fileBase = 'project_report';
}

$doc->output(
    $fileBase . '.pdf',
    $view_mode
);
