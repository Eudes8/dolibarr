#!/bin/bash
set -e

echo "============================================"
echo "  PayrollCI - Installation Dolibarr"
echo "============================================"

# --- 1. MariaDB ---
echo "📦 Démarrage MariaDB..."
sudo service mariadb start || sudo mysqld_safe &
sleep 3

echo "🗄️ Création base de données..."
sudo mysql -e "CREATE DATABASE IF NOT EXISTS dolibarr CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER IF NOT EXISTS 'dolibarr'@'localhost' IDENTIFIED BY 'dolibarr';"
sudo mysql -e "GRANT ALL PRIVILEGES ON dolibarr.* TO 'dolibarr'@'localhost';"
sudo mysql -e "FLUSH PRIVILEGES;"

# --- 2. PHP extensions ---
echo "📦 Installation extensions PHP..."
sudo apt-get update -qq
sudo apt-get install -y -qq php8.2-mysql php8.2-intl php8.2-gd php8.2-mbstring php8.2-curl php8.2-xml php8.2-zip > /dev/null 2>&1 || true
# Enable extensions if using docker-php-ext
sudo docker-php-ext-install mysqli pdo pdo_mysql gd intl > /dev/null 2>&1 || true

# --- 3. Télécharger Dolibarr ---
DOLI_VERSION="19.0.3"
DOLI_DIR="/workspaces/dolibarr-app"

if [ ! -d "$DOLI_DIR/htdocs" ]; then
    echo "⬇️ Téléchargement Dolibarr $DOLI_VERSION..."
    cd /tmp
    wget -q "https://github.com/Dolibarr/dolibarr/archive/refs/tags/${DOLI_VERSION}.tar.gz" -O dolibarr.tar.gz
    tar xzf dolibarr.tar.gz
    sudo mv "dolibarr-${DOLI_VERSION}" "$DOLI_DIR"
    rm dolibarr.tar.gz
    echo "✅ Dolibarr téléchargé"
else
    echo "✅ Dolibarr déjà installé"
fi

# --- 4. Lier le module PayrollCI ---
echo "🔗 Liaison module PayrollCI..."
REPO_ROOT=$(git rev-parse --show-toplevel 2>/dev/null || echo "/workspaces/dolibarr")
sudo mkdir -p "$DOLI_DIR/htdocs/custom"
sudo ln -sf "$REPO_ROOT/htdocs/custom/payrollci" "$DOLI_DIR/htdocs/custom/payrollci"

# --- 5. Configurer Dolibarr ---
echo "⚙️ Configuration Dolibarr..."
sudo mkdir -p /var/lib/dolibarr/documents
sudo chmod -R 777 /var/lib/dolibarr/documents

cat << 'CONF' | sudo tee "$DOLI_DIR/htdocs/conf/conf.php" > /dev/null
<?php
$dolibarr_main_url_root = 'http://localhost:8080';
$dolibarr_main_document_root = '/workspaces/dolibarr-app/htdocs';
$dolibarr_main_url_root_alt = '/custom';
$dolibarr_main_document_root_alt = '/workspaces/dolibarr-app/htdocs/custom';
$dolibarr_main_data_root = '/var/lib/dolibarr/documents';
$dolibarr_main_db_host = 'localhost';
$dolibarr_main_db_port = '3306';
$dolibarr_main_db_name = 'dolibarr';
$dolibarr_main_db_prefix = 'llx_';
$dolibarr_main_db_user = 'dolibarr';
$dolibarr_main_db_pass = 'dolibarr';
$dolibarr_main_db_type = 'mysqli';
$dolibarr_main_db_character_set = 'utf8';
$dolibarr_main_db_collation = 'utf8_unicode_ci';
$dolibarr_main_authentication = 'dolibarr';
$dolibarr_main_force_https = '0';
CONF

# --- 6. Installer les tables Dolibarr ---
echo "🗄️ Installation base Dolibarr..."
cd "$DOLI_DIR/htdocs/install"
php step2/step1.php > /dev/null 2>&1 || true

# Import core SQL files
for sql in $(find "$DOLI_DIR/htdocs/install/mysql/tables" -name "*.sql" -not -name "*.key.sql" | sort | head -80); do
    mysql -u dolibarr -pdolibarr dolibarr < "$sql" 2>/dev/null || true
done
for sql in $(find "$DOLI_DIR/htdocs/install/mysql/tables" -name "*.key.sql" | sort | head -80); do
    mysql -u dolibarr -pdolibarr dolibarr < "$sql" 2>/dev/null || true
done
# Data
for sql in $(find "$DOLI_DIR/htdocs/install/mysql/data" -name "*.sql" | sort | head -40); do
    mysql -u dolibarr -pdolibarr dolibarr < "$sql" 2>/dev/null || true
done

# Create admin user
mysql -u dolibarr -pdolibarr dolibarr -e "
INSERT IGNORE INTO llx_user (login, pass_crypted, admin, statut, entity, lastname, firstname, datec)
VALUES ('admin', '\\\$2y\\\$10\\\$rounds=10000\\\$abcdefghijklmn\\\$KJGxkFQuSEXz2O0S/gMVXJMhcV9h3KJ1e', 1, 1, 1, 'Admin', 'Super', NOW());
" 2>/dev/null || true

# --- 7. Tables PayrollCI ---
echo "📋 Installation tables PayrollCI..."
PAYROLL_SQL="$REPO_ROOT/htdocs/custom/payrollci/sql"
if [ -d "$PAYROLL_SQL" ]; then
    for sql in $(find "$PAYROLL_SQL" -name "*.sql" | sort); do
        mysql -u dolibarr -pdolibarr dolibarr < "$sql" 2>/dev/null || true
        echo "   ✅ $(basename $sql)"
    done
fi

# --- 8. Lancer le serveur PHP ---
echo ""
echo "🚀 Démarrage serveur PHP sur port 8080..."
cd "$DOLI_DIR/htdocs"
nohup php -S 0.0.0.0:8080 -t . > /tmp/php-server.log 2>&1 &

echo ""
echo "============================================"
echo "  ✅ PRÊT !"
echo "============================================"
echo ""
echo "  🌐 Dolibarr : http://localhost:8080"
echo "  👤 Login    : admin / admin"
echo "  📁 Module   : $REPO_ROOT/htdocs/custom/payrollci/"
echo ""
echo "  Pour relancer le serveur :"
echo "  cd /workspaces/dolibarr-app/htdocs && php -S 0.0.0.0:8080"
echo ""
