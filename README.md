# IpHost - WHMCS module
Plugin για σύνδεση του API της ipHost με το WHMCS

[IpHost]: <https://iphost.net>

## Eγκατάσταση (Installation)

- Ανεβάστε τον φάκελο iphost μέσα στον φάκελο modules/registrars
- Από το configuration-> domain registrars->registrar settings επιλέξτε IpHost στη συνέχεια συμπληρώστε τo apiKey που έχετε από την IpHost
- Από το configuration->domain pricing προσθέστε όλα τα extensions που θέλετε να έχετε, καθώς και τις τιμές τις οποίες θα τα χρεώνετε. 

Στο Automatic Registration βάλτε IpHost για τα extensions τα οποία θέλετε να κατοχυρώνονται από την [IpHost].

## Αναζήτηση ονομάτων χώρου (Search)

- Δημιουργήστε ένα φάκελο με όνομα whois στο φάκελο που βρίσκεται το WHMCS και προσθέστε μέσα στο φάκελο το αρχείο whois.php
- Όπου στο αρχείο whois.json  λέει whmcs.iphost.net βάλτε το url για το δικό σας whois/whois.php το οποίο κάνατε upload.
- Ανεβάστε το αρχείο whois.json στον server, στο μονοπάτι /resources/domains/ όπως αναφέρει η WHMCS http://docs.whmcs.com/WHOIS_Servers

## Συγχρονισμός (Sync)

To WHMCS μπορεί να συγχρονίζει τα expiry_date και next_renew_date σύμφωνα με την [IpHost].

- Μεταβείτε στο Configuration > System Settings > Automation Settings και να επιλέξετε αυτά που θέλετε στις επιλογές:
- Domain Sync Enabled - Πρέπει να είναι τσεκαρισμένο για να λειτουργεί το SYNCHRONIZATION.
- Sync Next Due Date - Πρέπει να είναι τσεκαρισμένο αν θέλετε να ανανεώνονται και τα next due dates, όπως τα expiry πεδία.
- Domain Sync Notify Only - Πρέπει να είναι τσεκαρισμένο αν δε θέλετε να ανανεώνονται αυτόματα οι ημερομηνίες, απλά να στέλνεται ένα ενημερωτικό email στους admins. (https://docs.whmcs.com/Domain_Synchronisation)

- Για την ενεργοποίηση του cron φροντίστε για την ενεργοποίηση του. (http://docs.whmcs.com/Domains_Tab#Domain_Sync_Enabled)

```sh
php -q /path/to/home/public_html/whmcspath/crons/domainsync.php
```

Example for domain sync and pending transfer
```sh
php -q crons/cron.php do --DomainStatusSync --DomainTransferSync  -vvv
```
