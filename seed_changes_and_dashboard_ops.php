<?php
/**
 * seed_changes_and_dashboard_ops.php
 * ------------------------------------
 * One-time seed script: inserts the "Changes/Suggestions/Modifications"
 * log (30 rows) and the "Dashboard - Operations List" (92 rows) for the
 * SPMES Mandal project, matching the CURRENT schema used by
 * download_report.php (changes_log / dashboard_operations).
 *
 * This is the data seed_safu_data.php was missing — those two tables
 * were never populated before, which is why both sections were not
 * showing up in the generated report regardless of section order.
 *
 * USAGE:
 *   1. Change $project_id below to your SPMES Mandal project's real ID.
 *   2. Run this file once in the browser (or via CLI: php seed_changes_and_dashboard_ops.php).
 *   3. Re-generate the report — both sections will now appear.
 *
 * Safe to re-run: it clears any existing rows for this project in
 * these two tables first, so running it twice won't duplicate data.
 */

require_once __DIR__ . '/db.php';

$project_id = 1; // <-- CHANGE THIS to your SPMES Mandal project ID


// ===============================================================
// CLEAR EXISTING ROWS FOR THIS PROJECT (safe re-run)
// ===============================================================

$conn->query("DELETE FROM changes_log WHERE project_id = $project_id");
$conn->query("DELETE FROM dashboard_operations WHERE project_id = $project_id");


// ===============================================================
// 1. CHANGES / SUGGESTIONS / MODIFICATIONS  (30 rows)
// Columns: change_date, page, section, note
// ===============================================================

$changes = [
    ['2025-05-08', 'Design changes',          'Donation Receipt',        'Footer text font size reduce and replace old text with new'],
    ['2025-05-08', 'Design changes',          'Donation Receipt',        'On header part, where the website link is present need to added QR code instead of website URl'],
    ['2025-05-08', 'Design changes',          'Donation Receipt',        'All the content of donation receipt should be managed to display on half A4 size paper'],
    ['2025-05-08', 'Website Changes',         'Donation Receipt',        'Replaced old background image of Header and footer with updated one'],
    ['2025-05-08', 'Dashboard Side Change',   'Donation Receipt',        'Replaced old background image of Header and footer with updated one'],
    ['2025-05-08', 'Website Changes',         'Donation Receipt',        'Reduce font size and spacing between all the fields'],
    ['2025-05-08', 'Dashboard Side Change',   'Donation Receipt',        'Reduce font size and spacing between all the fields'],
    ['2025-05-16', 'Design changes',          'Donation Form',           'Re-designed new donation form'],
    ['2025-05-16', 'Website Changes',         'Donation Form',           'In Identification type, remove below options: Voter ID, Passport, Driving license'],
    ['2025-05-16', 'Website Changes',         'Donation Receipt',        'Complete field of "PAN / Aadhar No. :" will be removed from donation receipt and now vertical will be displayed in full row'],
    ['2025-05-16', 'Dashboard Side Changes',  'Donation Receipt',        'Complete field of "PAN / Aadhar No. :" will be removed from donation receipt and now vertical will be displayed in full row'],
    ['2025-05-21', 'Website Changes',         'Donation Form',           'Whenever the user enters mobile number then below details will fetch in their respective form fields: First Name, Last Name, Email Id, Mobile Number, Full Address, Identification Type, Identification Number'],
    ['2025-05-21', 'Website Changes',         'Donation Form',           'Add below note above the form'],
    ['2025-05-21', 'Dashboard Side Changes',  'Donation Form',           'Create Get API for fetching data through the dashboard'],
    ['2026-04-15', 'Dashboard Side Changes',  'Donation Receipt',        'Update below 80G and 12A numbers on donation receipt: 80G - AADTS0790E25PN03 Valid Up to 2031-32; 12A - AADTS0790E25PN02 Valid Up to 2031-32'],
    ['2026-04-15', 'Website Side Changes',    'Donation Receipt',        'Update below 80G and 12A numbers on donation receipt: 80G - AADTS0790E25PN03 Valid Up to 2031-32; 12A - AADTS0790E25PN02 Valid Up to 2031-32'],
    ['2026-06-16', 'Website Side Changes',    'Contact Us',              "Change the old address with a new one shared by Rutuja ma'am and also change the Google map view link with the latest address."],
    ['2026-06-24', 'Website Changes',         'Partners In Development', 'Add a new static section named "Why Choose SPEMESM To Implement Your CSR?"'],
    ['2026-06-24', 'Design Side Work',        'Design New image',       'Create a new image for "Why Choose SPEMESM To Implement Your CSR?" new section'],
    ['2026-06-24', 'Website Changes',         'Partners In Development', 'Create new section for "Title:- Micro Impact" — this section consists of multiple blocks, each block containing a title and its count'],
    ['2026-06-24', 'Dashboard Side Changes',  'Partners In Development', 'Convert the "Partner\'s in development" tab on the sidebar into a dropdown and add 2 new sub-tabs: CSR Partners, Macro Impact'],
    ['2026-06-24', 'Dashboard Side Changes',  'Partners In Development', 'Created add/edit/delete functionality for the "Macro Impact" section. Form fields: Title, Count. "Add New" button added on the list page'],
    ['2026-06-24', 'Website Changes',         'Partners In Development', 'Create new static section for "Three Decades of Grassroots Experience — Creating CSR Impact through \'Design, Implementation, and Transformation\'"'],
    ['2026-06-26', 'Website Changes',         'Home Page',               'Case Study pop-up removed from home page'],
    ['2026-07-03', 'Website Changes',         'Footer',                  'Added the new link in website Footer section with caption "SHE-Box", link: https://shebox.wcd.gov.in/'],
    ['2026-07-09', 'Change in Direct Database','Donation Campaign table','Added amount Rs. 1073001 into "SPMESM - Bandhuta Nidhi" Donation Campaign'],
    ['2026-07-15', 'Design Side Work',        'Donation Campaign',       'Created 4 new designs for highlighting donation campaign on website'],
    ['2026-07-15', 'Design Side Work',        'Navigation menu',         'Created 4 new designs for navigation menu for design enhancement'],
    ['2026-07-15', 'Website Side work',       'Donation campaign',       'Client confirmed the design of option 1 and 3; developed and modified: 1) Donation form - added available donation campaigns (user can select a campaign to donate; selected campaign name is shown), 2) Home page - added a pop-up with a donation campaign vertical slider on the banner, with a close button'],
    ['2026-07-15', 'Website Side work',       'Navigation menu',         'Client confirmed the design of option-D; developed and modified all pages so that the breadcrumb design is changed across the site'],
];

$stmt = $conn->prepare("
    INSERT INTO changes_log (project_id, change_date, page, section, note, sort_order)
    VALUES (?, ?, ?, ?, ?, ?)
");

$sort = 1;
foreach ($changes as $row) {
    [$date, $page, $section, $note] = $row;
    $stmt->bind_param("issssi", $project_id, $date, $page, $section, $note, $sort);
    $stmt->execute();
    $sort++;
}
$stmt->close();

echo "Inserted " . count($changes) . " rows into changes_log.\n";


// ===============================================================
// 2. DASHBOARD - OPERATIONS LIST  (92 rows)
// Columns: page, section, operation
// (No explicit dates in the source list — using the report's own
// tracked dates below; adjust $opsDate if you want a different one.)
// ===============================================================

$opsDate = '2026-07-15';

$dashboardOps = [
    ['Login', 'username, password, login', 'Login into dashboard after entering valid credentials.'],
    ['Dashboard screen', 'Logo', 'It should refresh & redirect to the dashboard home screen.'],
    ['Dashboard', 'Sidebar icon', 'It should hide and unhide the sidebar fields.'],
    ['Profile', 'My profile', 'It should display previously inserted information and should be editable'],
    ['Banner Slider', 'Add banner slider', 'Add slider form should display and after adding details slider should be get added'],
    ['Banner Slider', 'Edit banner slider', 'Form should display with all the previously added details and it should be editable.'],
    ['Banner Slider', 'Delete banner slider', 'Banner slider should be deleted.'],
    ['Banner Slider', 'Search', 'As per search input result should display (Search functionality)'],
    ['Story of the week', 'Add story', 'Add story of week form should display and after adding details Story of the week should be get added'],
    ['Story of the week', 'Edit story', 'Form should display with all the previously added details and it should be editable.'],
    ['Story of the week', 'Delete story', 'Data should be deleted.'],
    ['Story of the week', 'Search', 'As per search input result should display (Search functionality)'],
    ['Social impact', 'Edit social impact', 'Form should display with all the previously added details and it should be editable.'],
    ['Social impact', 'Search', 'As per search input result should display (Search functionality)'],
    ['Verticals', 'Add Verticals', 'Add vertical form should display and after adding details vertical should be get added'],
    ['Verticals', 'Edit Verticals', 'Form should display with all the previously added details and it should be editable.'],
    ['Verticals', 'Delete Verticals', 'Data should be deleted.'],
    ['Verticals', 'Search', 'As per search input result should display (Search functionality)'],
    ['Projects', 'Add project', 'Add project form should display and after adding details project should be get added'],
    ['Projects', 'Search', 'As per search input result should display (Search functionality)'],
    ['Projects', 'Status and focus area', 'Sorting and search functionality.'],
    ['Website users', 'Add web user', 'Add web user form should display and after adding details web user should be get added'],
    ['Website users', 'Search', 'As per search input result should display (Search functionality)'],
    ['Website users', 'Edit', 'Form should display with all the previously added details and it should be editable.'],
    ['Website users', 'Delete', 'Data should be deleted.'],
    ['Admins', 'Add user', 'Add user form should display and after adding details user should be get added (As per added user, it should get login in dashboard as per privilege)'],
    ['Admins', 'Search', 'As per search input result should display (Search functionality)'],
    ['Admins', 'Edit admins', 'Form should display with all the previously added details and it should be editable.'],
    ['Admins', 'Delete', 'Data should be deleted.'],
    ['Donations', 'Data table', 'Donors list should display and click on any donor details should display.'],
    ['Donations', 'Add donation', 'Form should open and should be editable. Donations should be added.'],
    ['Donations', 'Search', 'Date, status and by donation field wise Search functionality'],
    ['Donations', 'Clear', 'The selected filter should get clear.'],
    ['Donations', 'Refund', 'Refund form should display & editable.'],
    ['Donations', 'Download receipt', 'Receipt with details should download.'],
    ['Donations', 'Edit donation', 'Form should display with all the previously added details and it should be editable.'],
    ['Donations', 'Delete', 'Data should be deleted from the list'],
    ['Testimonials', 'Add testimonials', 'Add testimonial form should display and after adding details testimonial should be get added'],
    ['Testimonials', 'Search', 'As per search input result should display (Search functionality)'],
    ['Testimonials', 'Edit', 'Form should display with all the previously added details and it should be editable.'],
    ['Testimonials', 'Delete', 'Data should be deleted.'],
    ['Documents', 'Add documents', 'Add document form should display and after adding details document should be get added'],
    ['Documents', 'Edit', 'Form should display with all the previously added details and it should be editable.'],
    ['Documents', 'Delete', 'Document should be deleted from the list'],
    ['Documents', 'PDF/Excel', 'PDF/excel should download'],
    ['Documents', 'Search', 'As per search input result should display (Search functionality)'],
    ['Blogs / Success Stories', 'Add blogs', 'Add blog form should display and after adding details blog should be get added'],
    ['Blogs / Success Stories', 'Edit', 'Form should display with all the previously added details and it should be editable.'],
    ['Blogs / Success Stories', 'Delete', 'Data should be deleted.'],
    ['Blogs / Success Stories', 'Search', 'As per search input result should display (Search functionality)'],
    ['Newsletter', 'Add newsletter', 'Add newsletter form should display and after adding details newsletter should be get added'],
    ['Newsletter', 'Search', 'As per search input result should display (Search functionality)'],
    ['Newsletter', 'Delete', 'Data should be deleted.'],
    ['Contact us', 'List', 'Contact list should display'],
    ['Contact us', 'Search', 'As per search input result should display (Search functionality)'],
    ['Teams', 'Add team member', 'Form should open and field should be editable'],
    ['Teams', 'Search', 'As per search input result should display (Search functionality)'],
    ['Teams', 'Delete', 'Data should be deleted.'],
    ['Teams', 'Edit', 'Form should display with all the previously added details and it should be editable.'],
    ['Events', 'Add events', 'Add Event form should display and after adding details Event should be get added'],
    ['Events', 'Search', 'As per search input result should display (Search functionality)'],
    ['Events', 'Edit', 'Form should display with all the previously added details and it should be editable.'],
    ['Events', 'Delete', 'Data should be deleted.'],
    ['Awards', 'Add awards', 'Add award form should display and after adding details award should be get added'],
    ['Awards', 'Search', 'As per search input result should display (Search functionality)'],
    ['Awards', 'Delete', 'Data should be deleted.'],
    ['Awards', 'Edit', 'Form should display with all the previously added details and it should be editable.'],
    ['News media', 'Add news media', 'Add News media form should display and after adding details News media should be get added'],
    ['News media', 'Delete', 'Data should be deleted.'],
    ['News media', 'Edit', 'Form should display with all the previously added details and it should be editable.'],
    ['News media', 'Search', 'As per search input result should display (Search functionality)'],
    ['Volunteers', 'Data table', 'List should display'],
    ['Volunteers', 'Search', 'As per search input result should display (Search functionality)'],
    ['Partners in development', 'Search', 'As per search input result should display (Search functionality)'],
    ['Partners in development', 'Add', 'Add Partners in development form should display and after adding details Partners in development should be get added'],
    ['Partners in development', 'Edit', 'Form should display with all the previously added details and it should be editable.'],
    ['Partners in development', 'Delete', 'Data should be deleted.'],
    ['Gallery', 'Add', 'After adding an image it should be added and display in the data table and in the website also.'],
    ['Gallery', 'Delete', 'Image should get deleted'],
    ['FAQ', 'Add FAQ', 'FAQ should be added as per added details and should display in the data table and in the website.'],
    ['FAQ', 'Edit', 'FAQ should get updated as per changes.'],
    ['FAQ', 'Delete', 'FAQ should get deleted from data table'],
    ['All fields data table', 'PDF/Excel', 'PDF & excel should be downloaded'],
    ['Donations campaign', 'Data table', 'Donation campaign list should display and click on any campaign details of campaign donors should display'],
    ['Donations campaign', 'Add donation campaign', 'Form should open and should be editable. Donation campaigns should be added.'],
    ['Donations campaign', 'Search', 'Date, status and by donation field wise Search functionality'],
    ['Donations campaign', 'Download receipt', 'As per donation details receipt should get downloaded.'],
    ['Donations campaign', 'Send receipt via mail', 'Receipt should be sent to the user on their registered email'],
    ['Partners In Development', 'Sidebar Dropdown', 'Create sidebar dropdown with below 2 links: 1. Macro Impact, 2. CSR Partners'],
    ['Macro Impact', 'List', 'Show all the added Macro Impact titles and counters in the datatable with action buttons. After clicking the delete action, the item is deleted. After clicking the edit action, admin can edit and update the data.'],
    ['Macro Impact', 'Add Macro Impact', 'Clicking "Add Macro Impact" opens the form; after adding details and clicking submit, the form is submitted with the given details.'],
    ['Macro Impact', 'Edit Macro Impact', 'Clicking edit opens the form and fetches the data; clicking submit updates the details. A cancel button is also available — clicking it returns to the list page without updating the details.'],
];

$stmt = $conn->prepare("
    INSERT INTO dashboard_operations (project_id, page, section, operation, created_date, sort_order)
    VALUES (?, ?, ?, ?, ?, ?)
");

$sort = 1;
foreach ($dashboardOps as $row) {
    [$page, $section, $operation] = $row;
    $stmt->bind_param("issssi", $project_id, $page, $section, $operation, $opsDate, $sort);
    $stmt->execute();
    $sort++;
}
$stmt->close();

echo "Inserted " . count($dashboardOps) . " rows into dashboard_operations.\n";
echo "Done. Re-generate the report to see both sections.\n";
