#!/bin/bash
# ============================================================
#  PassForm — Script de déploiement initial VPS Linux
#  Testé sur : Ubuntu 22.04 / 24.04, Debian 12
#  Prérequis : accès root ou sudo, domaine pointant sur le VPS
# ============================================================
set -e

# ── Variables à personnaliser ─────────────────────────────────────────────────
DOMAINE="passform.votre-domaine.fr"
DB_NAME="passform"
DB_USER="passform_user"
DB_PASS="$(openssl rand -base64 24)"
APP_DIR="/var/www/passform"
PHP_VERSION="8.3"

echo "========================================"
echo "  PassForm — Déploiement VPS"
echo "========================================"

# ── 1. Mise à jour système ────────────────────────────────────────────────────
echo "[1/10] Mise à jour du système..."
apt-get update -qq && apt-get upgrade -y -qq

# ── 2. Installation PHP 8.3 + extensions ─────────────────────────────────────
echo "[2/10] Installation PHP ${PHP_VERSION}..."
apt-get install -y -qq software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update -qq
apt-get install -y -qq \
    php${PHP_VERSION} \
    php${PHP_VERSION}-fpm \
    php${PHP_VERSION}-mysql \
    php${PHP_VERSION}-mbstring \
    php${PHP_VERSION}-xml \
    php${PHP_VERSION}-curl \
    php${PHP_VERSION}-zip \
    php${PHP_VERSION}-gd \
    php${PHP_VERSION}-intl \
    php${PHP_VERSION}-bcmath \
    php${PHP_VERSION}-redis

# ── 3. Nginx ──────────────────────────────────────────────────────────────────
echo "[3/10] Installation et configuration Nginx..."
apt-get install -y -qq nginx

cat > /etc/nginx/sites-available/passform << EOF
server {
    listen 80;
    server_name ${DOMAINE};
    root ${APP_DIR}/public;
    index index.php;

    client_max_body_size 10M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Interdit l'accès direct aux signatures stockées (passage par Laravel uniquement)
    location /storage/signatures {
        deny all;
    }
}
EOF

ln -sf /etc/nginx/sites-available/passform /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx

# ── 4. MySQL ──────────────────────────────────────────────────────────────────
echo "[4/10] Configuration MySQL..."
apt-get install -y -qq mysql-server

mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

echo "  Base de données créée : ${DB_NAME}"
echo "  Utilisateur : ${DB_USER}"
echo "  Mot de passe : ${DB_PASS}  ← À SAUVEGARDER"

# ── 5. Composer ───────────────────────────────────────────────────────────────
echo "[5/10] Installation Composer..."
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# ── 6. Node.js (pour les assets Filament) ────────────────────────────────────
echo "[6/10] Installation Node.js..."
curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
apt-get install -y -qq nodejs

# ── 7. Déploiement de l'application ──────────────────────────────────────────
echo "[7/10] Déploiement de l'application..."
mkdir -p ${APP_DIR}

# Si dépôt Git configuré :
# git clone https://votre-repo.git ${APP_DIR}
# Sinon, copier les fichiers manuellement et continuer

cd ${APP_DIR}
cp .env.example .env

# Remplacement des variables dans .env
sed -i "s|DB_DATABASE=passform|DB_DATABASE=${DB_NAME}|" .env
sed -i "s|DB_USERNAME=passform_user|DB_USERNAME=${DB_USER}|" .env
sed -i "s|DB_PASSWORD=CHANGEZ_MOI|DB_PASSWORD=${DB_PASS}|" .env
sed -i "s|APP_URL=https://votre-domaine.fr|APP_URL=https://${DOMAINE}|" .env

composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── 8. Permissions ────────────────────────────────────────────────────────────
echo "[8/10] Configuration des permissions..."
chown -R www-data:www-data ${APP_DIR}
find ${APP_DIR} -type f -exec chmod 644 {} \;
find ${APP_DIR} -type d -exec chmod 755 {} \;
chmod -R 775 ${APP_DIR}/storage ${APP_DIR}/bootstrap/cache

# ── 9. Cron pour le Scheduler Laravel (purge RGPD + mises à jour statuts) ────
echo "[9/10] Configuration du Scheduler Laravel..."
(crontab -l 2>/dev/null; echo "* * * * * www-data cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

# ── 10. Certificat SSL Let's Encrypt ─────────────────────────────────────────
echo "[10/10] Installation du certificat SSL..."
apt-get install -y -qq certbot python3-certbot-nginx
certbot --nginx -d ${DOMAINE} --non-interactive --agree-tos --email admin@${DOMAINE} --redirect

# ── Résumé ────────────────────────────────────────────────────────────────────
echo ""
echo "========================================"
echo "  Déploiement terminé !"
echo "========================================"
echo ""
echo "  URL application : https://${DOMAINE}/admin"
echo "  Identifiants admin par défaut :"
echo "    Email    : admin@passform.local"
echo "    Mot de passe : PassForm2025!"
echo ""
echo "  ⚠  ACTIONS REQUISES APRÈS DÉPLOIEMENT :"
echo "  1. Changer le mot de passe admin au premier login"
echo "  2. Configurer l'email dans .env (MAIL_*)"
echo "  3. Sauvegarder APP_KEY dans un gestionnaire de secrets"
echo "     (elle chiffre les NIR — ne jamais la régénérer)"
echo "  4. Planifier des sauvegardes MySQL quotidiennes"
echo ""
echo "  DB_PASS=${DB_PASS}"
echo ""
