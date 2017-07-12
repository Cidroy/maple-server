<?php

$cd = str_replace(ROOT,"",__DIR__)."/";

define('INC',$cd.'include/');
define('CNT','maple-content/');
define('ADMIN',$cd.'admin/');
define('LOG',$cd.'~$Logs/');
define('DATA','data/');
define('PLG',CNT.'plugin/');
define('THEME',CNT.'themes/');
define('CD',$cd);

require_once ROOT.INC.'class-file.php';
require_once ROOT.INC.'class-url.php';
require_once ROOT.INC.'error-handler.php';
require_once ROOT.INC.'class-template.php';
require_once ROOT.INC.'class-cache.php';
require_once ROOT.INC.'class-time.php';

require_once __DIR__.'/config.php';
require_once ROOT.INC.'error-handler.php';
require_once ROOT.INC.'functions.php';
require_once ROOT.INC.'db.php';
require_once ROOT.INC.'class-security.php';
require_once ROOT.INC.'Maple.php';
require_once ROOT.INC.'class-route.php';
require_once ROOT.INC.'class-session.php';
?>
