# PassForm — Guide de déploiement cPanel (hébergement mutualisé)

## Prérequis à vérifier chez votre hébergeur

- PHP **8.2 ou 8.3** disponible via "Select PHP Version"
- Extensions PHP requises : `mbstring`, `xml`, `curl`, `zip`, `gd`, `intl`, `bcmath`, `pdo_mysql`
- MySQL 5.7+ ou MariaDB 10.4+
- Composer disponible via SSH **ou** installable manuellement
- Accès SSH (recommandé) ou File Manager cPanel

---

## Étape 1 — Préparer la base de données

1. Dans cPanel → **MySQL Databases**
2. Créer une base : `votre_prefix_passform`
3. Créer un utilisateur : `votre_prefix_pf`
4. Choisir un mot de passe fort (noter le)
5. Ajouter l'utilisateur à la base avec **Tous les privilèges**

---

## Étape 2 — Uploader les fichiers

### Option A — Via SSH (recommandé)
```bash
# Se connecter au VPS
ssh votre_login@votre-domaine.fr

# Naviguer dans le dossier web
cd ~/public_html   # ou ~/www selon l'hébergeur

# Uploader l'archive du projet (depuis votre machine)
# scp passform.zip votre_login@votre-domaine.fr:~/public_html/

# Décompresser
unzip passform.zip -d passform/
```

### Option B — Via File Manager cPanel
1. cPanel → File Manager → `public_html`
2. Uploader le zip PassForm
3. Faire un clic droit → Extraire

---

## Étape 3 — Configurer le document root

Le dossier public de Laravel (`public/`) doit être le document root.

**Option A — Sous-domaine dédié (recommandé)**
1. cPanel → **Subdomains** (ou Domaines)
2. Créer `passform.votre-domaine.fr`
3. Document Root → pointer vers `public_html/passform/public`

**Option B — Dossier dans le domaine principal**
Créer un alias ou utiliser `.htaccess` à la racine (voir ci-dessous).

---

## Étape 4 — Configurer le .htaccess

Le fichier `public/.htaccess` de Laravel gère déjà le routage.
Vérifiez que `mod_rewrite` est activé (cPanel → "Apache Handlers" ou demander à l'hébergeur).

Contenu du `public/.htaccess` standard Laravel (déjà inclus dans le projet) :
```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## Étape 5 — Configurer le .env

Via SSH ou File Manager, éditer le fichier `.env` à la racine du projet :

```env
APP_NAME="PassForm"
APP_ENV=production
APP_KEY=                    # Sera généré à l'étape suivante
APP_DEBUG=false
APP_URL=https://passform.votre-domaine.fr
APP_TIMEZONE=Europe/Paris
APP_LOCALE=fr

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=votre_prefix_passform
DB_USERNAME=votre_prefix_pf
DB_PASSWORD=VOTRE_MOT_DE_PASSE

CACHE_STORE=file            # Pas de Redis en mutualisé
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
FILESYSTEM_DISK=local
```

---

## Étape 6 — Installer les dépendances (Composer)

### Via SSH
```bash
cd ~/public_html/passform
php8.3 /usr/local/bin/composer install --no-dev --optimize-autoloader
```

### Sans SSH — Script PHP de bootstrap
Si SSH n'est pas disponible, uploader ce fichier à la racine du projet et l'exécuter une fois via l'URL :

```php
<?php
// install.php — À SUPPRIMER APRÈS UTILISATION
if ($_GET['key'] !== 'VOTRE_CLE_SECRETE_TEMPORAIRE') die('Accès refusé');
passthru('cd ' . __DIR__ . ' && php composer.phar install --no-dev 2>&1');
```

> ⚠️ Supprimer ce fichier immédiatement après utilisation.

---

## Étape 7 — Initialiser l'application

```bash
# Générer la clé APP_KEY (chiffrement NIR — NE JAMAIS RÉGÉNÉRER ENSUITE)
php8.3 artisan key:generate

# Créer les tables et données initiales
php8.3 artisan migrate --seed --force

# Optimiser
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache

# Lien symbolique storage
php8.3 artisan storage:link
```

---

## Étape 8 — Configurer le Scheduler Laravel (Cron Jobs)

1. cPanel → **Cron Jobs**
2. Ajouter une tâche toutes les minutes :

```
* * * * * /usr/bin/php8.3 /home/VOTRE_LOGIN/public_html/passform/artisan schedule:run >> /dev/null 2>&1
```

> Remplacer `VOTRE_LOGIN` par votre nom d'utilisateur cPanel.
> Le chemin vers php peut varier : `/usr/local/bin/php8.3` ou `/opt/php83/bin/php`.

---

## Étape 9 — Permissions des dossiers

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage/logs
```

Via File Manager cPanel : clic droit sur le dossier → Change Permissions.

---

## Étape 10 — Vérification finale

1. Accéder à `https://passform.votre-domaine.fr/admin`
2. Se connecter avec :
   - Email : `admin@passform.local`
   - Mot de passe : `PassForm2025!`
3. **Changer immédiatement le mot de passe**

---

## ⚠️ Points critiques RGPD

| Point | Action requise |
|-------|---------------|
| **APP_KEY** | Sauvegarder dans un gestionnaire de secrets. Ne JAMAIS régénérer après mise en production — les NIR chiffrés deviendraient illisibles. |
| **Sauvegardes** | Configurer des sauvegardes MySQL quotidiennes (cPanel → Backup Wizard ou plugin JetBackup si disponible). |
| **HTTPS** | Vérifier que le certificat SSL est actif (cPanel → SSL/TLS → Let's Encrypt). |
| **Logs** | Les logs Laravel sont dans `storage/logs/`. Accès restreint via `.htaccess`. |

---

## Mise à jour de l'application

```bash
# 1. Activer le mode maintenance
php8.3 artisan down

# 2. Uploader les nouveaux fichiers

# 3. Mettre à jour les dépendances
php8.3 composer install --no-dev --optimize-autoloader

# 4. Appliquer les migrations
php8.3 artisan migrate --force

# 5. Vider les caches
php8.3 artisan config:cache
php8.3 artisan route:cache
php8.3 artisan view:cache

# 6. Désactiver la maintenance
php8.3 artisan up
```

---

## Compatibilité hébergeurs testée

| Hébergeur | Compatibilité | Notes |
|-----------|--------------|-------|
| o2switch  | ✅ Excellent  | SSH inclus, PHP 8.3, Composer disponible |
| Infomaniak| ✅ Très bon   | PHP 8.3, SSH, cron standard |
| OVH Pro   | ✅ Bon        | PHP 8.2/8.3, SSH en option |
| PlanetHoster | ✅ Bon    | LiteSpeed, PHP 8.3 |
| 1&1 IONOS | ⚠️ Partiel   | Vérifier accès SSH et Composer |
| Free (Prixtel) | ❌    | PHP trop ancien |
