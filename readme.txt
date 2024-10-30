=== Ledenbeheer ===
Contributors: ciryk
Requires at least: 5.5
Tested up to: 5.6
Requires PHP: 7.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 2.1.0

Externe connectie tussen Wordpress en Ledenbeheer. Synchronisatie van profielen, cursussen en activiteiten. Gebruik de shortcodes om de verschillende onderdelen weer te geven.

== Description ==

Gebruik deze plugin om alle cursussen, profielen alsook activiteiten to synchroniseren vanuit Ledenbeheer.
Als de synchronisatie compleet is kan je deze weergeven in kalender of tabel overzicht.

Ledenbeheer zal ieder uur vragen aan jouw site om zichzelf up-to-date te houden, je kan deze synchronisatie ook forceren vanuit Ledenbeheer.

== Installation ==

Stap 1: installeer de Ledenbeheer WordPress plugin
Op je WordPress website ga je naar “plugins > nieuwe plugin”.
Zoek naar Ledenbeheer en installeer de plugin.
Of upload de plugin via FTP.

Stap 2: activeer de plugin

Stap 3: schakel externe connectie in op je Ledenbeheer profiel.
Dit kan je doen door naar https://www.ledenbeheer.be/admin te surfen en vervolgens “Instellingen > externe koppeling”.
Zet “Gebruik van externe koppeling” aan, sla hierna de pagina op.

Stap 4: API key invullen
Ga op je WordPress site naar “instellingen > Ledenbeheer”
Verbind Ledenbeheer met jouw site door het Club ID en de API key bij de instellingen in te vullen.

Meer informatie nodig over onze plugin?
https://www.ledenbeheer.be/kennisdatabank/externe-koppeling/

== Changelog ==

= 2.1.0 =
* Bij fouten bij het ophalen van gegevens, wordt niet alle data gewist

= 2.0.9 =
* Render datums in juiste formaat

= 2.0.8 =
* Datums verkeerd weergegeven wanneer gebruiken maken van shortcodes
* Extra HTML element rond de cursus of activiteit shortcode voor extra styling

= 2.0.7 =
* Fout opvangen bij foutieve login

= 2.0.6 =
* Detail op Ledenbeheer krijgt nieuwe URL, deze aangepast zodat "Meer details" nog steeds werkt

= 2.0.5 =
* Wordpressinstallatie in subfolder gaf problemen tijdens sync omdat function get_plugin_data niet beschikbaar was

= 2.0.4 =
* Probleem oplossen verkeerde weergave maand in kalender
* Koppeling met nieuwe media voor cursus afbeelding
* Toon afgelopen cursussen en activiteiten in kalender, in de lijstweergave enkel de actieve

= 2.0.3 =
* Toevoegen van een changelog
* Vertalingen toevoegen voor het Nederlands
* Enkele problemen met synchronisatie oplossen
* Typfout "Eindatum" => "Einddatum"
