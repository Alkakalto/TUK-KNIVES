# TUK-KNIVES – produktkatalog och ägarpanel

HTML/CSS/JavaScript, PHP och MySQL/MariaDB för installation i domänens dokumentrot på cPanel. Ingen Node-process eller betald extern tjänst behövs i produktion. Den befintliga startsidan finns kvar i `EN/index.html`; `/` leder dit. Sortimentet finns på `/catalog.php`, ägarpanelen på `/admin`.

- [Installera på cPanel](docs/INSTALLATION.md)
- [Arbeta i VS Code och GitHub](docs/UTVECKLING.md)
- [Ägarguide](docs/AGARGUIDE.md)
- [Verifiering och återstående driftkontroller](docs/VERIFIERING.md)

Databasen skapas med `database/001_initial.sql`. Verkliga hemligheter ska ligga utanför dokumentroten, i `tuk-config.php`. `config.example.php` innehåller bara exempel.

Denna leverans publicerar ingenting och ändrar inte DNS. Testprodukter, testkonton, lokal databas och testkonfiguration ingår inte i installationspaketet.
