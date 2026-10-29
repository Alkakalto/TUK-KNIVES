# Verifiering

## Utfört lokalt

Miljö: Windows, PHP 8.4.27, MariaDB 11.4.5 i separat lokal testdatabas. Testmiljön följer inte med till webbhotellet. PHP-testruntime hämtades från officiella PHP-distributionen och dess SHA-256 kontrollerades. PHPMailer 7.1.1 hämtades från projektets versionstagg.

35 integrationstester av serverlogiken passerade, bland annat:

- Utkast, publicering, redigering, arkivering, återställning, duplicering och mjukradering.
- Val av huvudbild, normaliserad bildordning och borttagning av en bild.
- HTML-escaping, beloppsvalidering och avvisning av gamla redigeringsversioner.
- Produktpris hämtas från databasen trots manipulerat klientpris.
- Upprepat inskick ger samma förfrågan och referens, även efter reservation.
- Förfrågan reserverar inte automatiskt; bekräftelse gör det.
- Reserverade och sålda produkter avvisas av beställningstjänsten.
- Andra förfrågan kan inte bekräfta samma unika kniv.
- Betalningsutskick kräver oförändrad förhandsgranskning; betald kräver manuell kontroll.
- Avbokning häver reservation; gamla orderuppgifter bevaras när produkten tas bort.
- SMTP-anslutningsfel lagras utan att ordern försvinner.
- Rate limiter och daterad orderhistorik.

39 HTTP-/samtidighetskontroller passerade, bland annat:

- Samtliga adminvyer avvisar obehöriga; korrekt/felaktig inloggning och utloggning.
- HttpOnly/SameSite-cookie och CSRF-avvisning.
- Skapa produkt med riktig multipart-bilduppladdning, publicera och redigera.
- Förklädd PHP-fil med JPG-namn och påstådd JPEG-typ avvisas.
- Dubbelt produktinskick ger samma produkt.
- Publikt galleri, pris, sökning, tomläge och ägarknappar beroende på session.
- Beställningsformulär, kvittens, åtkomstskydd för kvittens och mejlfel i panelen.
- Reservation, uttryckligt betalningsutskick och manuell betalningsmarkering.
- Två samtidiga PHP-processer mot två databasanslutningar: exakt en bekräftelse lyckades och databasen innehöll exakt en aktiv reservation.
- Skyddade filsökvägar gav 403 via den lokala testserverns router.

PHP-filerna syntaxkontrollerades och JavaScript kontrollerades med `node --check`.

Två separata kontroller av engångsinstallationen passerade: första ägaren skapades med lösenord via standard input; ett andra försök att skapa en ägare avvisades.

## Återstår på webbhotellet

## Uppföljning: VS Code och GitHub

De 35 serverlogiktesterna och 39 HTTP-/samtidighetstesterna kördes om efter tilläggen för lokal utveckling. Samtliga passerade. 28 PHP-filer syntaxkontrollerades utan fel; JavaScript-filerna kontrollerades med `node --check`.

Startsida, Om oss och Kniv på beställning returnerade HTTP 200. Utvecklingsroutern avvisade interna filer, VS Code-inställningar, en URL-kodad intern sökväg och en otillåten fil i uploads. Specialbeställningar öppnar ett mejlutkast, inte en serverlagrad katalogorder.

GitHub Actions-konfigurationen är tillagd men en lyckad körning på GitHub måste verifieras efter att koden har pushats. Visuell mobilgranskning och extern SMTP-leverans återstår fortfarande.

## Återstående driftkontroller

- Verklig SMTP-leverans till ägare och kund, inklusive giltigt TLS-certifikat, avsändarbehörighet, mottagning och skräppostfilter. Lokalt testades den verkliga felvägen med en otillgänglig SMTP-server; framgångsrik extern leverans har inte påståtts vara verifierad.
- Apache/LiteSpeed `.htaccess`, nekad direktåtkomst och körningsskydd för uppladdningar. Den lokala PHP-servern använder en test-router, inte Apache.
- Produktions-HTTPS, Secure-cookie, cron, filrättigheter, uppladdningsgränser, backup och återställning.
- Visuell och interaktiv granskning i en faktisk mobilwebbläsare. Layouten har responsiva CSS-regler, synliga etiketter, minst 44 px knappar och mobilmeny, men browser-verktyget fastnade vid lokal granskning. Ingen godkänd visuell mobiltest hävdas.

## Kör om serverlogiktesterna

Använd en separat, tom databas vars namn slutar med `_test`. Importera schemat, sätt `TUK_CONFIG` till separat testkonfiguration och välj en avsiktligt otillgänglig SMTP-server. Kör aldrig tester mot kunddata: tester skapar produkter/order och ändrar testinställningar.

```bash
TUK_CONFIG=/säker/sökväg/test-config.php TUK_RUN_TESTS=1 php tests/integration.php
```

HTTP-testerna kördes med en separat testklient och lokala testfixturer. De lokala testkontona, nycklarna och databasen ingår inte i produktionsleveransen.
