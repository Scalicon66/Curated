<?php
require_once __DIR__ . '/lib.php';

user_logout();
flash_set('You have been securely logged out.');
redirect('index.php');
