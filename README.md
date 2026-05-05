Proxmox Usage Monitor
A lightweight, web-based dashboard to monitor the real-time status of a Proxmox server or any Linux machine. It visualizes metrics like CPU, RAM, Disk, Network, System Load, and Swap, along with a list of the top resource-consuming processes.

Features
Real-Time Graphs: Visual metrics using Chart.js.

Manual Snapshot: A dynamic button to capture and add data points instantly.

Top Processes: List of the top 10 processes by resource usage.

CPU Optimization: Specifically adjusted for 4-core systems (scaled to a true 0-100%).

Network Metrics: Displays RX/TX traffic in KB/s.

Installation
Upload Files: Upload the following files to your web server (Apache/Nginx) with PHP support:

index.html: The main user interface.

capture.php: The backend script that gathers system data.

latest.json: The history file updated by your automated script.

Permissions:
Ensure the web server user (www-data) has permissions to execute system commands such as top, free, and df.

Network Interface:
In capture.php, ensure the network interface name matches your system (e.g., eth0, ens18, or vmbr0).

Manual Snapshot Button
The "CAPTURE CURRENT STATUS" button performs these steps:

Triggers capture.php.

The server measures the network state (waiting 1 second to calculate an accurate KB/s rate).

Gathers RAM, Disk, CPU, Load, and Swap data.

Updates the active chart and the process table without refreshing the page.

Requirements
OS: Linux (Tested on Debian/Proxmox).

Backend: PHP 7.4 or higher.

Frontend: A modern web browser with JavaScript (ES6) enabled.
