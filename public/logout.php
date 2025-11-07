<?php
require_once 'bootstrap.php';

Auth::logout();
redirect('login.php');
