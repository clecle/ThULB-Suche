<?php

if (in_array(@$_SERVER['REMOTE_ADDR'], array('127.0.0.1', '::1'))) {
    opcache_reset();
    echo json_encode(array('success' => true));
} else {
    header("HTTP/1.1 401 Unauthorized");
    die();
}
