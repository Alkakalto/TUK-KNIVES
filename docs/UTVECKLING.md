# Arbeta vidare i VS Code och GitHub

## Lokal körning

Öppna hela projektmappen i VS Code. PHP-katalogen och adminpanelen behöver PHP och en separat MySQL/MariaDB-databas även lokalt. Live Server eller att dubbelklicka en HTML-fil kör inte PHP. GitHub Pages kan inte heller köra denna backend.

1. Installera PHP 8.2+ med pdo_mysql, mbstring, fileinfo, GD och OpenSSL, samt MySQL 8/MariaDB 10.6+ på utvecklingsdatorn.
2. Skapa en lokal databas och importera `database/001_initial.sql` i den en gång.
3. Kopiera `config.example.php` till `tuk-config.php` en nivå ovanför projektmappen (utanför webbplatsens dokumentrot). Ange lokala databasuppgifter, `base_url` som `http://127.0.0.1:8765`, `force_https` som `false` och en slumpad appnyckel. Använd test-SMTP eller en avsiktligt otillgänglig adress under utveckling.
4. Skapa ägarkontot enligt installationsguiden. På Windows kan du använda PowerShell-kommandot nedan. Lösenordet läses dolt och skickas via standard input, inte som argument.
5. I VS Code: **Terminal → Run Task → TUK: Starta lokal webbplats**. Ange `php` eller sökvägen till din `php.exe`. Öppna `http://127.0.0.1:8765` i webbläsaren.
6. Stoppa servern med Ctrl+C i aktivitetens terminal. Kör **TUK: Kontrollera PHP-filer** efter ändringar.

Windows, från projektmappen (byt e-postadress och PHP-sökväg):

```powershell
$tukSecure = Read-Host 'Nytt ägarlösenord (16–72 byte)' -AsSecureString
$tukPointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($tukSecure)
try {
    $tukPlain = [Runtime.InteropServices.Marshal]::PtrToStringBSTR($tukPointer)
    $tukPlain | & 'C:\sökväg\php.exe' bin/create-owner.php agare@example.se
} finally {
    [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($tukPointer)
    Remove-Variable tukPlain -ErrorAction SilentlyContinue
    $tukSecure.Dispose()
}
```

Om du använder en annan konfigurationsplats, sätt miljövariabeln `TUK_CONFIG` till filens absoluta sökväg innan VS Code/servern startas. Lägg aldrig riktiga lösenord i versionshanterade VS Code-inställningar.

Utvecklingsroutern blockerar interna filer eftersom PHP:s lokala server inte läser `.htaccess`. Bind bara servern till 127.0.0.1. För produktion används Apache/LiteSpeed och HTTPS enligt installationsguiden.

## GitHub

Repot innehåller källkod, schema, bibliotek och dokumentation. Databasens produkter/order, uppladdade bilder och hemligheter är driftdata och ska säkerhetskopieras separat. `.gitignore` skyddar vanliga lokala hemligheter och uppladdningar; granska ändå diffen innan du skickar kod.

Arbeta gärna i en egen branch i VS Code, exempelvis `feature/katalog`. Använd Source Control för att granska ändrade filer, skapa en commit och publicera branchen. Ta bara med filer som hör till webbplatsen. Den separata mappen `Schedual` har inte granskats för publicering i denna leverans.

GitHub Actions-filen `.github/workflows/check.yml` kör PHP-syntaxkontroll, JavaScript-syntaxkontroll och databasens integrationstester mot en tillfällig MySQL-databas vid push/pull request. Den publicerar ingenting hos Miss Hosting. CI-databaslösenordet gäller endast den kortlivade testcontainern och är inte ett produktionslösenord.

## Specialbeställningar

Den befintliga sidan för specialbeställda knivar öppnar nu ett mejlutkast i kundens e-postprogram. Kunden måste själv skicka mejlet. Dessa fria designförfrågningar ligger inte i produktordrarnas databas. För en publicerad kniv används katalogens serverlagrade beställningsformulär med referensnummer.

## Inför webbhotellet

Följ `docs/INSTALLATION.md`. Använd separat produktionsdatabas, produktionskonfiguration och SMTP. Kontrollera faktisk mejlleverans, mobilgränssnitt, HTTPS och serverns `.htaccess`-skydd. En push till GitHub ändrar inte den publicerade webbplatsen hos Miss Hosting.
