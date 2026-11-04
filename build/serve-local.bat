@echo off
rem Local gallery with every demo and its review pages (no publishing needed).
rem Rebuild first if needed: python build\gallery.py --local
cd /d "%~dp0.."
start "" http://localhost:8080/
php -S localhost:8080 -t dist
