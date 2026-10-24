$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot '..')
New-Item -ItemType Directory -Force secrets,backups | Out-Null
$utf8 = [System.Text.UTF8Encoding]::new($false)
foreach ($name in @('db_password','root_password','admin_password','grafana_password','exporter_password')) {
    if (!(Test-Path "secrets/$name")) {
        $bytes = New-Object byte[] 24
        $rng = [System.Security.Cryptography.RandomNumberGenerator]::Create()
        $rng.GetBytes($bytes)
        $rng.Dispose()
        [IO.File]::WriteAllText((Join-Path $PWD "secrets/$name"), [BitConverter]::ToString($bytes).Replace('-','').ToLowerInvariant(), $utf8)
    }
}
$password = (Get-Content secrets/exporter_password -Raw).Trim()
if (!(Test-Path secrets/exporter.cnf)) { [IO.File]::WriteAllText((Join-Path $PWD 'secrets/exporter.cnf'), "[client]`nuser=exporter`npassword=$password`nhost=mysql`n", $utf8) }
if (!(Test-Path secrets/init-exporter.sql)) { [IO.File]::WriteAllText((Join-Path $PWD 'secrets/init-exporter.sql'), "CREATE USER IF NOT EXISTS 'exporter'@'%' IDENTIFIED BY '$password' WITH MAX_USER_CONNECTIONS 3;`nGRANT PROCESS, REPLICATION CLIENT, SELECT ON *.* TO 'exporter'@'%';`n", $utf8) }
Write-Host 'Secrets ready. Start Docker Desktop, then run: docker compose up -d --build'
