=== Actionwear products sync ===

Contributors: buggyzap
Tags: actionwear
Requires at least: 5.0
Tested up to: 6.6.1
Stable tag: 2.3.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Aggiungi e sincronizza i prodotti di ActionWear sul tuo WooCommerce

== Description ==

Questo plugin ti permette di aggiungere e sincronizzare i prodotti di ActionWear sul tuo WooCommerce.
Grazie ad una semplice interfaccia, seleziona i prodotti che vuoi importare partendo da un semplice SKU o da una Categoria.
Una volta sincronizzati i prodotti, sarai in grado di gestire le tabelle di ricarico per i prodotti Actionwear sulla base o di una detemrinata categoria o del brand.
Funzionalità
* Aggiungi e sincronizza i prodotti di ActionWear
* Crea regole di prezzo personalizzate in base a brand o categoria
* Associa le categorie di ActionWear con quelle di WooCommerce
* Ricevi e Inserisci informazioni utili riguardo il restock dei prodotti direttamente all\'interno della scheda prodotto 
* Monitora le operazioni effettuate dai cron tramite il pannello registri

== Installation ==

Per installare il plugin è sufficiente seguire le istruzioni della procedura guidata, durante la quale verrà richiesto di inserire la vostra API Key, di cui dovrete preventivamente essere in possesso. 
Se non siete in possesso di tale informazione, rivolgetevi al supporto di ActionWear a info@camacartigrafiche.com.

== Frequently Asked Questions ==


= Posso mettere in offerta un prodotto? =

 La gestione delle offerte deve essere fatta tramite coupon, in quanto i prezzi dei singoli prodotti, per ora, non può essere gestita.

= Posso cambiare i prezzi dei prodotti? =

 Si, puoi modificare le tabelle di ricarico. Per far questo devi andare in 'Listini' e, una volta effettuate le modifiche alle tabelle, effettua una risincronizzazione dei prodotti nella sezione 'Avanzate'.

= Posso cambiare la base del ricarico (ad esempio da Categorie a Brand) una volta terminata la procedura guidata?  =

 Una volta terminata la procedura guidata non è possibile cambiare la base del ricarico. In questo caso è necessario resettare le impostazioni tramite il pannello Avanzate e procedere con una nuova.

= Posso cancellare il listino Ricarico Globale? =

 No, il listino Ricarico Globale viene generato in automatico e non può essere cancellato in quanto gestisce il ricarico di tutti i prodotti ActionWear per il quale non è stato impostato un ricarico specifico.

= Posso modificare le immagini di un prodotto? =

 Si, Avanzate -> Abilita la modifica delle immagini

= Posso eliminare un prodotto? =

 Si. Per eliminare un prodotto le operazioni da effettuare sono 2: Prima devi deselzionare il prodotto dalla sezione 'Prodotti' e in seguito cancellarlo dalla lista prodotti di woocommerce. 

= Una volta ricevuto l'ordine come posso inoltrarlo a Camac? =

 Puoi esportare l'ordine in un file .csv compatibile con il sito action-wear. Per farlo recati nell'ordine dal menu di woocommerce, all'interno dell'ordine troverai come azione aggiuntiva la possibilità di esportarlo per ActionWear. Il file generato potrà essere caricato sul sito action-wear.com nell'area 'ordine da csv'

= Cosa posso fare se un CronJob risulta bloccato? =

 Puoi sbloccare manualmente un CronJob tramite l'apposito comando del pannello 'Avanzate'.

= Perchè non vedo le disponibilità del fornitore all'interno della scehda prodotto? =

 Nel caso in cui le quantità di riassortimento siano uguali a 0, non verrà visualizzata alcune informazione aggiuntiva.

== Screenshots ==

1. Scegli i prodotti ActionWear da sincronizzare sul tuo WooCommerce partendo da un semplice SKU o da una Categoria
2. Imposta le tabelle di ricarico in base a un determinato Brand o a una Categoria
3. Associa una o più Categorie dei prodotti ActionWear con le Categorie del tuo WooCommerce

== Changelog ==


= 2.3.0 =

* Added a new page that show products that are not available anymore on ActionWear but still present on WooCommerce
* Added multiple checkbox selection on import page after completing the onboarding
* Added a flag under the resync products that let you to choose if you want to resync all products or only missing in WooCommerce
* Added a new task that let you to resync all images of products
* Added a new product delete task to combinations that have price 0
* Changed the task failure post-action to retry the task instead of delete it
* Changed timeout to 90 seconds to get all actionwear skus on onboarding and differential product import
* Chnaged verbosity for some log messages
* Fixed a bug that doesn't show the task queue lists in some cases

= 2.2.1 =

* Changed the timeout time of task queue from 50 to 110 seconds to prevent duplicated tasks
* Fixed a bug of camac placeholder image that doesn't show up on frontend
* Fixed a bug that sometimes show others plugins queue tasks on plugin dashboard

= 2.2.0 =

* Added a new Queue view on dashboard to show tasks in progress and delete them if needed
* Added a Circuit Breaker during every API call to show a notice if API is not reachable
* Created a new task to update prices programmatically on all products
* Added a new post meta to track original product price before price markup
* Added a new trigger in advanced settings to reapply price recharge on demand
* Added the possibility to filter synced / not synced products on sku selection
* Improved module security
* Changed some logs with additional details to help debugging
* Changed brand_id from M2 to the brand_id present during product creation
* Changed the way that establish how many products are synced
* Fixed a bug that doesn't apply price recharge 

= 2.1.7 =

* Fixed a bug that doesn't associate product images where sku has more than 3 parts

= 2.1.6 =

* Fixed a bug that show products count as 0 on dashboard even if there are actionwear products
* Fixed a bug that duplicate categories on associations view

= 2.1.5 =

* 6.4 compatibility

= 2.1.4 =

* Fixed supplier_availability default view setting
* Fixed a scenario when a supplier view table doesn't shown up on frontend
* Prevent sku of actionwear_products table to be duplicated on product differential sync

= 2.1.3 =

* Fixed cronjob differential timing
* Added log on attribute creation

= 2.1.2 =

* Full products resync doesn't occur even if resync button was pressed
* Exception generated during images import

= 2.1.1 =

* Product doesn't delete/edit cover image even the option is enabled
* Log on terms error

= 2.1.0 =

* Added notice if wp-json is not reachable or rewrite rules are not working
* Added log if pricesall is empty
* Disabled notion faqs on pre-release process
* Hard reset not working properly
* Attributes skipping if camac_color_group is empty
* Mismatch between real wordpress url and site url
* Missing full path on images cause an error if wordpress is installed on a subfolder

= 2.0.1 =

 - WP 6.2.2 compatibility

= 2.0.0 =

* Facultative selection of all CAMAC attributes to make them taxonomies on a new OnBoarding 4 step
* Products import by languages: IT, EN, FR, ES on a new OnBoarding 4 step
* Attribute camac_color_group is now a taxonomy by default
* Plugin multilanguage support: IT, EN
* New dashboard pages: Attributes, Language
* Trigger to resyncronize only categories associations
* Setting to temporarily freeze all syncronizations
* Setting to toggle products images modification
* Setting to toggle Differential Price Sync
* Setting to toggle Price Markup Tables
* Database versioning and migration system
* Deleted products from Action-Wear.com API are now automatically deleted from plugin sync and your website database
* Info modal is now triggered when pressing 'Reset settings'
* Button in backoffice product page to resync a specific single product
* Hard reset button in plugins page
* Background products processing is now handled by a queue system which automatically adapts to you server's capabilities
* Setting to choose how many products to import per process removed
* Unlock Cronjob setting removed
* Label 'Usa immagini del configurabile' is now 'Usa immagini del prodotto indossato'
* Debug Mode is now false by default
* Products images when 'Usa immagini del prodotto indossato' setting is disabled -- bugfix
* Url redirecting -- bugfix
* Interface general fixes

= 1.1.27 =

* Differential price schema

= 1.1.24 =

* Product categories association fixed

= 1.1.22 =

* Assets file inclusion

= 1.1.21 =

* Github wordpress publish action
* Node update faqs and pdf creation from node file to npx command

= 1.1.9 =

* Created Docker image where run tests
* Codeception Tests suite
* Workflow that run tests on docker image that install WP, WooCommerce, ActionWear and run tests
* giorniconsegnafornitore to wp metas
* Standard Product attributes to real WooCommerce Attributes that works with taxonomies
* Supplier and Actionwear quantities frontend views and content
* Db Tests

= 1.1.0 =

* Controllo su stato logged delle API json del modulo in modo da prevenire chiamate esterne non da backend
* Visualizzazione detailed su prodotti con tante varianti
* Replica immagini delle taglie su sku contenente il simbolo "+"

= 1.0.80 = - 2022-02-10

* Pagina prodotti dove è possibile selezionare in qualsiasi momento i prodotti che si intendono rimuovere/aggiungere alla sincronizzazione
* Sezione in Home del plugin con i due plugin consigliati
* Banner sincronizzato con vostre API esterne
* Trigger che mostra un avviso se presente una invalidations da vostra api esterna, chiede di risincronizzare i prodotti e si può avviare il resync totale dei prodotti (non vengono eliminati e ricreati ma semplicemente aggiornati)
* Polling su pagina Registri per avere la view sempre aggiornata
* Implementazione del listino pubblico, impostando di default il ricarico a 150 (2,5)
* Configurazione per preferenza su immagini del configurabile (copyright sui modelli) + automazione lato import. Aggiunto anche in fase di onboarding.
* Procedura di sincronizzazione differenziale sulla base delle vostre API. Attualmente le sync si aggiornano ogni 5 minuti per le quantità e ogni ora per i prezzi.
* Preferenza su mostrare/nascondere disponibilità fornitore + scelta del tipo di vista.
* Implementazione lato frontend della vista delle disponibilità
* Controlli preliminari per rilevamento configurazioni non adatte o obbligatorie lato server
* Controllo sull'esistenza di WC anche in fase di attivazione del plugin
* Pulsante di esportazione in formato .csv per gli ordini e dei soli prodotti di ActionWear
* Avviso che permette il resync se almeno una delle chiamate API di ActionWear fallisce
* Resize dinamico immagine in base a contesto
* Sincronizzazione prodotti adesso non rimuove le entità ma le aggiorna
* Assegnazione automatica alla taglia che contiene l'immagine, alle altre taglie corrispondenti
* Chiamata API pricesall e listino

= 1.0.2 = - 2022-09-11

* Typo

= 1.0.1 = - 2022-09-11

* Test release

= 1.0.0 = - 2022-09-11

* Tutti i file della repository
