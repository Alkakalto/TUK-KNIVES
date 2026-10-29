# Installation på cPanel

## 1. Förbered webbhotellet

Installera i domänens dokumentrot, exempelvis `/home/KONTO/public_html`. Sökvägarna utgår från `/`; en underkatalog kräver anpassning av länkar och omskrivningar. Säkerhetskopiera den befintliga webbplatsen först.

Välj en underhållen PHP-version, minst PHP 8.2, med PDO MySQL, mbstring, fileinfo, GD (JPEG/PNG/WebP) och OpenSSL. Använd MySQL 8 eller MariaDB 10.6+ med InnoDB. Ingen Node.js, Composer eller byggprocess behövs på servern: PHPMailer 7.1.1 och dess licens ingår i `vendor/PHPMailer`.

Apache/LiteSpeed ska läsa `.htaccess` och stödja mod_rewrite. Ladda upp webbplatsfilerna med dolda `.htaccess`-filer inkluderade. Ladda inte upp `.git`, `Schedual`, lokala testmiljöer eller säkerhetskopior. Dokumentation, tester, SQL och CLI-skript kan förvaras utanför dokumentroten efter installation; reglerna blockerar webbåtkomst till dem när de ligger kvar.

I cPanels MultiPHP INI Editor:

```ini
upload_max_filesize = 8M
post_max_size = 100M
max_file_uploads = 12
memory_limit = 256M
max_execution_time = 120
display_errors = Off
log_errors = On
```

Sätt mappar till 755 och vanliga filer till 644. PHP-användaren måste kunna skriva i `uploads/`; använd aldrig 777. Bilder granskas, avkodas och kodas om på servern, får slumpade namn och levereras med körningsskydd. Kontrollera att GD har WebP-stöd. Ställ PHP:s fellogg utanför dokumentroten.

## 2. Skapa databasen

Skapa en tom UTF-8-databas och en separat databasuser i cPanels MySQL Database Wizard. Ge den användaren åtkomst till just denna databas. Importera `database/001_initial.sql` en gång via phpMyAdmin. Detta är första migrationen; den ska inte importeras över en redan installerad databas. Inga äldre databasmigrationer behövdes eftersom originalprojektet saknade serverlagring.

Efter installation behöver webbapplikationen SELECT, INSERT, UPDATE och DELETE. Schemaändringar kräver separat migrationsbehörighet. Välj ett långt, unikt databaslösenord.

## 3. Skyddad konfiguration

Kopiera `config.example.php` till `/home/KONTO/tuk-config.php`, alltså en nivå ovanför `public_html`. Fyll i:

| Fält | Värde att ange |
|---|---|
| `base_url` | Din riktiga HTTPS-adress utan avslutande snedstreck |
| `force_https` | `true` i produktion |
| `db.dsn` | Värd, databasnamn inklusive cPanel-prefix och `charset=utf8mb4` |
| `db.user`, `db.password` | Databasens separata användare och lösenord |
| `app_key` | 64 slumpade hextecken, genererade enligt kommandot nedan |
| `smtp.host`, `smtp.port` | Webbhotellets SMTP-server och port, normalt 587 eller 465 |
| `smtp.encryption` | `tls` för STARTTLS/587 eller `ssl` för implicit TLS/465 |
| `smtp.username`, `smtp.password` | SMTP-kontots uppgifter |
| `smtp.from_email`, `smtp.from_name` | Avsändaradress som kontot får använda och butiksnamn |

Generera appnyckeln i cPanels terminal:

```bash
php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'
```

Konfigurationsfilen ska vara läsbar av PHP-processen men inte av andra konton, vanligtvis rättighet 600. Den ska aldrig ligga i Git eller i den publika katalogen. Alternativ placering kan anges med miljövariabeln `TUK_CONFIG`, både för webb-PHP och CLI/cron.

Aktivera HTTPS med cPanels SSL/TLS-funktion. Servern ska ange `HTTPS=on` till PHP. Webbplatsen litar inte automatiskt på klientskickade proxyheaders. Om hotellet har omvänd proxy behöver hotellet konfigurera detta korrekt för att undvika omdirigeringsloopar.

SMTP-certifikat verifieras; stäng inte av TLS-kontrollen. Konfigurera avsändardomänens SPF/DKIM/DMARC enligt e-postleverantörens instruktioner. Detta utförs inte av implementationen.

## 4. Skapa första ägarkontot en gång

Det finns ingen publik registrering, inget installationsformulär på webben och inget standardlösenord. Kör följande i cPanels terminal med den PHP-binär som hör till din valda PHP-version. Lösenordet läses dolt av Bash och skickas via standard input, inte som argument eller i kommandots historik:

```bash
cd /home/KONTO/public_html
read -r -s -p 'Nytt ägarlösenord (16–72 byte): ' TUK_OWNER_PASSWORD
printf '\n'
printf '%s' "$TUK_OWNER_PASSWORD" | php bin/create-owner.php agare@example.se
unset TUK_OWNER_PASSWORD
```

Välj ett unikt lösenord från en lösenordshanterare. Skriptet lagrar bara en `password_hash` och använder ett databaslås. När en ägare finns vägrar skriptet skapa fler. Notifieringsadressen sätts samtidigt. Ta gärna bort `bin/create-owner.php` från servern efteråt. Om terminalåtkomst saknas, be webbhotellet hjälpa till med samma CLI-metod; lägg inte till ett publikt setup-skript.

Logga in på `/admin`. Fyll i Inställningar: kontaktuppgifter, notifieringsadress, Swish-nummer, mottagarnamn, frakttext och om sålda knivar ska visas. SMTP-lösenord och databaslösenord visas aldrig där.

## 5. Mejl och cron

Förfrågan och två mejluppdrag sparas i samma databastransaktion. Kunden omdirigeras till sin kvittens efter sparandet. Kvittenssidan försöker leverera mottagningsmejlen; om kunden lämnar sidan finns mejlen kvar för cron. Betalningsmejl köas först när ägaren uttryckligen klickar på skickaknappen efter förhandsgranskning.

Lägg till ett cron-jobb varje minut i cPanel, exempelvis:

```cron
* * * * * /usr/local/bin/php /home/KONTO/public_html/bin/send-mail.php >> /home/KONTO/tuk-mail.log 2>&1
```

Kontrollera PHP-sökvägen med webbhotellet. Cron och webb-PHP måste använda samma konfiguration. Rotera loggen och begränsa åtkomsten. Cron skickar bara väntande mejl. Misslyckade eller osäkra leveranser visas i ägarpanelen och kräver kontroll innan ägaren väljer nytt försök. SMTP kan inte garantera exakt en leverans om en anslutning bryts efter att mottagarservern accepterat mejlet. Ett pågående mejl kan provas igen efter tio minuter, efter kontroll av faktisk leverans.

Fel i SMTP innebär aldrig att förfrågan raderas eller att kunden ska skicka en ny. Ändrad notifieringsadress används vid nytt försök med ägarens notifieringsmejl.

## 6. Kontroller innan drift

- Se att `/admin` leder till inloggning utan session.
- Kontrollera att `/app/bootstrap.php`, `/database/001_initial.sql`, `/config.example.php`, `/.git/config` och filer i `/vendor` ger 403/404, utan filinnehåll.
- Kontrollera att `uploads/.htaccess` gäller och att endast bildfiler levereras från uppladdningsmappen.
- Skapa en egen testprodukt. Testa bilder, publicering, sökning, förfrågan, reservation, betalningsmejl och manuell betalningskontroll.
- Skicka mottagnings- och betalningsmejl till adresser du kontrollerar. Verifiera faktisk leverans, skräpposthantering och avsändare. Testa SMTP-fel och nytt försök.
- Testa på telefon: meny, katalog, galleri, beställningsformulär, inloggning, bilduppladdning och orderhantering.
- Kontrollera rätt företagsuppgifter och komplettera integritetstexten med verksamhetens faktiska lagringstider och rutiner innan drift.

## Säkerhetskopiering och återställning

Säkerhetskopiera både databasen och hela `uploads/`, samt källkoden och den skyddade konfigurationen. Kopior av bara källkoden räcker inte. Förvara krypterade kopior utanför den publika katalogen och helst även utanför webbhotellet. Begränsa åtkomsten eftersom databasen innehåller kunduppgifter och mejlinnehåll.

Använd cPanels Backup/Backup Wizard eller en schemalagd databasdump med `mariadb-dump --single-transaction` / `mysqldump --single-transaction` samt filbackup. Lägg databaslösenord i en skyddad klientkonfigurationsfil, inte i kommandoraden. Gör kopian när inga uppladdningar pågår eller använd hotellets samordnade snapshotfunktion.

Prova återställning i en separat, stängd testmiljö: importera dumpen, återställ bilderna med samma filnamn, koppla testkonfigurationen och kontrollera produkter/orderhistorik. Stäng av mejlcron i återställningstestet så att gamla mejl inte går till kunder. Återställ konfigurationens appnyckel vid katastrofåterställning.

Borttagna produkter mjukraderas för att bevara historik. Bildfiler som inte längre används kan finnas kvar på disk; radera dem inte utan att kontrollera referenser från samtliga produkter, inklusive kopior och arkiverade produkter. Automatisk gallring av kunduppgifter ingår inte; ägaren behöver bestämma och följa en gallringsrutin.
