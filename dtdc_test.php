<?php
/**
 * DTDC API Configuration & Test Tool
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/dtdc.php';

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    die('Access Denied. Admin only.');
}

echo "<!DOCTYPE html>";
echo "<html><head><title>DTDC API Test</title>";
echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css' rel='stylesheet'>";
echo "<style>
    body { padding: 20px; background: #f0f2f9; }
    .container { max-width: 900px; }
    .card { border-radius: 12px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 20px; }
    .card-header { background: linear-gradient(135deg, #001A93 0%, #3B5BDB 100%); color: #fff; border-radius: 12px 12px 0 0; padding: 15px; }
    .status-ok { color: #22863a; }
    .status-error { color: #cb2431; }
    code { background: #f5f5f5; padding: 2px 6px; border-radius: 4px; }
</style>";
echo "</head><body>";
echo "<div class='container'>";

echo "<div class='card'>";
echo "<div class='card-header'><h5 style='margin:0;'>⚙️ DTDC API Configuration & Testing</h5></div>";
echo "<div class='card-body'>";

// Current configuration
echo "<h6 class='mt-4'>Current DTDC Credentials (lib/dtdc.php):</h6>";
echo "<table class='table table-sm table-bordered'>";
echo "<tr><td><strong>Username</strong></td><td><code>PL3537_trk_json</code> (DEMO)</td></tr>";
echo "<tr><td><strong>Password</strong></td><td><code>wafBo</code> (DEMO)</td></tr>";
echo "<tr><td><strong>API Key</strong></td><td><code>bbb8196c734d8487983936199e880072</code> (DEMO)</td></tr>";
echo "<tr><td><strong>Customer Code</strong></td><td><code>PL3537</code> (DEMO)</td></tr>";
echo "</table>";

echo "<div class='alert alert-warning mt-4'>";
echo "<strong>⚠️ These are DEMO credentials!</strong><br>";
echo "You need to replace them with your actual DTDC account details.<br>";
echo "Contact DTDC support to get your credentials.";
echo "</div>";

// Test button
if (isset($_POST['test'])) {
    echo "<hr>";
    echo "<h6>Test Results:</h6>";

    try {
        $dtdc = new DtdcClient();

        // Try a dummy AWB to test authentication
        $result = $dtdc->track('999999999999');

        if ($result['success']) {
            echo "<div class='alert alert-success'>";
            echo "<strong class='status-ok'>✅ DTDC API is working!</strong><br>";
            echo "Authentication successful. System can fetch tracking data.";
            echo "</div>";
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<strong class='status-error'>❌ API Error:</strong><br>";
            echo htmlspecialchars($result['error']);
            echo "</div>";
        }

    } catch (Exception $e) {
        echo "<div class='alert alert-danger'>";
        echo "<strong class='status-error'>❌ Exception:</strong><br>";
        echo htmlspecialchars($e->getMessage());
        echo "</div>";
    }
}

echo "<form method='POST' style='margin-top: 20px;'>";
echo "<button type='submit' name='test' value='1' class='btn btn-primary'>Test DTDC Connection</button>";
echo "</form>";

echo "<hr>";
echo "<h6 class='mt-4'>How to Configure DTDC Credentials:</h6>";
echo "<ol>";
echo "<li><strong>Get credentials from DTDC:</strong>";
echo "   <ul>";
echo "   <li>Login to your DTDC account portal</li>";
echo "   <li>Navigate to API Management / Integration Settings</li>";
echo "   <li>Generate/copy your: Username, Password, API Key, Customer Code</li>";
echo "   </ul>";
echo "</li>";
echo "<li><strong>Update lib/dtdc.php</strong> in the DtdcClient constructor (lines 25-28):</li>";
echo "</ol>";

echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 8px; margin: 20px 0; font-size: 12px;'>";
echo "<strong>Replace in lib/dtdc.php constructor:</strong><br>";
echo "<code>";
echo "public function __construct(array \$cfg = []) {<br>";
echo "&nbsp;&nbsp;\$this->username     = \$cfg['username']      ?? '<strong>YOUR_USERNAME</strong>';<br>";
echo "&nbsp;&nbsp;\$this->password     = \$cfg['password']      ?? '<strong>YOUR_PASSWORD</strong>';<br>";
echo "&nbsp;&nbsp;\$this->apiKey       = \$cfg['api_key']       ?? '<strong>YOUR_API_KEY</strong>';<br>";
echo "&nbsp;&nbsp;\$this->customerCode = \$cfg['customer_code'] ?? '<strong>YOUR_CUSTOMER_CODE</strong>';<br>";
echo "}";
echo "</code>";
echo "</div>";

echo "<h6 class='mt-4'>Alternative: Use .env Configuration (Recommended for Production)</h6>";
echo "<p>Add these to your .env file:</p>";
echo "<div style='background: #f5f5f5; padding: 15px; border-radius: 8px; font-size: 12px;'>";
echo "<code>";
echo "DTDC_USERNAME=your_username<br>";
echo "DTDC_PASSWORD=your_password<br>";
echo "DTDC_API_KEY=your_api_key<br>";
echo "DTDC_CUSTOMER_CODE=your_customer_code<br>";
echo "</code>";
echo "</div>";

echo "<p style='margin-top: 20px; font-size: 12px; color: #666;'>";
echo "Then modify lib/dtdc.php constructor to read from \$_ENV.<br>";
echo "Or create a new DtdcConfig wrapper class.";
echo "</p>";

echo "</div>";
echo "</div>";

echo "</div>";
echo "</body></html>";
