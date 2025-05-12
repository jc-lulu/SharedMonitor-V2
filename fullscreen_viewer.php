<?php
// Check if share ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    die('Error: No sharing ID provided. Please use the link provided by the sharer.');
}

$shareId = $_GET['id'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Full Screen Viewer</title>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            background-color: #000;
            font-family: Arial, sans-serif;
        }

        #screen {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #contentFrame {
            width: 100%;
            height: 100%;
            border: none;
        }

        #waitingMessage {
            color: #fff;
            font-size: 24px;
            text-align: center;
        }

        #status {
            position: fixed;
            top: 10px;
            right: 10px;
            color: #fff;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 1000;
            opacity: 0.7;
            transition: opacity 0.3s;
        }

        #status:hover {
            opacity: 1;
        }

        #controls {
            position: fixed;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background-color: rgba(0, 0, 0, 0.5);
            padding: 5px;
            border-radius: 4px;
            z-index: 1000;
            display: flex;
            opacity: 0;
            transition: opacity 0.3s;
        }

        body:hover #controls {
            opacity: 0.7;
        }

        #controls:hover {
            opacity: 1;
        }

        button {
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 4px;
            padding: 8px 12px;
            margin: 0 5px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0b7dda;
        }

        #exitBtn {
            background-color: #f44336;
        }

        #exitBtn:hover {
            background-color: #d32f2f;
        }
    </style>
</head>

<body>
    <div id="status">Waiting for connection...</div>
    <div id="screen">
        <p id="waitingMessage">Connecting to screen share session...</p>
        <iframe id="contentFrame" style="display: none;" allowfullscreen></iframe>
    </div>
    <div id="controls">
        <button id="refreshBtn">Refresh</button>
        <button id="fullscreenBtn">Enter Fullscreen</button>
        <button id="exitBtn">Exit Fullscreen View</button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const contentFrame = document.getElementById('contentFrame');
            const status = document.getElementById('status');
            const waitingMessage = document.getElementById('waitingMessage');
            const refreshBtn = document.getElementById('refreshBtn');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const exitBtn = document.getElementById('exitBtn');
            const screenDiv = document.getElementById('screen');

            const shareId = '<?php echo $shareId; ?>';
            let isConnected = false;
            let refreshIntervalId;
            let reconnectAttempts = 0;
            const maxReconnectAttempts = 5;
            let lastUrl = '';

            // Auto-enter fullscreen mode on load (browsers may block this)
            try {
                // We'll attempt fullscreen after user interaction instead
                // as browsers block automatic fullscreen
            } catch (e) {
                console.log("Auto fullscreen not allowed. User needs to click the fullscreen button.");
            }

            // Start receiving content URLs
            startReceiving();

            refreshBtn.addEventListener('click', function () {
                if (refreshIntervalId) {
                    clearInterval(refreshIntervalId);
                }
                reconnectAttempts = 0;
                startReceiving();
            });

            fullscreenBtn.addEventListener('click', function () {
                requestFullscreen(document.documentElement);
            });

            exitBtn.addEventListener('click', function () {
                // Return to the monitor page
                window.location.href = 'monitor.php?id=' + shareId;
            });

            // Listen for ESC key to detect fullscreen exit
            document.addEventListener('fullscreenchange', function () {
                if (!document.fullscreenElement) {
                    fullscreenBtn.style.display = 'inline';
                } else {
                    fullscreenBtn.style.display = 'none';
                }
            });

            function requestFullscreen(element) {
                if (element.requestFullscreen) {
                    element.requestFullscreen();
                } else if (element.mozRequestFullScreen) {
                    element.mozRequestFullScreen();
                } else if (element.webkitRequestFullscreen) {
                    element.webkitRequestFullscreen();
                } else if (element.msRequestFullscreen) {
                    element.msRequestFullscreen();
                }
            }

            function startReceiving() {
                status.textContent = 'Connecting...';
                status.style.color = '#f39c12';

                refreshIntervalId = setInterval(getLatestUrl, 1000); // Check every second
            }

            function getLatestUrl() {
                fetch('get_frame.php?id=' + encodeURIComponent(shareId) + '&t=' + new Date().getTime())
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Server returned status ' + response.status);
                        }
                        return response.text();
                    })
                    .then(data => {
                        if (data.startsWith('ERROR:')) {
                            handleError(data.substring(6));
                            return;
                        }

                        if (data === 'NO_FRAME') {
                            if (isConnected) {
                                waitingMessage.textContent = 'Waiting for sharer to resume...';
                                waitingMessage.style.display = 'block';
                                contentFrame.style.display = 'none';
                            } else {
                                waitingMessage.textContent = 'Waiting for sharer to start...';
                            }
                            return;
                        }

                        // We have a valid URL
                        if (!isConnected) {
                            isConnected = true;
                            status.textContent = 'Connected';
                            status.style.color = 'green';
                            reconnectAttempts = 0;
                        }

                        // Only update iframe if URL has changed
                        if (data !== lastUrl) {
                            lastUrl = data;
                            contentFrame.src = data;
                            contentFrame.style.display = 'block';
                            waitingMessage.style.display = 'none';
                        }
                    })
                    .catch(error => {
                        handleError('Connection error: ' + error.message);
                    });
            }

            function handleError(message) {
                reconnectAttempts++;

                if (reconnectAttempts > maxReconnectAttempts) {
                    clearInterval(refreshIntervalId);
                    status.textContent = 'Disconnected: ' + message;
                    status.style.color = 'red';
                    waitingMessage.textContent = 'Connection failed. Please try refreshing.';
                } else {
                    status.textContent =
                        `Connection issue (attempt ${reconnectAttempts}/${maxReconnectAttempts}): ${message}`;
                    status.style.color = '#e74c3c';
                }
            }
        });
    </script>
</body>

</html>