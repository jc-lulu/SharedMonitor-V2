<?php
session_start();
// Generate a unique ID for this sharing session if not already set
if (!isset($_SESSION['share_id'])) {
    $_SESSION['share_id'] = uniqid('share_');
}
$shareId = $_SESSION['share_id'];

// Get server's IP address
function getServerIP()
{
    // Check if server is running on localhost
    if ($_SERVER['SERVER_ADDR'] == '::1' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
        // Try to get the local machine IPv4 address
        $ipAddresses = array_filter(
            explode("\n", shell_exec("ipconfig | findstr /i \"IPv4 Address\"")),
            'trim'
        );
        if (!empty($ipAddresses)) {
            foreach ($ipAddresses as $line) {
                if (strpos($line, 'IPv4') !== false) {
                    $ip = trim(explode(":", $line)[1]);
                    if (!empty($ip)) {
                        return $ip;
                    }
                }
            }
        }
        return '127.0.0.1'; // Fallback to localhost
    }

    return $_SERVER['SERVER_ADDR']; // Return the server IP address
}

$serverIP = getServerIP();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Screen Sharing - Sharer</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            line-height: 1.6;
        }

        .container {
            border: 1px solid #ccc;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        h1 {
            color: #333;
        }

        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-right: 10px;
        }

        button:hover {
            background-color: #45a049;
        }

        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
        }

        #status {
            margin: 10px 0;
            font-weight: bold;
        }

        .share-info {
            background-color: #f0f0f0;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }

        .share-url {
            font-weight: bold;
            word-break: break-all;
            margin-top: 10px;
            color: #0066cc;
            cursor: pointer;
        }

        .share-option {
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 4px;
            background-color: #e9f5fe;
        }

        .share-option-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .copy-btn {
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
        }

        .copy-btn:hover {
            background-color: #0069d9;
        }

        .copied {
            background-color: #28a745;
        }

        #urlInput {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .warning {
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .checkbox-container {
            display: flex;
            align-items: center;
            margin: 15px 0;
        }

        .checkbox-container input {
            margin-right: 10px;
        }
    </style>
</head>

<body>
    <h1>Screen Sharing Tool - Sharer</h1>

    <div class="container">
        <p>Enter a URL you want to share with viewers:</p>
        <input type="url" id="urlInput" placeholder="https://example.com" value="https://example.com">

        <div class="warning">
            <strong>Note:</strong> Some websites (like YouTube, Facebook, etc.) have security restrictions that prevent
            them from being shared in this tool. This is because they set X-Frame-Options headers to prevent embedding.
            Try using the proxy option below for these sites.
        </div>

        <div class="checkbox-container">
            <input type="checkbox" id="useProxy">
            <label for="useProxy">Use proxy for restricted sites (YouTube, Facebook, etc.)</label>
        </div>

        <button id="shareButton">Start Sharing</button>
        <button id="stopButton" disabled>Stop Sharing</button>
        <div id="status">Not sharing</div>

        <div class="share-info">
            <p>Share one of these links with anyone who needs to view your shared content:</p>

            <div class="share-option">
                <div class="share-option-title">IPv4 Address (recommended):</div>
                <div class="share-url" id="ipShareUrl">
                    <?php echo "http://{$serverIP}/ScreenShareMonitor/monitor.php?id={$shareId}"; ?>
                    <button class="copy-btn" onclick="copyToClipboard('ipShareUrl')">Copy</button>
                </div>
            </div>

            <div class="share-option">
                <div class="share-option-title">Hostname URL:</div>
                <div class="share-url" id="hostnameShareUrl"></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const shareButton = document.getElementById('shareButton');
            const stopButton = document.getElementById('stopButton');
            const status = document.getElementById('status');
            const urlInput = document.getElementById('urlInput');
            const useProxyCheckbox = document.getElementById('useProxy');
            const hostnameShareUrl = document.getElementById('hostnameShareUrl');

            // Set the share URL using hostname
            const hostname = window.location.hostname;
            const monitorUrl = window.location.protocol + '//' + hostname +
                '/ScreenShareMonitor/monitor.php?id=<?php echo $shareId; ?>';
            hostnameShareUrl.innerHTML = monitorUrl +
                ' <button class="copy-btn" onclick="copyToClipboard(\'hostnameShareUrl\')">Copy</button>';

            let intervalId;
            let isSharing = false;
            const shareId = '<?php echo $shareId; ?>';

            // Copy to clipboard function
            window.copyToClipboard = function (elementId) {
                const element = document.getElementById(elementId);
                const urlText = element.innerText.split('Copy')[0].trim();

                navigator.clipboard.writeText(urlText).then(function () {
                    const btn = element.querySelector('.copy-btn');
                    btn.textContent = 'Copied!';
                    btn.classList.add('copied');
                    setTimeout(() => {
                        btn.textContent = 'Copy';
                        btn.classList.remove('copied');
                    }, 2000);
                });
            };

            shareButton.addEventListener('click', () => {
                let url = urlInput.value.trim();

                if (!url) {
                    alert('Please enter a valid URL to share');
                    return;
                }

                // If proxy option is selected, use our proxy
                if (useProxyCheckbox.checked) {
                    url = window.location.protocol + '//' + window.location.host +
                        '/ScreenShareMonitor/proxy.php?url=' + encodeURIComponent(url);
                }

                // Start sharing the URL
                isSharing = true;
                shareButton.disabled = true;
                stopButton.disabled = false;
                status.textContent = 'Sharing active';
                status.style.color = 'green';

                // Send the URL to the server at regular intervals
                intervalId = setInterval(() => {
                    sendContentUrl(url);
                }, 1000); // Update every second
            });

            stopButton.addEventListener('click', stopSharing);

            function stopSharing() {
                if (intervalId) {
                    clearInterval(intervalId);
                }

                // Update UI
                isSharing = false;
                shareButton.disabled = false;
                stopButton.disabled = true;
                status.textContent = 'Sharing stopped';
                status.style.color = 'black';
            }

            function sendContentUrl(url) {
                fetch('update_frame.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'shareId=' + encodeURIComponent(shareId) + '&contentUrl=' + encodeURIComponent(url)
                })
                    .catch(error => {
                        console.error('Error sending URL:', error);
                    });
            }
        });
    </script>
</body>

</html>