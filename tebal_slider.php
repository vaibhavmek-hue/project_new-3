<?php require_once 'common/auth_check.php'; include 'db.php'; ?>
<!DOCTYPE html>

<!-- =========================================================
* Sneat - Bootstrap 5 HTML Admin Template - Pro | v1.0.0
==============================================================

* Product Page: https://themeselection.com/products/sneat-bootstrap-html-admin-template/
* Created by: ThemeSelection
* License: You must have a valid license purchased in order to legally use the theme for your project.
* Copyright ThemeSelection (https://themeselection.com)

=========================================================
 -->
<!-- beautify ignore:start -->
<html
  lang="en"
  class="light-style layout-menu-fixed"
  dir="ltr"
  data-theme="theme-default"
  data-assets-path="./assets/"
  data-template="vertical-menu-template-free"
>
  <?php include 'common/header.php'; ?>


  <body>
    <!-- Layout wrapper -->
    <div class="layout-wrapper layout-content-navbar">
      <div class="layout-container">
        <!-- Menu -->

         <?php include 'common/sidebar.php'; ?>

        <!-- / Menu -->

        <!-- Layout container -->
        <div class="layout-page">
          <!-- Navbar -->

        <?php include 'common/navbar.php'; ?>
          <!-- / Navbar -->

          
  <!-- Content wrapper -->
          <div class="content-wrapper">
            <!-- Content -->

            <div class="container-xxl flex-grow-1 container-p-y">
              <h4 class="fw-bold py-3 mb-4"><span class="text-muted fw-light">Tables /</span> Slider Tables</h4>

              <!-- Basic Bootstrap Table -->
              <div class="card">
                <div class="row card-header">
                  <div class="col-md-6">
                                   <h5 class="card-header">Slider Table </h5>
                  </div>
                   <div class="col-md-6 text-end">
                    <a class="btn btn-primary" href="add_slider.php"
                      ><i class="bx bx-plus me-1"></i> Add Slider</a
                    >
                  </div>
                </div>
                <div class="table-responsive text-nowrap">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Sr No</th>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody class="table-border-bottom-0">
                      <?php
                      $result = $conn->query("SELECT * FROM projects ORDER BY created_at DESC");
                      $sr_no = 1;
                      if ($result && $result->num_rows > 0):
                          while ($row = $result->fetch_assoc()):
                      ?>
                      <tr>
                        <td><?php echo $sr_no++; ?></td>
                        <td><i class="fab fa-angular fa-lg text-danger me-3"></i> <strong><?php echo htmlspecialchars($row['project_name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($row['client_name']); ?></td>
                        <td>
                          <ul class="list-unstyled users-list m-0 avatar-group d-flex align-items-center">
                            <li>
                              <?php echo (int)$row['num_users']; ?> users
                            </li>
                          </ul>
                        </td>
                        <td><span class="badge bg-label-primary me-1"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        <td>
                          <a class="btn btn-primary" href="edit_slider.php?id=<?php echo $row['id']; ?>"
                                ><i class="bx bx-edit-alt me-1"></i> Edit</a
                              >
                              <a class="btn btn-danger" href="#;"
                                ><i class="bx bx-trash me-1"></i> Delete</a>         
                                             </td>
                      </tr>
                      <?php
                          endwhile;
                      else:
                      ?>
                      <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No projects found.</td>
                      </tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </div>
              </div>
              <!--/ Basic Bootstrap Table -->

         

          </div>
          <!-- Content wrapper -->



          
            <?php include 'common/footer.php'; ?>
        </div>
        <!-- / Layout page -->
      </div>
    </div>
    <!-- / Layout wrapper -->
  </body>
</html>
