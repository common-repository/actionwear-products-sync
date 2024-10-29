# ActionWear Wordpress Plugin

## Start dev environment

```console
npm run docker:dev
```

### Install WP (inside container)

```console
./docker:install-wp.sh
```

### SET debug mode to false

Add this line after debug mode in wp-config.php

```console
@ini_set('display_errors', 1);
```

### Execute WP-CLI commands (inside container)

```console
wp --allow-root <command>
```

### Login to WP Backend

Username: wordpress
Password: wordpress

### Routes for debug purposes

#### /archive_update

Triggera la sync differenziale di prodotti, categorie ed immagini. Quella che avviene ogni 36 ore.

#### /wc_created_align

Sincronizza i wc_created ed i wc_to_create con i prodotti esistenti su woocommerce.