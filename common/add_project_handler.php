<?php
/**
 * Shared "Add Project" POST handler.
 *
 * Include this near the top of any page (before HTML output) that hosts
 * the Add Project modal from common/add_project_modal.php. On success it
 * redirects back to that SAME page (preserving its existing query string,
 * e.g. clientsdetail.php?id=5) with &added=1 appended, so each host page
 * can show its own "Project added!" toast.
 *
 * On validation failure it sets $project_form_error and falls through
 * (no redirect), so the host page can re-open the modal with that message.
 *
 * Requires: $conn (from db.php) already available.
 */

$project_form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_project'])) {
    // ---- Fields the user actually fills in ----
    $project_name  = trim($_POST['project_name'] ?? '');
    $client_id_raw = trim($_POST['client_id'] ?? '');
    $client_id     = ($client_id_raw !== '') ? (int) $client_id_raw : 0;
    // Projects.client_id is a nullable FK to clients.id. 0 is never a valid
    // client row, so it must be stored as NULL, not 0, or the FK constraint
    // (fk_projects_client) rejects the insert.
    $client_id_val = $client_id > 0 ? $client_id : null;
    $client_name   = '';
    if ($client_id > 0) {
        $cstmt = $conn->prepare("SELECT client_name FROM clients WHERE id = ?");
        $cstmt->bind_param('i', $client_id);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        if ($cres && $cres->num_rows > 0) {
            $client_name = $cres->fetch_assoc()['client_name'];
        }
        $cstmt->close();
    }
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $num_users   = (int) ($_POST['num_users'] ?? 0);
    $start_date  = trim($_POST['start_date'] ?? '');
    $end_date    = trim($_POST['end_date'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $status   = 'In Progress';
    $progress = 0;
    if ($start_date === '') {
        $start_date = date('Y-m-d');
    }
    $end_date_val = $end_date !== '' ? $end_date : null;

    if ($project_name === '') {
        $project_form_error = "Project Name is required.";
    } elseif ($phone !== '' && !preg_match('/^[0-9]{10}$/', $phone)) {
        $project_form_error = "Phone Number must be exactly 10 digits, numbers only.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO projects
                (project_name, client_id, client_name, email, phone, num_users, start_date, end_date, description, status, progress)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            'sisssissssi',
            $project_name,
            $client_id_val,
            $client_name,
            $email,
            $phone,
            $num_users,
            $start_date,
            $end_date_val,
            $description,
            $status,
            $progress
        );
        if ($stmt->execute()) {
            $new_project_id = $stmt->insert_id;
            $stmt->close();

            // ---- Save any Work rows added inline on the form ----
            $work_types = [
                'app'  => ['table' => 'app_work',      'name_field' => 'screen_name'],
                'dash' => ['table' => 'dashboard_work', 'name_field' => 'page_name'],
                'web'  => ['table' => 'website_work',   'name_field' => 'page_name'],
            ];

            foreach ($work_types as $prefix => $cfg) {
                $names     = $_POST[$prefix . '_name'] ?? [];
                $statuses  = $_POST[$prefix . '_status'] ?? [];
                $dates     = $_POST[$prefix . '_created_date'] ?? [];
                $end_dates = $_POST[$prefix . '_end_date'] ?? [];

                foreach ($names as $i => $work_name) {
                    $work_name = trim($work_name);
                    if ($work_name === '') {
                        continue;
                    }
                    $work_status   = trim($statuses[$i] ?? 'Pending');
                    $work_date     = trim($dates[$i] ?? '');
                    $work_date     = $work_date !== '' ? $work_date : date('Y-m-d');
                    $work_end_date = trim($end_dates[$i] ?? '');
                    $work_end_date = $work_end_date !== '' ? $work_end_date : null;

                    $sql = "INSERT INTO {$cfg['table']} ({$cfg['name_field']}, status, created_date, end_date, project_id) VALUES (?, ?, ?, ?, ?)";
                    $wstmt = $conn->prepare($sql);
                    $wstmt->bind_param('ssssi', $work_name, $work_status, $work_date, $work_end_date, $new_project_id);
                    $wstmt->execute();
                    $wstmt->close();
                }
            }

            // ---- Save selected technologies (multi-select dropdown) ----
            $tech_category_map = [
                'JavaScript' => 'Language', 'PHP' => 'Language', 'Python' => 'Language',
                'Java' => 'Language', 'TypeScript' => 'Language', 'C#' => 'Language',
                'HTML' => 'Frontend', 'CSS' => 'Frontend', 'React' => 'Frontend',
                'Vue.js' => 'Frontend', 'Angular' => 'Frontend', 'Bootstrap' => 'Frontend',
                'Tailwind CSS' => 'Frontend', 'jQuery' => 'Frontend',
                'Node.js' => 'Backend', 'Laravel' => 'Backend', 'Django' => 'Backend',
                'Express.js' => 'Backend', '.NET' => 'Backend', 'Spring Boot' => 'Backend',
                'MySQL' => 'Database', 'PostgreSQL' => 'Database', 'MongoDB' => 'Database',
                'SQLite' => 'Database', 'Firebase' => 'Database',
                'Git' => 'DevOps & Tools', 'Docker' => 'DevOps & Tools', 'AWS' => 'DevOps & Tools',
                'Nginx' => 'DevOps & Tools', 'Linux' => 'DevOps & Tools', 'GitHub Actions' => 'DevOps & Tools',
            ];
            $selected_technologies = $_POST['technologies'] ?? [];
            if (is_array($selected_technologies) && count($selected_technologies) > 0) {
                $tstmt = $conn->prepare("INSERT INTO technologies (tech_type, name, version, project_id) VALUES (?, ?, '', ?)");
                foreach ($selected_technologies as $tech_name) {
                    $tech_name = trim($tech_name);
                    if ($tech_name === '') {
                        continue;
                    }
                    $tech_type = $tech_category_map[$tech_name] ?? 'Other';
                    $tstmt->bind_param('ssi', $tech_type, $tech_name, $new_project_id);
                    $tstmt->execute();
                }
                $tstmt->close();
            }

            // Redirect back to whichever page hosted the modal, preserving
            // its existing query string (e.g. clientsdetail.php?id=5).
            $redirect_path = strtok($_SERVER['REQUEST_URI'], '?');
            $qs = $_GET;
            unset($qs['page']); // a fresh add makes pagination position irrelevant
            $qs['added'] = 1;
            header("Location: " . $redirect_path . '?' . http_build_query($qs));
            exit;
        } else {
            $project_form_error = "Error: " . $stmt->error;
            $stmt->close();
        }
    }
}
