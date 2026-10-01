```php
<?php
// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

/*
|--------------------------------------------------------------------------
| Check whether any Project submenu page is active
|--------------------------------------------------------------------------
*/
$project_pages = [
    'allprojectdashboard.php',
    'websitework.php',
    'appwork.php',
    'dashboardwork.php'
];

$project_open = in_array($current_page, $project_pages);
?>

<aside id="layout-menu"
       class="layout-menu menu-vertical menu bg-menu-theme">

    <!-- =====================================================
         BRAND
    ====================================================== -->
    <div class="p-4 border-bottom border-secondary border-opacity-25">

        <a href="index.php"
           class="d-flex align-items-center gap-3 text-decoration-none">

            <div class="bg-primary text-white d-flex align-items-center justify-content-center rounded-3 flex-shrink-0"
                 style="width:32px;height:32px;">

                <i class="bx bx-layer fs-5"></i>

            </div>

            <span class="fs-5 fw-bold text-dark tracking-wide text-truncate">
                PWRG
            </span>

        </a>

    </div>


    <!-- =====================================================
         MENU SHADOW
    ====================================================== -->
    <div class="menu-inner-shadow"></div>


    <!-- =====================================================
         SIDEBAR MENU
    ====================================================== -->
    <ul class="menu-inner py-3 d-flex flex-column h-100 list-unstyled mb-0">


        <!-- =================================================
             DASHBOARD
        ================================================== -->
        <li class="menu-item
            <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">

            <a href="index.php" class="menu-link">

                <i class="menu-icon tf-icons bx bx-home-circle"></i>

                <div data-i18n="Analytics">
                    Dashboard
                </div>

            </a>

        </li>


        <!-- =================================================
             ALL PROJECT
        ================================================== -->
        <li class="menu-item pt-4
            <?php echo $project_open ? 'open' : ''; ?>">

            <!-- Main Dropdown Button -->
            <a href="javascript:void(0);"
               class="menu-link menu-toggle">

                <i class="menu-icon fa-solid fa-folder-open text-primary"></i>

                <div data-i18n="Projects">
                    All Project
                </div>

            </a>


            <!-- =============================================
                 PROJECT SUBMENU
            ============================================== -->
            <ul class="menu-sub">


                <!-- -----------------------------------------
                     ALL PROJECTS
                ------------------------------------------ -->
                <li class="menu-item pb-3
                    <?php echo ($current_page == 'allprojectdashboard.php') ? 'active' : ''; ?>">

                    <a href="allprojectdashboard.php"
                       class="menu-link">

                        <i class="menu-icon fa-solid fa-folder me-2"></i>

                        <div>
                            All Projects
                        </div>

                    </a>

                </li>


                <!-- -----------------------------------------
                     WEBSITE WORK
                ------------------------------------------ -->
                <li class="menu-item pb-3
                    <?php echo ($current_page == 'websitework.php') ? 'active' : ''; ?>">

                    <a href="websitework.php"
                       class="menu-link">

                        <i class="menu-icon fa-solid fa-globe me-2"></i>

                        <div>
                            Website Work
                        </div>

                    </a>

                </li>


                <!-- -----------------------------------------
                     APP WORK
                ------------------------------------------ -->
                <li class="menu-item pb-3
                    <?php echo ($current_page == 'appwork.php') ? 'active' : ''; ?>">

                    <a href="appwork.php"
                       class="menu-link">

                        <i class="menu-icon fa-solid fa-mobile-button me-2"></i>

                        <div>
                            App Work
                        </div>

                    </a>

                </li>


                <!-- -----------------------------------------
                     DASHBOARD WORK
                ------------------------------------------ -->
                <li class="menu-item pb-3
                    <?php echo ($current_page == 'dashboardwork.php') ? 'active' : ''; ?>">

                    <a href="dashboardwork.php"
                       class="menu-link">

                        <i class="menu-icon fa-solid fa-laptop me-2"></i>

                        <div>
                            Dashboard Work
                        </div>

                    </a>

                </li>

            </ul>

        </li>


        <!-- =================================================
             ALL CLIENTS
        ================================================== -->
        <li class="menu-item pt-4
            <?php echo ($current_page == 'allclient.php') ? 'active' : ''; ?>">

            <a href="allclient.php"
               class="menu-link">

                <i class="menu-icon fa-solid fa-id-badge"></i>

                <div data-i18n="Account">
                    All Clients
                </div>

            </a>

        </li>


        <!-- =================================================
             CLIENT DETAILS
             Disabled / Commented
        ================================================== -->

        <!--
        <li class="menu-item
            <?php echo ($current_page == 'clientsdetail.php') ? 'active' : ''; ?>">

            <a href="clientsdetail.php"
               class="menu-link">

                <i class="menu-icon fa-solid fa-circle-info"></i>

                <div data-i18n="Connections">
                    Clients Details
                </div>

            </a>

        </li>
        -->


        <!-- =================================================
             TECHNOLOGY
        ================================================== -->
        <li class="menu-item pt-4
            <?php echo ($current_page == 'tech.php') ? 'active' : ''; ?>">

            <a href="tech.php"
               class="menu-link">

                <i class="menu-icon fa-solid fa-microchip"></i>

                <div data-i18n="Misc">
                    Technology
                </div>

            </a>

        </li>


        <!-- =================================================
             API KEYS
        ================================================== -->
        <!-- <li class="menu-item pt-4
            <?php echo ($current_page == 'api_keys.php') ? 'active' : ''; ?>">

            <a href="api_keys.php"
               class="menu-link">

                <i class="menu-icon fa-solid fa-key"></i>

                <div data-i18n="Misc">
                    API Keys
                </div>

            </a>

        </li> -->


    </ul>

</aside>
