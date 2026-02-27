<?php
require_once __DIR__ . '/config/config.php';
logoutUser();
redirect('/index.php', 'Logged out.', 'success');

