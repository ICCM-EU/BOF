<?php

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require __DIR__.'/../classes/Admin.php';

$app->get('/admin', 'ICCM\BOF\Admin:showAdminView')->setName('admin');

$app->post('/admin', 'ICCM\BOF\Admin:update_config')->setName('admin_config');

?>
