@echo off
cd /d C:\laragon\www\base-php
php scripts\enviar_recordatorios_whatsapp.php >> tmp\whatsapp_scheduler.log 2>&1
