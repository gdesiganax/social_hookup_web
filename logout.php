<?php
require __DIR__ . '/app/bootstrap.php';

require_post();
logout_user();
redirect('login.php');
