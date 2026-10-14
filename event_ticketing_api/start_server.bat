@echo off
title EventHub REST API Server - SWC3633/SWC4443
echo ====================================================================
echo   SWC3633 / SWC4443 Web API Development Final Project Server
echo   Event Ticketing and Venue Booking RESTful Web API
echo   Student: Aidil Najmee Bin Che Jan (AM2412018166)
echo ====================================================================
echo.
echo Starting local web server on http://localhost:8000 ...
echo Access the Master Portal Hub at: http://localhost:8000/index.html
echo.
echo Press Ctrl+C to terminate the server.
echo.

if exist "C:\xampp\php\php.exe" (
    "C:\xampp\php\php.exe" -S localhost:8000
) else (
    php -S localhost:8000
)

pause

