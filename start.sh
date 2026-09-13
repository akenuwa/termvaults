#!/usr/bin/env bash
# ==============================================================================
# SecOps TermVault Launcher
# Architect / Operative: 0745
# Organization: KoiTech
# Starts the local PHP server for quick access to your terminal cheat codes
# ==============================================================================

PORT=0745

# Check if port is in use, fallback if needed
if ss -tuln | grep -q ":$PORT "; then
  PORT=8090
fi

echo -e "\033[1;36m"
echo "  ____               ___              _____                     __     __            _ _   "
echo " / ___|  ___  ___   / _ \ _ __  ___  |_   _|__ _ __ _ __ ___    \ \   / /_ _ _   _ _| | |_ "
echo " \___ \ / _ \/ __| | | | | '_ \/ __|   | |/ _ \ '__| '_ \` _ \    \ \ / / _\` | | | | | | __|"
echo "  ___) |  __/ (__  | |_| | |_) \__ \   | |  __/ |  | | | | | |    \ V / (_| | |_| | | | |_ "
echo " |____/ \___|\___|  \___/| .__/|___/   |_|\___|_|  |_| |_| |_|     \_/ \__,_|\__,_|_|_|\__|"
echo "                         |_|                                                            "
echo -e "\033[0m"
echo -e "\033[1;35m[::] Organization: KOITECH  |  Lead Architect: 0745\033[0m"
echo -e "\033[1;32m[+] Starting SecOps TermVault on http://localhost:$PORT\033[0m"
echo -e "\033[1;33m[+] Press Ctrl+C anytime to stop the server\033[0m"
echo ""

# Attempt to open browser if in GUI environment
if command -v xdg-open > /dev/null 2>&1 && [ -n "$DISPLAY" ]; then
  (sleep 1.2 && xdg-open "http://localhost:$PORT" > /dev/null 2>&1) &
fi

cd "$(dirname "$0")"
php -S 127.0.0.1:$PORT
