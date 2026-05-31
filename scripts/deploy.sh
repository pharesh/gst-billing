#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# deploy.sh  —  Build & zip the app for AWS Elastic Beanstalk (Docker platform)
#
# Usage:
#   bash scripts/deploy.sh
#
# Output:
#   deploy.zip  (drop this into the EB console → Upload and deploy)
#
# Notes:
#   • EB reads env vars from the EB Environment → Configuration → Software page.
#     Copy everything from .env.production there — never ship secrets inside the ZIP.
#   • The Docker build (Dockerfile) runs composer install + npm run build,
#     so vendor/ and node_modules/ are intentionally excluded from the ZIP.
# ─────────────────────────────────────────────────────────────────────────────
set -e

echo ""
echo "┌─────────────────────────────────────────────┐"
echo "│   GST Billing — AWS EB Deploy ZIP Builder   │"
echo "└─────────────────────────────────────────────┘"
echo ""

# ── 1. Make sure we are at the project root ──────────────────────────────────
cd "$(dirname "$0")/.."
ROOT="$(pwd)"

# ── 2. Output zip path ───────────────────────────────────────────────────────
ZIP_NAME="deploy.zip"
ZIP_PATH="$ROOT/$ZIP_NAME"

# Remove any old zip
rm -f "$ZIP_PATH"

echo "▶ Creating $ZIP_NAME from: $ROOT"
echo ""

# ── 3. Build zip (exclude everything that EB/Docker builds itself) ────────────
zip -r "$ZIP_PATH" . \
    --exclude "*.git*" \
    --exclude "node_modules/*" \
    --exclude "vendor/*" \
    --exclude "public/build/*" \
    --exclude "public/hot" \
    --exclude "storage/*.key" \
    --exclude "storage/logs/*.log" \
    --exclude "storage/pail/*" \
    --exclude ".env" \
    --exclude ".env.local" \
    --exclude ".env.*.local" \
    --exclude "*.zip" \
    --exclude ".phpunit.result.cache" \
    --exclude "tests/*" \
    --exclude ".DS_Store" \
    --exclude "Thumbs.db"

echo ""
echo "✅  Done!  ZIP size: $(du -sh "$ZIP_PATH" | cut -f1)"
echo ""
echo "Next steps:"
echo "  1. Go to AWS Elastic Beanstalk → your environment → Upload and deploy"
echo "  2. Upload: $ZIP_NAME"
echo "  3. Make sure ALL keys from .env.production are set in:"
echo "     EB Environment → Configuration → Software → Environment properties"
echo ""
echo "  Key env vars to set in EB console:"
echo "    APP_KEY         = $(grep APP_KEY .env.production | cut -d= -f2-)"
echo "    APP_URL         = https://quickgst.scalesup.studio"
echo "    DB_CONNECTION   = mongodb"
echo "    MONGODB_URI     = (your Atlas URI)"
echo "    DB_DATABASE     = gst_billing"
echo ""
