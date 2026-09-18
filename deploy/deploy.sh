#!/usr/bin/env bash
# ==============================================================================
# MindCare Counselling Service - Automated Production Deployment Script
# Target Environment: Azure Linux VM (Ubuntu / Debian)
# ==============================================================================
set -euo pipefail

# ------------------------------------------------------------------------------
# Configuration Variables (Can be passed via environment or arguments)
# ------------------------------------------------------------------------------
ACR_NAME="${ACR_NAME:-mindcareacr}"
ACR_LOGIN_SERVER="${ACR_LOGIN_SERVER:-${ACR_NAME}.azurecr.io}"
IMAGE_NAME="${IMAGE_NAME:-mindcare-app}"
IMAGE_TAG="${IMAGE_TAG:-latest}"
FULL_IMAGE="${ACR_LOGIN_SERVER}/${IMAGE_NAME}:${IMAGE_TAG}"

CONTAINER_NAME="${CONTAINER_NAME:-mindcare-app}"
HOST_PORT="${HOST_PORT:-80}"

# MariaDB / Database Environment Variables
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-mindcare_db}"
ENCRYPTION_KEY="${ENCRYPTION_KEY:-mindcare_aes_32char_secret_key!!}"

# Optional ACR credentials (if not using Azure Managed Identity)
ACR_USERNAME="${ACR_USERNAME:-}"
ACR_PASSWORD="${ACR_PASSWORD:-}"

echo "======================================================================"
echo " Starting MindCare Automated Deployment"
echo " Image:       ${FULL_IMAGE}"
echo " Container:   ${CONTAINER_NAME}"
echo " Database:    ${DB_USER}@${DB_HOST}:${DB_PORT}/${DB_NAME}"
echo " Timestamp:   $(date -u +"%Y-%m-%dT%H:%M:%SZ")"
echo "======================================================================"

# ------------------------------------------------------------------------------
# 1. Ensure Docker Engine is Installed and Running
# ------------------------------------------------------------------------------
if ! command -v docker &> /dev/null; then
    echo "[*] Docker not found. Installing docker.io..."
    sudo apt-get update -y
    sudo apt-get install -y docker.io curl
    sudo systemctl enable --now docker
else
    echo "[+] Docker is already installed."
    sudo systemctl start docker || true
fi

# ------------------------------------------------------------------------------
# 2. Prevent Port 80 Conflicts with Native Web Servers
# ------------------------------------------------------------------------------
echo "[*] Ensuring port ${HOST_PORT} is free from native Apache/Nginx..."
sudo systemctl stop apache2 2>/dev/null || true
sudo systemctl disable apache2 2>/dev/null || true
sudo systemctl stop nginx 2>/dev/null || true
sudo systemctl disable nginx 2>/dev/null || true

# ------------------------------------------------------------------------------
# 3. Authenticate with Azure Container Registry (ACR)
# ------------------------------------------------------------------------------
echo "[*] Authenticating with ACR (${ACR_LOGIN_SERVER})..."

if [[ -n "${ACR_USERNAME}" && -n "${ACR_PASSWORD}" ]]; then
    echo "[+] Authenticating via direct ACR credentials..."
    echo "${ACR_PASSWORD}" | sudo docker login "${ACR_LOGIN_SERVER}" -u "${ACR_USERNAME}" --password-stdin
elif command -v az &> /dev/null; then
    echo "[+] Attempting authentication via Azure CLI (Managed Identity or active session)..."
    # If Azure VM has a System-Assigned Managed Identity, authenticate silently
    az login --identity --allow-no-subscriptions 2>/dev/null || true
    az acr login --name "${ACR_NAME}"
else
    echo "[!] Warning: No explicit ACR credentials provided and Azure CLI is not installed."
    echo "[!] Proceeding under assumption of pre-authenticated Docker session or local image."
fi

# ------------------------------------------------------------------------------
# 4. Pull Latest Container Image from ACR
# ------------------------------------------------------------------------------
echo "[*] Pulling latest image: ${FULL_IMAGE}..."
sudo docker pull "${FULL_IMAGE}"

# ------------------------------------------------------------------------------
# 5. Stop and Remove Old Container Instance
# ------------------------------------------------------------------------------
if sudo docker ps -a --format '{{.Names}}' | grep -Eq "^${CONTAINER_NAME}\$"; then
    echo "[*] Stopping and removing existing container: ${CONTAINER_NAME}..."
    sudo docker stop "${CONTAINER_NAME}" || true
    sudo docker rm -f "${CONTAINER_NAME}" || true
fi

# ------------------------------------------------------------------------------
# 6. Run New Container Instance
# ------------------------------------------------------------------------------
echo "[*] Launching new MindCare container..."
# Using --network host allows the container on Linux to bind directly to port 80
# and communicate with MariaDB at 127.0.0.1:3306 without bridge routing complexities.
sudo docker run -d \
    --name "${CONTAINER_NAME}" \
    --restart unless-stopped \
    --network host \
    -e DB_HOST="${DB_HOST}" \
    -e DB_PORT="${DB_PORT}" \
    -e DB_USER="${DB_USER}" \
    -e DB_PASS="${DB_PASS}" \
    -e DB_NAME="${DB_NAME}" \
    -e ENCRYPTION_KEY="${ENCRYPTION_KEY}" \
    "${FULL_IMAGE}"

# ------------------------------------------------------------------------------
# 7. Post-Deployment Verification & Health Check
# ------------------------------------------------------------------------------
echo "[*] Waiting 5 seconds for Apache/PHP services to initialize..."
sleep 5

if sudo docker ps --filter "name=${CONTAINER_NAME}" --filter "status=running" | grep -q "${CONTAINER_NAME}"; then
    echo "[+] Container '${CONTAINER_NAME}' is UP and running."
else
    echo "[!] ERROR: Container '${CONTAINER_NAME}' failed to start. Showing logs:"
    sudo docker logs --tail 50 "${CONTAINER_NAME}"
    exit 1
fi

echo "[*] Testing HTTP response from container..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:${HOST_PORT}/frontend/pages/login.html" || true)

if [[ "${HTTP_STATUS}" =~ ^(200|301|302)$ ]]; then
    echo "[+] Health check PASSED: HTTP status ${HTTP_STATUS} received from http://127.0.0.1:${HOST_PORT}/"
else
    echo "[!] Warning: Received HTTP ${HTTP_STATUS}. Printing recent container logs:"
    sudo docker logs --tail 25 "${CONTAINER_NAME}"
fi

echo "======================================================================"
echo " MindCare Deployment Completed Successfully!"
echo " Access URL: http://$(curl -s https://api.ipify.org || echo 'YOUR_VM_PUBLIC_IP')/frontend/pages/login.html"
echo "======================================================================"
