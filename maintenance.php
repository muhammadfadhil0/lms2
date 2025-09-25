<?php
// Check if maintenance mode is still active
$config = include 'src/config/maintenance.php';
if ($config['maintenance_mode'] === false) {
    // Maintenance mode is disabled, redirect back to login
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php require 'assets/head.php'; ?>
    <title>Maintenance - Point</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: white;
            min-height: 100vh;
            color: #1e293b;
            overflow-x: hidden;
        }

        .maintenance-wrapper {
            position: relative;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Circuit board background pattern - removed */

        .circuit-bg svg {
            height: 100%;
            width: 100%;
        }

        /* Animated status lights */
        .status-lights {
            position: absolute;
            top: 2rem;
            right: 2rem;
            display: flex;
            gap: 0.75rem;
        }

        .status-light {
            height: 0.75rem;
            width: 0.75rem;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        .status-light.red {
            background-color: #f97316;
        }

        .status-light.yellow {
            background-color: #fb923c;
            animation-delay: 100ms;
        }

        .status-light.green {
            background-color: #ea580c;
            animation-delay: 200ms;
        }

        .maintenance-container {
            position: relative;
            margin: 0 auto;
            display: flex;
            min-height: 100vh;
            max-width: 64rem;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 4rem 1.5rem;
        }

        /* Server rack icon */
        .server-icon {
            margin-bottom: 3rem;
            position: relative;
            height: 6rem;
            width: 6rem;
        }

        .server-rack {
            position: absolute;
            inset: 0;
            border-radius: 0.5rem;
            background: #fed7aa;
            box-shadow: 0 10px 15px -3px rgba(249, 115, 22, 0.2);
        }

        .server-line {
            position: absolute;
            height: 0.25rem;
            width: 2rem;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 9999px;
            background: #f97316;
        }

        .server-line:nth-child(1) { top: 0.5rem; }
        .server-line:nth-child(2) { top: 1.25rem; }
        .server-line:nth-child(3) { top: 2rem; }
        .server-line:nth-child(4) { top: 2.75rem; }
        .server-line:nth-child(5) { top: 3.5rem; }
        .server-line:nth-child(6) { top: 4.25rem; }
        .server-line:nth-child(7) { top: 5rem; }

        .server-base {
            position: absolute;
            bottom: -0.5rem;
            left: 50%;
            height: 1rem;
            width: 3rem;
            transform: translateX(-50%);
            border-radius: 0 0 0.5rem 0.5rem;
            background: #fb923c;
        }

        .server-cable-left, .server-cable-right {
            position: absolute;
            top: 50%;
            height: 3rem;
            width: 1rem;
            transform: translateY(-50%);
            background: #fb923c;
        }

        .server-cable-left {
            left: -0.5rem;
            border-radius: 0.5rem 0 0 0.5rem;
        }

        .server-cable-right {
            right: -0.5rem;
            border-radius: 0 0.5rem 0.5rem 0;
        }

        /* Headline */
        .maintenance-title {
            margin-bottom: 1.5rem;
            text-align: center;
            font-size: 2.25rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .gradient-text {
            background: linear-gradient(to right, #f97316, #ea580c);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
        }

        /* Status message */
        .status-message {
            margin-bottom: 2rem;
            width: 100%;
            max-width: 32rem;
            border-radius: 0.5rem;
            border: 1px solid #fed7aa;
            background: rgba(255, 255, 255, 0.9);
            padding: 1.5rem;
            backdrop-filter: blur(8px);
        }

        .status-flex {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .status-icon {
            height: 1.5rem;
            width: 1.5rem;
            flex-shrink: 0;
            color: #f97316;
        }

        .status-content h2 {
            margin-bottom: 0.5rem;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .status-content p {
            color: #64748b;
        }

        /* Progress indicators */
        .progress-section {
            margin-bottom: 3rem;
            width: 100%;
            max-width: 28rem;
        }

        .progress-header {
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            font-size: 0.875rem;
            color: #64748b;
        }

        .progress-bar {
            height: 0.5rem;
            width: 100%;
            overflow: hidden;
            border-radius: 9999px;
            background: #fed7aa;
        }

        .progress-fill {
            height: 100%;
            width: 65%;
            background: linear-gradient(to right, #f97316, #ea580c);
            animation: progressBar 3s ease-in-out infinite alternate;
        }

        .progress-footer {
            margin-top: 0.5rem;
            text-align: right;
            font-size: 0.75rem;
            color: #94a3b8;
        }

        /* Feature grid */
        .features-grid {
            margin-bottom: 3rem;
            display: grid;
            gap: 1.5rem;
            grid-template-columns: repeat(1, 1fr);
        }

        .feature-card {
            border-radius: 0.5rem;
            border: 1px solid #fed7aa;
            background: rgba(255, 255, 255, 0.9);
            padding: 1.5rem;
        }

        .feature-icon {
            margin-bottom: 1rem;
            display: flex;
            height: 2.5rem;
            width: 2.5rem;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
        }

        .feature-icon.orange {
            background: rgba(249, 115, 22, 0.1);
            color: #f97316;
        }

        .feature-icon.orange-alt {
            background: rgba(234, 88, 12, 0.1);
            color: #ea580c;
        }

        .feature-card h3 {
            margin-bottom: 0.5rem;
            font-size: 1.125rem;
            font-weight: 600;
        }

        .feature-card p {
            color: #64748b;
        }

        /* Support section */
        .support-section {
            text-align: center;
        }

        .support-section p {
            margin-bottom: 1rem;
            color: #64748b;
        }

        .refresh-button {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid #f97316;
            background: #f97316;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            color: white;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .refresh-button:hover {
            background: #ea580c;
            border-color: #ea580c;
        }

        .refresh-button svg {
            height: 1.25rem;
            width: 1.25rem;
        }

        /* Floating backgrounds - removed */

        /* Footer */
        .maintenance-footer {
            position: absolute;
            right: 0;
            bottom: 0;
            left: 0;
            border-top: 1px solid rgba(249, 115, 22, 0.2);
            padding: 1rem 0;
            text-align: center;
            font-size: 0.875rem;
            color: #92400e;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
            100% {
                opacity: 1;
            }
        }

        @keyframes progressBar {
            0% {
                width: 60%;
            }
            100% {
                width: 75%;
            }
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        /* Responsive */
        @media (min-width: 640px) {
            .maintenance-title {
                font-size: 3rem;
            }
            
            .features-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .maintenance-container {
                padding: 4rem 2rem;
            }
        }

        @media (max-width: 640px) {
            .maintenance-title {
                font-size: 2rem;
            }

            .maintenance-container {
                padding: 2rem 1rem;
            }
            
            .status-lights {
                top: 1rem;
                right: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="maintenance-wrapper">

        <!-- Animated status lights -->
        <div class="status-lights">
            <span class="status-light red"></span>
            <span class="status-light yellow"></span>
            <span class="status-light green"></span>
        </div>

        <div class="maintenance-container">
            <!-- Tabler icon as span (much larger) -->
            <div class="server-icon" style="height:auto; width:auto;">
                <span class="ti ti-server-cog" style="font-size:8rem; display:inline-block; line-height:1; color:currentColor;" aria-hidden="true"></span>
            </div>

            <!-- Headline -->
            <h1 class="maintenance-title">
                <span class="gradient-text">Sistem Masih </span>
                <br />Dalam Perbaikan
            </h1>

            <!-- Status message -->
            <div class="status-message">
                <div class="status-flex">
                    <svg class="status-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <div class="status-content">
                        <h2><?php 
                        $config = include 'src/config/maintenance.php';
                        echo htmlspecialchars($config['maintenance_header'] ?? 'Scheduled Infrastructure Upgrade');
                        ?></h2>
                        <p><?php 
                        echo htmlspecialchars($config['maintenance_message'] ?? 'Sistem sedang dalam pemeliharaan. Silakan coba lagi nanti.');
                        ?></p>
                    </div>
                </div>
            </div>

            <!-- Support contact -->
            <div class="support-section">
                <p>Ingin Segera Bantuan?</p>
                <button class="refresh-button" onclick="refreshPage()">
                    <svg id="refresh-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                        </path>
                    </svg>
                    Coba Lagi
                </button>
            </div>

        </div>

        <!-- Footer -->
        <div class="maintenance-footer">
            <p>© 2023 Point LMS. All rights reserved.</p>
        </div>
    </div>

    <script>
        function refreshPage() {
            const refreshIcon = document.getElementById('refresh-icon');
            refreshIcon.classList.add('spin');
            
            setTimeout(() => {
                // Redirect to login instead of reload to check maintenance status
                window.location.href = 'login.php';
            }, 1000);
        }

        // Auto refresh setiap 30 detik - redirect to login to check status
        setInterval(() => {
            window.location.href = 'login.php';
        }, 30000);
    </script>
</body>

</html>