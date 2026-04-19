<?php
/**
 * DTDC API Full Debug - Show all requests and responses
 * Share this with DTDC support team for debugging
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/helpers.php';

$user = auth_user();
if (!$user || $user['role'] !== 'admin') {
    die('Access Denied');
}

$username = env('DTDC_USERNAME');
$password = env('DTDC_PASSWORD');
$awb = trim($_GET['awb'] ?? 'P79187948');

?>
<!DOCTYPE html>
<html>
<head>
    <title>DTDC API Debug - Full Request/Response</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; padding: 20px; font-family: 'Courier New', monospace; }
        .card { margin-bottom: 20px; }
        .header-row { background: #e8e8e8; padding: 8px; margin: 5px 0; border-left: 3px solid #007bff; }
        pre { background: #000; color: #0f0; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 11px; margin: 10px 0; }
        .response-box { background: #f0f0f0; padding: 15px; border-radius: 5px; }
        .request-box { background: #e3f2fd; padding: 15px; border-radius: 5px; }
        .copy-btn { position: absolute; right: 10px; top: 10px; }
        .container-section { position: relative; }
        h4 { margin-top: 20px; color: #003d99; }
        .input-group { margin-bottom: 20px; }
    </style>
</head>
<body>

<div class="container-fluid">
    <h2>🔧 DTDC API Debug Console</h2>
    <p class="text-muted">Complete request/response log for DTDC support team</p>

    <!-- Input Section -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Enter AWB Number to Track</h5>
        </div>
        <div class="card-body">
            <div class="input-group">
                <input type="text" class="form-control" id="awbInput" value="<?= htmlspecialchars($awb) ?>" placeholder="Enter AWB number">
                <button class="btn btn-primary" onclick="testAWB()">Test Tracking</button>
            </div>
        </div>
    </div>

    <!-- Step 1: Authentication -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">📤 Step 1: Authentication Request</h5>
        </div>
        <div class="card-body request-box container-section">
            <button class="btn btn-sm btn-outline-secondary copy-btn" onclick="copyText('auth-request')">Copy</button>
            <h6>GET Request:</h6>
            <pre id="auth-request">https://blktracksvc.dtdc.com/dtdc-api/api/dtdc/authenticate?username=<?= urlencode($username) ?>&password=<?= urlencode($password) ?></pre>

            <h6>Headers:</h6>
            <pre>Accept: text/plain
User-Agent: CareyGo/1.0</pre>
        </div>
    </div>

    <!-- Step 1: Auth Response -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">📥 Step 1: Authentication Response</h5>
        </div>
        <div class="card-body response-box container-section">
            <button class="btn btn-sm btn-outline-secondary copy-btn" onclick="copyText('auth-response')">Copy</button>
            <h6>Status Code:</h6>
            <pre id="auth-status">Loading...</pre>

            <h6>Response Body (Plain Text):</h6>
            <pre id="auth-response">Loading...</pre>

            <h6>Token Extracted:</h6>
            <pre id="auth-token" style="color: #0a0;">username:token_here</pre>
        </div>
    </div>

    <!-- Step 2: Tracking Request -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">📤 Step 2: Tracking Request</h5>
        </div>
        <div class="card-body request-box container-section">
            <button class="btn btn-sm btn-outline-secondary copy-btn" onclick="copyText('track-request')">Copy</button>
            <h6>POST Request:</h6>
            <pre>https://blktracksvc.dtdc.com/dtdc-api/rest/JSONCnTrk/getTrackDetails</pre>

            <h6>Headers:</h6>
            <pre>Content-Type: application/json
Accept: application/json
User-Agent: CareyGo/1.0
X-Access-Token: [token_from_step_1]</pre>

            <h6>Request Body (JSON):</h6>
            <pre id="track-request">Loading...</pre>
        </div>
    </div>

    <!-- Step 2: Tracking Response -->
    <div class="card">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">📥 Step 2: Tracking Response</h5>
        </div>
        <div class="card-body response-box container-section">
            <button class="btn btn-sm btn-outline-secondary copy-btn" onclick="copyText('track-response')">Copy</button>
            <h6>HTTP Status Code:</h6>
            <pre id="track-status">Loading...</pre>

            <h6>Response Body (JSON):</h6>
            <pre id="track-response">Loading...</pre>
        </div>
    </div>

    <!-- Summary -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">📋 Quick Copy Options</h5>
        </div>
        <div class="card-body">
            <div class="btn-group mb-3" role="group">
                <button class="btn btn-success" onclick="copyAuthRequestResponse()">📋 Copy Auth (Request + Response)</button>
                <button class="btn btn-success" onclick="copyTrackRequestResponse()">📋 Copy Tracking (Request + Response)</button>
                <button class="btn btn-primary" onclick="copyAllDebug()">📋 Copy All Data</button>
            </div>
            <p class="text-muted small">Paste into email or ticket for DTDC support team</p>
        </div>
    </div>

</div>

<script>
function testAWB() {
    const awb = document.getElementById('awbInput').value.trim();
    if (!awb) {
        alert('Please enter an AWB number');
        return;
    }
    window.location.href = '?awb=' + encodeURIComponent(awb);
}

function copyText(elementId) {
    const element = document.getElementById(elementId);
    const text = element.innerText || element.textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = event.target;
        const original = btn.innerText;
        btn.innerText = '✅ Copied!';
        setTimeout(() => btn.innerText = original, 2000);
    });
}

function copyAuthRequestResponse() {
    const authRequest = document.getElementById('auth-request').textContent;
    const authStatus = document.getElementById('auth-status').textContent;
    const authResponse = document.getElementById('auth-response').textContent;
    const authToken = document.getElementById('auth-token').textContent;

    const combined = `═══════════════════════════════════════════════════
DTDC AUTHENTICATION REQUEST & RESPONSE
═══════════════════════════════════════════════════

REQUEST:
--------
${authRequest}

Headers:
Accept: text/plain
User-Agent: CareyGo/1.0


RESPONSE:
---------
HTTP Status Code: ${authStatus}

Response Body:
${authResponse}

Extracted Token:
${authToken}

═══════════════════════════════════════════════════`;

    navigator.clipboard.writeText(combined).then(() => {
        const btn = event.target;
        const original = btn.innerText;
        btn.innerText = '✅ Copied!';
        setTimeout(() => btn.innerText = original, 2000);
    });
}

function copyTrackRequestResponse() {
    const trackRequest = document.getElementById('track-request').textContent;
    const trackStatus = document.getElementById('track-status').textContent;
    const trackResponse = document.getElementById('track-response').textContent;

    const combined = `═══════════════════════════════════════════════════
DTDC TRACKING REQUEST & RESPONSE
═══════════════════════════════════════════════════

REQUEST:
--------
URL: https://blktracksvc.dtdc.com/dtdc-api/rest/JSONCnTrk/getTrackDetails
Method: POST

Headers:
Content-Type: application/json
Accept: application/json
User-Agent: CareyGo/1.0
X-Access-Token: [from_authentication_step]

Body:
${trackRequest}


RESPONSE:
---------
HTTP Status Code: ${trackStatus}

Response Body:
${trackResponse}

═══════════════════════════════════════════════════`;

    navigator.clipboard.writeText(combined).then(() => {
        const btn = event.target;
        const original = btn.innerText;
        btn.innerText = '✅ Copied!';
        setTimeout(() => btn.innerText = original, 2000);
    });
}

function copyAllDebug() {
    const timestamp = new Date().toISOString();
    const authRequest = document.getElementById('auth-request').textContent;
    const authStatus = document.getElementById('auth-status').textContent;
    const authResponse = document.getElementById('auth-response').textContent;
    const trackRequest = document.getElementById('track-request').textContent;
    const trackStatus = document.getElementById('track-status').textContent;
    const trackResponse = document.getElementById('track-response').textContent;

    const combined = `═════════════════════════════════════════════════════════════════════════════
DTDC API COMPLETE DEBUG LOG
Generated: ${timestamp}
═════════════════════════════════════════════════════════════════════════════

STEP 1: AUTHENTICATION REQUEST
──────────────────────────────────────────────────────────────────────────────
${authRequest}

Headers:
  Accept: text/plain
  User-Agent: CareyGo/1.0


STEP 1: AUTHENTICATION RESPONSE
──────────────────────────────────────────────────────────────────────────────
HTTP Status Code: ${authStatus}

Response (Plain Text):
${authResponse}


STEP 2: TRACKING REQUEST
──────────────────────────────────────────────────────────────────────────────
URL: https://blktracksvc.dtdc.com/dtdc-api/rest/JSONCnTrk/getTrackDetails
Method: POST

Headers:
  Content-Type: application/json
  Accept: application/json
  User-Agent: CareyGo/1.0
  X-Access-Token: [token_from_step_1]

Request Body:
${trackRequest}


STEP 2: TRACKING RESPONSE
──────────────────────────────────────────────────────────────────────────────
HTTP Status Code: ${trackStatus}

Response Body:
${trackResponse}

═════════════════════════════════════════════════════════════════════════════
END OF DEBUG LOG
═════════════════════════════════════════════════════════════════════════════`;

    navigator.clipboard.writeText(combined).then(() => {
        const btn = event.target;
        const original = btn.innerText;
        btn.innerText = '✅ Copied!';
        setTimeout(() => btn.innerText = original, 2000);
    });
}

// Load debug data via AJAX
document.addEventListener('DOMContentLoaded', function() {
    fetch('dtdc-api-debug-json.php?awb=<?= urlencode($awb) ?>')
        .then(r => r.json())
        .then(data => {
            document.getElementById('auth-status').textContent = data.auth.statusCode;
            document.getElementById('auth-response').textContent = data.auth.response;
            document.getElementById('auth-token').textContent = data.auth.token || 'No token received';

            document.getElementById('track-request').textContent = JSON.stringify(data.track.request, null, 2);
            document.getElementById('track-status').textContent = data.track.statusCode;
            document.getElementById('track-response').textContent = JSON.stringify(data.track.response, null, 2);
        })
        .catch(err => {
            document.getElementById('auth-response').textContent = 'Error: ' + err.message;
        });
});
</script>

</body>
</html>
