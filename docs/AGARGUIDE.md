# Ägarguide

## Lägg upp en kniv

1. Logga in på `/admin` och välj **Lägg till produkt**. Samma knapp finns överst i katalogen när du är inloggad.
2. Fyll i namn, pris i kronor, kategori, beskrivning, material och mått. Produktnumret skapas automatiskt.
3. Välj en eller flera JPG-, PNG- eller WebP-bilder. Du ser förhandsvisningar innan du sparar. Högst 12 bilder, högst 8 MB per bild.
4. Välj **Utkast** och **Spara produkt**. Välj sedan huvudbild och ordning bland de uppladdade bilderna och spara igen. Bilder med lägre ordningsnummer kommer först; huvudbilden kommer alltid först.
5. Välj **Förhandsgranska sparad produkt**. När allt är klart: välj **Publicerad** och spara. Minst en bild behövs vid publicering.

Utkast syns bara när du är inloggad. Ändra till **Arkiverad** för att dölja en produkt och behålla den. **Återställ till utkast** gör den redigerbar igen utan att publicera direkt. **Duplicera** skapar ett nytt utkast med eget nummer och tillgänglig status. **Ta bort** kräver en bekräftelseruta; använd arkivering om du vill kunna återställa.

## Hantera en beställningsförfrågan

1. Öppna **Förfrågningar**. Nya förfrågningar finns även om notifieringsmejlet misslyckades.
2. Öppna förfrågan och kontrollera kund, leveransadress och produktpriset vid förfrågan. Interna anteckningar visas aldrig för kunden.
3. Ange fraktkostnaden och välj **Bekräfta och reservera kniven**. Totalpris är sparat produktpris plus frakt. Bara en förfrågan kan reservera en viss kniv. Andra förfrågningar finns kvar men kan inte bekräftas för samma kniv.
4. Skriv eventuell extra betalningsinstruktion och spara underlaget. Swish-nummer, mottagare, totalbelopp och referens läggs till automatiskt för Sverige. För andra länder skriver du den överenskomna betalningsinstruktionen.
5. Läs hela förhandsgranskningen och välj uttryckligen **Skicka betalningsinstruktion till kunden**. Status blir **Inväntar betalning**. Kontrollera mejlstatus nedanför. Frakt och totalpris är låsta efter detta steg.

En inskickad förfrågan är inte en reservation. Bekräftelsen till kunden uppmanar kunden att vänta med betalning tills du har kontrollerat tillgänglighet och skickat totalpris och betalningsuppgifter.

## Betalning och leverans

1. Kontrollera själv i Swish/banken att rätt belopp har kommit in med rätt referens.
2. Kryssa i kontrollrutan och välj **Markera betald och kniven som såld**. Kunden kan inte göra detta.
3. När paketet är skickat: välj **Markera ordern som skickad**.
4. Om affären avbryts: öppna **Avbryt förfrågan och häv reservation**. Kniven blir tillgänglig igen. Vid redan mottagen betalning måste eventuell återbetalning hanteras och bekräftas manuellt. Skickade order kan inte avbrytas i panelen.

För en kniv som sålts utanför webbplatsen kan du ändra till **Såld** direkt i produktformuläret. Har kniven en kopplad order ska ändringarna göras i ordern. Under Inställningar väljer du om sålda knivar ska ligga kvar som tidigare arbeten.

## När något inte fungerar

- **Misslyckat mejl:** förfrågan är sparad. Kontrollera SMTP med webbhotellet, kontrollera om mejlet ändå kommit fram och välj därefter **Försök skicka igen**. Be inte kunden skicka en dubblett.
- **Ändrad i annan flik:** ladda om produkten/förfrågan och kontrollera de aktuella uppgifterna innan du sparar igen.
- **Uppladdningen misslyckas:** kontrollera filtyp, storlek och bildmått. Vid formulärfel behöver valda lokala filer väljas på nytt.
- **Utloggad:** sessionen upphör efter 30 minuters inaktivitet. Logga in och öppna formuläret igen.
- **För många inloggningsförsök:** vänta minst 15 minuter.

Spara regelbundet och logga ut när du är klar, särskilt på delade datorer. Orderhistoriken visar sparade statusändringar med datum och tid.
