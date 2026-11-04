# Screenshots of every demo home page for the gallery (desktop 1280x800 at half size, phone 375px inside a frame).
# Needs Chrome. Usage (from the satellite-system folder): powershell -File build/screenshots.ps1, then python build/gallery.py
$root = Split-Path -Parent $PSScriptRoot
$src = "$root\dist\gallery\src"; $shots = "$root\dist\gallery\shots"
$chrome = "C:\Program Files\Google\Chrome\Application\chrome.exe"
New-Item -ItemType Directory -Force $src, $shots | Out-Null
$enc = New-Object System.Text.UTF8Encoding $false
# Age gate hidden for the picture; everything else is the shipped page.
$inject = '<style>[class*="agegate"],[class*="age-gate"]{display:none!important}html,body{overflow:visible!important}</style>'
foreach ($id in (Get-ChildItem "$root\sites\demos\*.json" | ForEach-Object { $_.BaseName })) {
  [IO.File]::WriteAllText("$src\$id.html", [IO.File]::ReadAllText("$root\dist\sites\$id\index.html", $enc).Replace('</head>', "$inject</head>"), $enc)
  # Headless Chrome will not make a window under ~500px, so the phone view renders in a 375px iframe.
  [IO.File]::WriteAllText("$src\$id-phone.html", "<!doctype html><html><head><meta charset=`"utf-8`"><style>html,body{margin:0;background:#fff}iframe{display:block;width:375px;height:780px;border:0}</style></head><body><iframe src=`"$id.html`"></iframe></body></html>", $enc)
  $base = "file:///$($src.Replace('\','/'))"
  foreach ($s in @(@("$id-d.png", '1280,800', '0.5', "$base/$id.html"), @("$id-m.png", '520,780', '0.8', "$base/$id-phone.html"))) {
    Start-Process -FilePath $chrome -Wait -NoNewWindow -RedirectStandardError "$root\dist\gallery\err.txt" -ArgumentList @(
      '--headless=new', '--disable-gpu', '--hide-scrollbars', '--allow-file-access-from-files', '--virtual-time-budget=5000',
      "--force-device-scale-factor=$($s[2])", "--window-size=$($s[1])", "--screenshot=$shots\$($s[0])", $s[3]) | Out-Null
  }
}
Write-Output "$((Get-ChildItem $shots).Count) screenshots in dist/gallery/shots"
