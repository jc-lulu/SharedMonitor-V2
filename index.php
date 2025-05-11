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

    #preview {
        width: 100%;
        max-width: 800px;
        border: 1px solid #ddd;
        margin-top: 10px;
        background-color: #f9f9f9;
        display: none;
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
    </style>
</head>

<body>
    <h1>Screen Sharing Tool - Sharer</h1>

    <div class="container">
        <p>Click the button below to start sharing your screen.</p>
        <button id="shareButton">Start Sharing</button>
        <button id="stopButton" disabled>Stop Sharing</button>
        <div id="status">Not sharing</div>

        <div class="share-info">
            <p>Share one of these links with anyone who needs to see your screen:</p>

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

        <video id="preview" autoplay muted></video>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const shareButton = document.getElementById('shareButton');
        const stopButton = document.getElementById('stopButton');
        const status = document.getElementById('status');
        const preview = document.getElementById('preview');
        const hostnameShareUrl = document.getElementById('hostnameShareUrl');

        // Set the share URL using hostname
        const hostname = window.location.hostname;
        const monitorUrl = window.location.protocol + '//' + hostname +
            '/ScreenShareMonitor/monitor.php?id=<?php echo $shareId; ?>';
        hostnameShareUrl.innerHTML = monitorUrl +
            ' <button class="copy-btn" onclick="copyToClipboard(\'hostnameShareUrl\')">Copy</button>';

        let mediaRecorder;
        let recordedBlobs;
        let stream;
        let intervalId;

        // Copy to clipboard function
        window.copyToClipboard = function(elementId) {
            const element = document.getElementById(elementId);
            const urlText = element.innerText.split('Copy')[0].trim();

            navigator.clipboard.writeText(urlText).then(function() {
                const btn = element.querySelector('.copy-btn');
                btn.textContent = 'Copied!';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = 'Copy';
                    btn.classList.remove('copied');
                }, 2000);
            });
        };

        shareButton.addEventListener('click', async () => {
            try {
                // Check if screen sharing is supported
                if (!navigator.mediaDevices || !navigator.mediaDevices.getDisplayMedia) {
                    throw new Error(
                        "Screen sharing is not supported in your browser or requires HTTPS. Please use a modern browser with HTTPS enabled."
                    );
                }

                // Request screen capture
                stream = await navigator.mediaDevices.getDisplayMedia({
                    video: {
                        cursor: "always"
                    },
                    audio: false
                });

                // Show preview of what's being shared
                preview.srcObject = stream;
                preview.style.display = 'block';

                // Update UI
                shareButton.disabled = true;
                stopButton.disabled = false;
                status.textContent = 'Sharing active';
                status.style.color = 'green';

                // Stream ended by user through browser UI
                stream.getVideoTracks()[0].addEventListener('ended', () => {
                    stopSharing();
                });

                // Start capturing frames
                startCapture(stream);

            } catch (error) {
                console.error('Error starting screen share:', error);
                status.textContent = 'Failed to start sharing: ' + error.message;
                status.style.color = 'red';
            }
        });

        stopButton.addEventListener('click', stopSharing);

        function stopSharing() {
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
            }
            if (intervalId) {
                clearInterval(intervalId);
            }

            // Update UI
            preview.style.display = 'none';
            preview.srcObject = null;
            shareButton.disabled = false;
            stopButton.disabled = true;
            status.textContent = 'Sharing stopped';
            status.style.color = 'black';
        }

        function startCapture(mediaStream) {
            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            const videoTrack = mediaStream.getVideoTracks()[0];
            const videoSettings = videoTrack.getSettings();

            // Set canvas dimensions to video dimensions
            canvas.width = videoSettings.width;
            canvas.height = videoSettings.height;

            const video = document.createElement('video');
            video.srcObject = mediaStream;
            video.play();

            // Send frames at regular intervals
            intervalId = setInterval(() => {
                // Draw current video frame to canvas
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                // Convert canvas to data URL
                const imageData = canvas.toDataURL('image/jpeg',
                    0.6); // Reduced quality for better performance at higher FPS

                // Send the image data to the server
                sendFrame(imageData);
            }, 16.67); // ~60fps (1000ms/60 = 16.67ms per frame)
        }

        function sendFrame(imageData) {
            fetch('update_frame.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'shareId=<?php echo $shareId; ?>&imageData=' + encodeURIComponent(imageData)
                })
                .catch(error => {
                    console.error('Error sending frame:', error);
                });
        }
    });
    </script>
</body>

</html>