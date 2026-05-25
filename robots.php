<?php
require_once __DIR__ . '/config/jwt.php';

header('Content-Type: text/plain; charset=UTF-8');

$baseUrl = rtrim(SITE_URL, '/');
echo "User-agent: *\n";
echo "Allow: /\n";
echo "Disallow: /admin/\n";
echo "Disallow: /api/\n";
echo "Disallow: /auth/\n";
echo "Disallow: /config/\n";
echo "Disallow: /customer/\n";
echo "Disallow: /database/\n";
echo "Disallow: /lib/\n";
echo "Disallow: /scratch/\n";
echo "\n";
echo "Sitemap: {$baseUrl}/sitemap.xml\n";
