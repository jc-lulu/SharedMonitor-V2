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
    <title>Screen Sharing - Monitor</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
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

        #screen {
            width: 100%;
            max-width: 1200px;
            border: 1px solid #ddd;
            background-color: #f5f5f5;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #status {
            margin: 10px 0;
            font-weight: bold;
        }

        .controls {
            margin-bottom: 15px;
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

        .waiting-message {
            font-size: 18px;
            color: #666;
        }

        .fullscreen-btn {
            background-color: #2196F3;
        }

        .fullscreen-btn:hover {
            background-color: #0b7dda;
        }
    </style>
</head>

<body>
    <h1>Screen Sharing Monitor</h1>

    <div class="container">
        <div class="controls">
            <button id="refreshBtn">Refresh</button>
            <button id="fullscreenBtn" class="fullscreen-btn">Fullscreen</button>
            <button id="fullscreenViewerBtn" class="fullscreen-btn" style="background-color: #673AB7;">Open Fullscreen
                Viewer</button>
        </div>
        <div id="status">Waiting for screen share...</div>
        <div id="screen">
            <p class="waiting-message" id="waitingMessage">Connecting to screen share session...</p>
            <img id="screenImage" style="max-width: 100%; display: none;" />
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const screenImage = document.getElementById('screenImage');
            const status = document.getElementById('status');
            const waitingMessage = document.getElementById('waitingMessage');
            const refreshBtn = document.getElementById('refreshBtn');
            const fullscreenBtn = document.getElementById('fullscreenBtn');
            const fullscreenViewerBtn = document.getElementById('fullscreenViewerBtn');
            const screenDiv = document.getElementById('screen');

            const shareId = '<?php echo $shareId; ?>';
            let isConnected = false;
            let refreshIntervalId;
            let reconnectAttempts = 0;
            const maxReconnectAttempts = 5;

            // Start receiving frames
            startReceiving();

            refreshBtn.addEventListener('click', function () {
                if (refreshIntervalId) {
                    clearInterval(refreshIntervalId);
                }
                reconnectAttempts = 0;
                startReceiving();
            });

            fullscreenBtn.addEventListener('click', function () {
                if (screenDiv.requestFullscreen) {
                    screenDiv.requestFullscreen();
                } else if (screenDiv.mozRequestFullScreen) {
                    screenDiv.mozRequestFullScreen();
                } else if (screenDiv.webkitRequestFullscreen) {
                    screenDiv.webkitRequestFullscreen();
                } else if (screenDiv.msRequestFullscreen) {
                    screenDiv.msRequestFullscreen();
                }
            });

            fullscreenViewerBtn.addEventListener('click', function () {
                // Navigate to the fullscreen viewer page
                window.location.href = 'fullscreen_viewer.php?id=' + shareId;
            });

            function startReceiving() {
                status.textContent = 'Connecting...';
                status.style.color = '#f39c12';

                refreshIntervalId = setInterval(getLatestFrame, 16.67); // ~60fps (1000ms/60 = 16.67ms per frame)
            }

            function getLatestFrame() {
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
                                screenImage.style.display = 'none';
                            } else {
                                waitingMessage.textContent = 'Waiting for sharer to start...';
                            }
                            return;
                        }

                        // We have a valid frame
                        if (!isConnected) {
                            isConnected = true;
                            status.textContent = 'Connected';
                            status.style.color = 'green';
                            reconnectAttempts = 0;
                        }

                        screenImage.src = data;
                        screenImage.style.display = 'block';
                        waitingMessage.style.display = 'none';
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
                    status.textContent = `Connection issue (attempt ${reconnectAttempts}/${maxReconnectAttempts}): ${message}`;
                    status.style.color = '#e74c3c';
                }
            }
        });
    </script>
</body>

</html>