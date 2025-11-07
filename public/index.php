<?php
require_once 'bootstrap.php';

// Redirect to login if not authenticated
if (!Auth::check()) {
    redirect('login.php');
}

// Redirect to dashboard
redirect('dashboard.php');
