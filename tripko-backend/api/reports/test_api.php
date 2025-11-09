<?php
session_start();
$_SESSION['user_id'] = 1; // Simulate logged in admin

header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Reports API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        pre { background: #f5f5f5; padding: 1em; border-radius: 4px; overflow-x: auto; }
        .error { color: #dc2626; }
        .success { color: #16a34a; }
        .controls { margin-bottom: 1em; }
        button { padding: 0.5em 1em; }
        select { padding: 0.5em; }
    </style>
</head>
<body>
    <h2>Reports API Test</h2>
    <div class="controls">
        <select id="period">
            <option value="7">Last 7 days</option>
            <option value="30" selected>Last 30 days</option>
            <option value="90">Last 3 months</option>
            <option value="365">Last year</option>
        </select>
        <button onclick="testAPI()">Test API</button>
    </div>
    <div id="result">Loading...</div>

    <script>
        async function testAPI() {
            try {
                const period = document.getElementById('period').value;
                document.getElementById('result').innerHTML = 'Loading...';

                const response = await fetch(`get_reports.php?period=${period}`, {
                    method: 'GET',
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                console.log('Response status:', response.status);
                console.log('Response headers:', response.headers);
                
                const text = await response.text();
                console.log('Raw response:', text);
                
                try {
                    const data = JSON.parse(text);
                    document.getElementById('result').innerHTML = 
                        '<div class="' + (data.success ? 'success' : 'error') + '">' +
                        (data.success ? 'Success!' : 'Error: ' + data.message) +
                        '</div>' +
                        '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                } catch (e) {
                    document.getElementById('result').innerHTML = 
                        '<div class="error">Error parsing JSON: ' + e + '</div>' +
                        '<pre>Raw response: ' + text + '</pre>';
                }
            } catch (error) {
                document.getElementById('result').innerHTML = 
                    '<div class="error">Error: ' + error + '</div>';
                console.error('Error:', error);
            }
        }

        // Test on load
        testAPI();
    </script>
</body>
</html>