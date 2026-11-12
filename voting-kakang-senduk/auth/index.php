<?php
require_once __DIR__ . '/../config/app.php';
if (current_user()) {
    redirect(current_user()['role'] === 'admin' ? '../admin/index.php' : '../index.php');
}
redirect('login.php');
