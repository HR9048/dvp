@echo off

:: ← Your API key
set API_KEY=20170472417


:: Run vehicle_program_autoscript.php through HTTP with API key header
curl -X GET "http://localhost:8880/dvp_test/vehicle_program_autoscript_monthly.php" -H "X-API-KEY: %API_KEY%"



pause
exit
