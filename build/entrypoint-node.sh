#!/bin/bash
set -e

cd /app

if [ ! -x node_modules/.bin/vite ]; then
  npm install --no-package-lock
fi

exec npm run dev -- --host 0.0.0.0
