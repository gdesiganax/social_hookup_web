<?php
require dirname(__DIR__) . '/app/bootstrap.php';

require_admin();
require_post();

$siteName = text_input('site_name', 120);
$supportEmail = filter_var($_POST['support_email'] ?? '', FILTER_VALIDATE_EMAIL);
$defaultStatus = in_array(($_POST['default_user_status'] ?? ''), ['active', 'inactive'], true)
    ? (string) $_POST['default_user_status']
    : 'active';
$maintenanceMode = isset($_POST['maintenance_mode']) ? '1' : '0';

if ($siteName === '' || !$supportEmail) {
    flash('danger', 'Please enter a valid site name and support email.');
    redirect('admin/settings.php');
}

set_setting('site_name', $siteName);
set_setting('support_email', (string) $supportEmail);
set_setting('default_user_status', $defaultStatus);
set_setting('maintenance_mode', $maintenanceMode);

flash('success', 'Settings saved.');
redirect('admin/settings.php');
