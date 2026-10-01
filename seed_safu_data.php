<?php
require_once 'db.php';
$project_id = 1; // CHANGE THIS to your SPMES Mandal project ID

// 1. Team Members
$team = [
    ['Hussain Ambawala', 'Sr. Web Developer'],
    ['Imran Shaikh', 'Sr. Front-end Developer'],
    ['Komal Pawar', 'Sr. Software Tester'],
    ['Pratik Narwadkar', 'Project Manager']
];
foreach ($team as $t) {
    $conn->query("INSERT INTO project_team_members (project_id, member_name, role) VALUES ($project_id, '{$t[0]}', '{$t[1]}')");
}

// 2. Figma Design Work (32 items)
$figma = ["home page","donation details","donation receipt","project grid","project details","donation main page","donation form","about us","contact us","Internship/Volunteer","Career","digital documents","Associates","blog list","blog details","team","team details","quality policy","awards-Felicitation Of Change Makers","awards-Awards Received By SPMESM","event details","testimonials","Event-registration","Event Registration Successful","Donation Successful","Donation failed","Donation Campaigns","Donation Campaign Details","Design new image for Why choose SPMESM section","Header Design 4","Donation Campaign 1","Donation Campaign 4"];
foreach ($figma as $f) {
    $conn->query("INSERT INTO figma_design_work (project_id, page_name) VALUES ($project_id, '$f')");
}

// 3. Website Pages (29 items)
$webPages = [
    ["Home","https://spmesmandal.org/"],["Donate now","https://spmesmandal.org/donate"],["Donate now:- donate online","https://spmesmandal.org/donate/now"],["Documents","https://spmesmandal.org/documents"],["Partners in development","https://spmesmandal.org/partners-in-development"],["Blogs","https://spmesmandal.org/blogs"],["Blogs details","https://spmesmandal.org/blogs/Friday-Focus-By-SPMESM"],["Newsletter","https://spmesmandal.org/newsletter"],["About us","https://spmesmandal.org/about-us"],["Teams","https://spmesmandal.org/teams"],["Awards & accolades","https://spmesmandal.org/awards-and-accolades"],["Projects","https://spmesmandal.org/projects"],["Projects details","https://spmesmandal.org/projects/Project-Swati"],["Events","https://spmesmandal.org/events"],["Events details","https://spmesmandal.org/events/-Arunodaya-Community-Mental-Health-Symposium"],["Contact us","https://spmesmandal.org/contact-us"],["Testimonials","https://spmesmandal.org/testimonials"],["Campaigns","https://spmesmandal.org/donation-campaigns"],["Volunteer","https://spmesmandal.org/volunteer"],["Gallery","https://spmesmandal.org/gallery"],["Reports","https://spmesmandal.org/reports"],["Others","https://spmesmandal.org/awards-and-accolades/felicitati on"],["News media","https://spmesmandal.org/news-media"],["New media details","https://spmesmandal.org/news-media/-"],["Story of week details","https://spmesmandal.org/story/Symposium-On-Community-Mental-Health"],["Internship/Volunteer","https://spmesmandal.org/volunteer-internship"],["Career","https://spmesmandal.org/careers"],["FAQ","https://spmesmandal.org/faq"],["Donation campaign detail page","https://spmesmandal.org/donation-campaigns/tejaswini-mahila-vikas-prakalp-flight-of-dreams"]
];
foreach ($webPages as $w) {
    $conn->query("INSERT INTO website_pages (project_id, page_name, page_link) VALUES ($project_id, '{$w[0]}', '{$w[1]}')");
}

// 4. Dashboard Pages Designed (68 items) & Developed (68 items with links)
$dashPages = ["Login","Dashboard screen","Banner slider","Add banner slider","Edit banner slider","Story of the week","Add story of the week","Edit story of the week","Social impact","Edit social impact","Verticals","Add verticals","Edit verticals","Projects","Add projects","Edit project","Website users","Add web user","Edit web user","Admins","Add users","Edit user","Donations","Donor details","Refund donation","Add donations","Edit donation","My profile","Testimonials","Add testimonials","Edit testimonials","Documents","Add document","Edit document","Blogs/success stories","Add Blogs/success stories","Edit blogs/success stories","Newsletter","Add newsletter","Contact us","Teams","Add team member","Edit team member","Events","Add events","Edit event","Awards","Add awards","Edit awards","News media","Add news media","Edit news media","Volunteers","Collaborates","Partners in development","Add Partners in development","Edit Partner's in development","FAQ","Add FAQ","Edit FAQ","Donation receipt logs","Gallery","Add gallery","Edit gallery image","Macro impact","Add macro impact","Edit macro impact"];

$dashLinks = ["https://admin.spmesmandal.org/login","https://admin.spmesmandal.org/","https://admin.spmesmandal.org/home/slider/list","https://admin.spmesmandal.org/home/slider/add-slider","https://admin.spmesmandal.org/home/slider/edit/8","https://admin.spmesmandal.org/home/story/list","https://admin.spmesmandal.org/home/story/add-story","https://admin.spmesmandal.org/home/story/edit/5","https://admin.spmesmandal.org/home/social-impact/list","https://admin.spmesmandal.org/home/social-impact/add-social-impact","https://admin.spmesmandal.org/focus-area/list","https://admin.spmesmandal.org/focus-area/add-focus-area","https://admin.spmesmandal.org/focus-area/edit/8","https://admin.spmesmandal.org/project/list","https://admin.spmesmandal.org/project/add-project","https://admin.spmesmandal.org/project/edit/124","https://admin.spmesmandal.org/web-users/list","https://admin.spmesmandal.org/web-users/add-web-user","https://admin.spmesmandal.org/web-users/edit/8","https://admin.spmesmandal.org/users/list","https://admin.spmesmandal.org/users/add-user","https://admin.spmesmandal.org/users/edit/2","https://admin.spmesmandal.org/donation/list","https://admin.spmesmandal.org/donation/detail/1","https://admin.spmesmandal.org/donation/refund/1","https://admin.spmesmandal.org/donation/add-donation","https://admin.spmesmandal.org/donation/edit/1","https://admin.spmesmandal.org/profile","https://admin.spmesmandal.org/testimonials/list","https://admin.spmesmandal.org/testimonials/add-testimonial","https://admin.spmesmandal.org/testimonials/edit/2","https://admin.spmesmandal.org/digital-documents/list","https://admin.spmesmandal.org/digital-documents/add-digital-document","https://admin.spmesmandal.org/digital-documents/edit/7","https://admin.spmesmandal.org/blog/list","https://admin.spmesmandal.org/blog/add-blog","https://admin.spmesmandal.org/blog/edit/52","https://admin.spmesmandal.org/newsletter/list","https://admin.spmesmandal.org/newsletter/add-newsletter","https://admin.spmesmandal.org/contact/list","https://admin.spmesmandal.org/team/list","https://admin.spmesmandal.org/team/add-team","https://admin.spmesmandal.org/team/edit/25","https://admin.spmesmandal.org/event/list","https://admin.spmesmandal.org/event/add-event","https://admin.spmesmandal.org/event/edit/1","https://admin.spmesmandal.org/awards/list","https://admin.spmesmandal.org/awards/add-awards","https://admin.spmesmandal.org/awards/edit/16","https://admin.spmesmandal.org/news_media/list","https://admin.spmesmandal.org/news_media/add-news_media","https://admin.spmesmandal.org/news_media/edit/4","https://admin.spmesmandal.org/volunteers/list","https://admin.spmesmandal.org/collaborates/list","https://admin.spmesmandal.org/associates/list","https://admin.spmesmandal.org/associates/add-associate","https://admin.spmesmandal.org/associates/edit/2","https://admin.spmesmandal.org/faq/list","https://admin.spmesmandal.org/faq/add-faq","https://admin.spmesmandal.org/faq/edit/4","https://admin.spmesmandal.org/donation_reciept/donation-receipt-list","https://admin.spmesmandal.org/gallery/list","https://admin.spmesmandal.org/gallery/add-gallery","https://admin.spmesmandal.org/gallery/edit-gallery","https://admin.spmesmandal.org/associates/macro-impact/list","https://admin.spmesmandal.org/associates/macro-impact/add-macro-impact","https://admin.spmesmandal.org/associates/macro-impact/edit/4"];

for ($i=0; $i < count($dashPages); $i++) {
    $conn->query("INSERT INTO dashboard_pages_designed (project_id, page_name) VALUES ($project_id, '{$dashPages[$i]}')");
    $link = $dashLinks[$i] ?? '';
    $conn->query("INSERT INTO dashboard_pages_developed (project_id, page_name, page_link) VALUES ($project_id, '{$dashPages[$i]}', '$link')");
}

// 5. Database Tables (28 items)
$dbTables = ["activity_log","awards","blogs","collaborates","contact_us","country","digital_documents","donations","donation_status","events","event_registrations","failed_jobs","focus_area","home_slider","identification","jobs","migrations","newsletter","news_media","organization","password_resets","payment_mode","payment_types","donation campaign","gallery","volunteer","associates","macro_impact"];
foreach ($dbTables as $t) {
    $conn->query("INSERT INTO database_tables (project_id, table_name) VALUES ($project_id, '$t')");
}

// 6. APIs (42 items)
$apis = ["login","countries","registration","forgot_password","get home data","get user","logout","get all donations","projects","get all projects","get project by title","focus area","get_top_5_testimonials","get_all_testimonials","get_all_documents","get_all_blogs","get_blog","get_newsletter","create_contact","get_team","get_team_member_by_name","get_all_events","get_event","register_for_event","get_event_registration","get_all_awards","get_all_news","get_news","get_all_success_storie","get_email_with_token","reset password","register as collaborate","get all associate","change password","update profile","verify user by email","donation details","register as volunteer","fetch payment","get gallery","payment checkout","get_all_macro_impacts"];
foreach ($apis as $a) {
    $conn->query("INSERT INTO api_integrations (project_id, api_name) VALUES ($project_id, '$a')");
}

echo "Data seeded successfully! You can now generate the exact report.";