<#
.SYNOPSIS
    Richtet Apache und MariaDB aus XAMPP als Windows-Dienste mit automatischem Start ein.

.DESCRIPTION
    - nimmt PHP (C:\xampp\php) in den System-PATH auf, damit "php artisan ..." in jeder Konsole geht
    - prüft die Apache-Konfiguration (httpd -t), bevor etwas geändert wird
    - beendet von der XAMPP-Oberfläche gestartete Apache/MariaDB-Prozesse sauber
    - installiert die Dienste "Apache2.4" und "mysql" (Namen, die auch das XAMPP Control Panel erkennt),
      Starttyp "Automatisch", Neustart bei Absturz
    - legt die Firewall-Regel für Port 80 an (nur privates Netzwerk, nur eigenes Subnetz)
    - prüft am Ende, ob die Anmeldeseite antwortet

    Mehrfach ausführbar: Was schon eingerichtet ist, wird übersprungen.
    Rückgängig machen: Skript mit -Uninstall aufrufen (entfernt Dienste und Firewall-Regel, nicht den PATH-Eintrag).

.EXAMPLE
    Rechtsklick auf PowerShell -> "Als Administrator ausführen", dann:
    powershell -ExecutionPolicy Bypass -File .\scripts\windows\Install-Server.ps1
#>
[CmdletBinding()]
param(
    [string]$XamppPath = 'C:\xampp',
    [switch]$Uninstall
)

$ErrorActionPreference = 'Stop'

$ApacheService = 'Apache2.4'
$MysqlService = 'mysql'
$FirewallRule = 'Rezeptbuch (Apache, Heimnetz)'

$httpd = Join-Path $XamppPath 'apache\bin\httpd.exe'
$mysqld = Join-Path $XamppPath 'mysql\bin\mysqld.exe'
$mysqladmin = Join-Path $XamppPath 'mysql\bin\mysqladmin.exe'
$myIni = Join-Path $XamppPath 'mysql\bin\my.ini'
$phpDir = Join-Path $XamppPath 'php'

function Write-Step([string]$Text) { Write-Host "`n==> $Text" -ForegroundColor Cyan }
function Write-Ok([string]$Text) { Write-Host "    OK: $Text" -ForegroundColor Green }
function Write-Skip([string]$Text) { Write-Host "    -- $Text" -ForegroundColor DarkGray }

# Windows PowerShell 5.1 macht Ausgaben von Programmen auf stderr (z. B. "Syntax OK" von httpd -t)
# bei umgeleitetem stderr zu Fehlern. Deshalb Programme mit Umleitung hierüber aufrufen.
function Invoke-Native([scriptblock]$Command) {
    $previous = $ErrorActionPreference
    $ErrorActionPreference = 'Continue'
    try { & $Command 2>&1 | ForEach-Object { "$_" } } finally { $ErrorActionPreference = $previous }
}

# --- Administratorrechte -------------------------------------------------------------------------
$identity = [Security.Principal.WindowsPrincipal][Security.Principal.WindowsIdentity]::GetCurrent()
if (-not $identity.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)) {
    Write-Host 'Bitte PowerShell mit Rechtsklick -> "Als Administrator ausführen" starten und das Skript erneut aufrufen.' -ForegroundColor Yellow
    exit 1
}

foreach ($file in $httpd, $mysqld, $myIni) {
    if (-not (Test-Path $file)) { throw "Nicht gefunden: $file (XAMPP-Pfad mit -XamppPath angeben)" }
}

# --- Deinstallation ------------------------------------------------------------------------------
if ($Uninstall) {
    Write-Step 'Dienste entfernen'
    foreach ($name in $ApacheService, $MysqlService) {
        $svc = Get-Service -Name $name -ErrorAction SilentlyContinue
        if ($svc) {
            if ($svc.Status -ne 'Stopped') { Stop-Service -Name $name -Force }
            sc.exe delete $name | Out-Null
            Write-Ok "Dienst $name entfernt"
        } else { Write-Skip "Dienst $name nicht vorhanden" }
    }
    if (Get-NetFirewallRule -DisplayName $FirewallRule -ErrorAction SilentlyContinue) {
        Remove-NetFirewallRule -DisplayName $FirewallRule
        Write-Ok 'Firewall-Regel entfernt'
    }
    Write-Host "`nFertig. Apache und MariaDB lassen sich wieder über das XAMPP Control Panel starten."
    exit 0
}

# --- 1. PHP in den PATH --------------------------------------------------------------------------
Write-Step 'PHP in den System-PATH aufnehmen'
$machinePath = [Environment]::GetEnvironmentVariable('Path', 'Machine')
if (($machinePath -split ';') -contains $phpDir) {
    Write-Skip "$phpDir ist schon im PATH"
} else {
    [Environment]::SetEnvironmentVariable('Path', ($machinePath.TrimEnd(';') + ';' + $phpDir), 'Machine')
    Write-Ok "$phpDir hinzugefügt (gilt für neu geöffnete Konsolen)"
}
if (-not (($env:Path -split ';') -contains $phpDir)) { $env:Path += ";$phpDir" }

# --- 2. Apache-Konfiguration prüfen --------------------------------------------------------------
Write-Step 'Apache-Konfiguration prüfen (httpd -t)'
$test = Invoke-Native { & $httpd -t }
if ($LASTEXITCODE -ne 0) {
    $test | ForEach-Object { Write-Host "    $_" -ForegroundColor Red }
    throw 'Die Apache-Konfiguration ist fehlerhaft - es wurde nichts verändert.'
}
Write-Ok 'Syntax OK'

# --- 3. Von XAMPP gestartete Prozesse beenden -----------------------------------------------------
Write-Step 'Laufende XAMPP-Prozesse (ohne Dienst) beenden'
if (-not (Get-Service -Name $MysqlService -ErrorAction SilentlyContinue)) {
    if (Get-Process -Name mysqld -ErrorAction SilentlyContinue) {
        # Sauber herunterfahren, damit keine Daten verloren gehen (XAMPP-Standard: root ohne Passwort).
        Invoke-Native { & $mysqladmin -u root shutdown } | Out-Null
        Start-Sleep -Seconds 3
        if (Get-Process -Name mysqld -ErrorAction SilentlyContinue) {
            Write-Host '    MariaDB läuft noch. Bitte im XAMPP Control Panel bei MySQL auf "Stop" klicken und das Skript erneut starten.' -ForegroundColor Yellow
            exit 1
        }
        Write-Ok 'MariaDB beendet'
    } else { Write-Skip 'MariaDB läuft nicht' }
}
if (-not (Get-Service -Name $ApacheService -ErrorAction SilentlyContinue)) {
    $running = Get-Process -Name httpd -ErrorAction SilentlyContinue
    if ($running) {
        $running | Stop-Process -Force
        Start-Sleep -Seconds 2
        Write-Ok 'Apache beendet'
    } else { Write-Skip 'Apache läuft nicht' }
}

# --- 4. Dienste installieren ---------------------------------------------------------------------
Write-Step 'Dienste installieren'
if (Get-Service -Name $MysqlService -ErrorAction SilentlyContinue) {
    Write-Skip "Dienst $MysqlService existiert schon"
} else {
    & $mysqld --install $MysqlService "--defaults-file=$myIni"
    if ($LASTEXITCODE -ne 0) { throw 'MariaDB-Dienst konnte nicht installiert werden.' }
    Write-Ok "Dienst $MysqlService installiert"
}
if (Get-Service -Name $ApacheService -ErrorAction SilentlyContinue) {
    Write-Skip "Dienst $ApacheService existiert schon"
} else {
    & $httpd -k install -n $ApacheService
    if ($LASTEXITCODE -ne 0) { throw 'Apache-Dienst konnte nicht installiert werden.' }
    Write-Ok "Dienst $ApacheService installiert"
}

# --- 5. Autostart, Neustart bei Absturz, starten --------------------------------------------------
Write-Step 'Automatischen Start einrichten und Dienste starten'
foreach ($name in $MysqlService, $ApacheService) {
    Set-Service -Name $name -StartupType Automatic
    # Bei Absturz nach 5 s, 5 s und 30 s neu starten; Zähler nach einem Tag zurücksetzen.
    sc.exe failure $name reset= 86400 actions= restart/5000/restart/5000/restart/30000 | Out-Null
}
# Apache erst nach der Datenbank starten
sc.exe config $ApacheService depend= $MysqlService | Out-Null
foreach ($name in $MysqlService, $ApacheService) {
    if ((Get-Service -Name $name).Status -ne 'Running') { Start-Service -Name $name }
    Write-Ok "$name läuft (Starttyp: Automatisch)"
}

# --- 6. Firewall ---------------------------------------------------------------------------------
Write-Step 'Firewall-Freigabe für das Heimnetz'
if (Get-NetFirewallRule -DisplayName $FirewallRule -ErrorAction SilentlyContinue) {
    Write-Skip 'Regel existiert schon'
} else {
    New-NetFirewallRule -DisplayName $FirewallRule -Direction Inbound -Action Allow -Program $httpd `
        -Protocol TCP -LocalPort 80 -Profile Private -RemoteAddress LocalSubnet | Out-Null
    Write-Ok 'Port 80 für das lokale Subnetz (Profil "Privat") freigegeben'
}
$netProfile = (Get-NetConnectionProfile | Where-Object IPv4Connectivity -ne 'Disconnected' | Select-Object -First 1)
if ($netProfile -and $netProfile.NetworkCategory -ne 'Private') {
    Write-Host "    Hinweis: Das Netzwerk '$($netProfile.Name)' ist als '$($netProfile.NetworkCategory)' eingestuft. Für Zugriffe von iPhone/iPad muss es 'Privat' sein (Einstellungen -> Netzwerk)." -ForegroundColor Yellow
}

# --- 7. Test -------------------------------------------------------------------------------------
Write-Step 'Test: http://localhost/login'
try {
    $response = Invoke-WebRequest -Uri 'http://localhost/login' -UseBasicParsing -TimeoutSec 15
    Write-Ok "Antwort $($response.StatusCode)"
} catch {
    Write-Host "    Keine erfolgreiche Antwort: $($_.Exception.Message)" -ForegroundColor Yellow
    Write-Host '    Details: C:\xampp\apache\logs\familyrecipes-error.log und storage\logs\laravel.log' -ForegroundColor Yellow
}

$ip = (Get-NetIPAddress -AddressFamily IPv4 -PrefixOrigin Dhcp,Manual -ErrorAction SilentlyContinue |
    Where-Object { $_.IPAddress -notlike '169.254*' } | Select-Object -First 1).IPAddress
Write-Host "`nFertig. Das Rezeptbuch startet jetzt mit Windows." -ForegroundColor Green
Write-Host "  Auf diesem PC:   http://localhost"
if ($ip) { Write-Host "  Im Heimnetz:     http://$ip" }
Write-Host '  Für "php artisan ..." eine NEUE PowerShell öffnen (der PATH gilt erst dort).'
Write-Host '  Das XAMPP Control Panel zeigt die Dienste mit grünem Haken; Start/Stopp dort nur noch über die Dienste.'
