<?php
// 404.php — نمایش صفحه ۴۰۴ سفارشی (برای هاست Apache)
http_response_code(404);
header('HTTP/1.0 404 Not Found');
readfile(__DIR__ . '/404.html');
