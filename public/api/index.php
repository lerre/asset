<?php

define('WWW_ROOT', dirname(dirname(dirname(__DIR__))));
define('MyAPP_ROOT', WWW_ROOT . DIRECTORY_SEPARATOR . 'asset');
define('MyPHP_ROOT', WWW_ROOT . DIRECTORY_SEPARATOR . 'assetMy');
define('CONTROLLER_PREFIX', '\MyAPP\Controller\Api');

//define('TPL_PATH',   MyAPP_PATH . '/templates');
//define('ASSETS',       WWW_ROOT . '/assets');
//define('ADMIN_STATIC', WWW_ROOT . '/public/admin/static');

// 开启错误提醒
ini_set('display_errors', 1);
date_default_timezone_set('PRC');

require_once MyAPP_ROOT . '/utils/WxSpAes/WxSpAes.php';
require_once MyPHP_ROOT . '/My.php';

$MyApp = new My\Application();
$MyApp->init();

/**
 * 获取项目根路径
 * @param string $path
 * @return string
 */
function getRootPath($path = '')
{
    $rootPath = rtrim(MyAPP_ROOT, '/') . DIRECTORY_SEPARATOR;
    if ($path) {
        $path = trim($path, '/');
    }
    $rootPath .= $path;
    return $rootPath;
}
