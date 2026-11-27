# Translation Manager Bundle

Pflegbare Übersetzungen für Contao (4.13 / 5.x). Texte werden im Backend als
Schlüssel/Wert-Paare je Sprache gepflegt und im Frontend per Insert-Tag oder PHP
ausgegeben.

## Installation

```bash
composer require alnv/translation-manager-bundle
php vendor/bin/contao-console contao:migrate
```

Das Backend-Modul **Übersetzer** wird in die Navigationsgruppe des
Catalog Manager Bundles (`catalog-manager-bundle`) eingehängt.

## Funktionsweise

### Datensätze (`tl_translation`)

| Feld          | Bedeutung                                                    |
|---------------|--------------------------------------------------------------|
| `language`    | Sprache (z. B. `de`, `en`)                                   |
| `name`        | Schlüssel, über den der Text abgerufen wird                  |
| `translation` | Übersetzter Text (HTML erlaubt)                              |
| `invisible`   | Deaktiviert – der Datensatz wird nicht ausgegeben            |

Ausgegeben werden nur aktive Datensätze der aktuellen Sprache
(`$GLOBALS['TL_LANGUAGE']`, sonst Request-Locale, sonst `de`). Gibt es einen
Schlüssel in einer Sprache mehrfach, gewinnt der erste Datensatz.

Schlüssel werden normalisiert: Die Zeichen `{ } ( ) / \ @ :` und Leerzeichen
werden entfernt. `{{translate::foo bar}}` sucht also nach `foobar`.

### Ausgabe

**Insert-Tag**

```
{{translate::schluessel}}
{{translate::schluessel::Fallback-Text}}
```

**PHP**

```php
use Alnv\ContaoTranslationManagerBundle\Library\Translation;

echo Translation::getInstance()->translate('schluessel', 'Fallback-Text');

// andere Sprache + Simple Tokens
echo Translation::getInstance('en')->translate('greeting', 'Hello ##name##', ['name' => 'Max']);
```

Ablauf beim Übersetzen:

1. Übersetzung zum Schlüssel suchen, sonst Fallback zurückgeben.
2. Enthaltene Insert-Tags (`{{…}}`) werden ersetzt.
3. Werden Daten übergeben, werden Simple Tokens (`##token##`) ersetzt.

### Platzhalter für unbekannte Schlüssel

Wird im **Frontend** ein Schlüssel *mit Fallback* angefragt, der noch nicht in
der Datenbank existiert, legt das Bundle automatisch einen **deaktivierten**
Datensatz an (`name` = Schlüssel, `translation` = Fallback, ohne Sprache). So
tauchen alle verwendeten Texte im Backend auf und müssen nur noch übersetzt,
mit Sprache versehen und aktiviert werden.

Konfiguration (`config/config.yaml`):

```yaml
alnv_contao_translation_manager:
    placeholders:
        enabled: true          # false = nie automatisch Platzhalter anlegen
        exclude:               # PCRE-Ausdrücke auf den normalisierten Schlüssel
            - '/\.\d+$/'       # z. B. Schlüssel mit numerischer ID am Ende
```

### Caching

- Übersetzungen und bekannte Schlüssel liegen im Cache-Pool
  `alnv.translation_manager.cache` (Basis `cache.app`, Lebensdauer 3600 s)
  und zusätzlich pro Request im Speicher.
- Beim Speichern, Löschen oder Wiederherstellen eines Datensatzes im Backend
  wird der Cache automatisch geleert (Contao-Cache-Tags `contao.db.tl_translation*`
  bzw. `onundo_callback`).
- Manuell: `php vendor/bin/contao-console cache:pool:clear alnv.translation_manager.cache`
- `cache:clear` entfernt außerdem alte Dateicaches (`var/cache/cm.translation.cache.*`)
  früherer Versionen.

### Migration

`Migration\NullValuesMigration` ersetzt Alt-`NULL`-Werte in `tstamp`, `name`
und `invisible`, bevor der Schema-Abgleich die Spalten auf `NOT NULL` umstellt
(sonst bricht MySQL mit *1265 Data truncated* ab). Sie läuft automatisch mit
`contao:migrate` bzw. dem Install-Tool und nur, solange solche Werte existieren.
