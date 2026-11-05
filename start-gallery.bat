@echo off
rem Start the local gallery in Docker and open it. Stop with: docker compose down
cd /d "%~dp0"
docker compose up -d --build || (echo Docker is not running. Start Docker Desktop and try again. & pause & exit /b 1)
if "%SX_PORT%"=="" set SX_PORT=8090
start "" http://localhost:%SX_PORT%/
