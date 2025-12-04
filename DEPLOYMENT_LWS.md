# Guide de Déploiement Laravel sur LWS

Ce guide vous explique comment déployer votre backend Laravel sur LWS (LWS Hosting) à partir de GitHub.

## 📋 Prérequis

- Compte LWS avec accès SSH
- Dépôt GitHub avec votre code
- Base de données MySQL/MariaDB créée sur LWS
- PHP 8.2+ installé sur le serveur
- Accès au panel de contrôle LWS

---

## 🚀 Étape 1 : Préparer le dépôt GitHub

### 1.1 Pousser le code vers GitHub

```bash
# Dans votre projet local
cd /Applications/myProject/centreDeRechercheBumba/backendCentre/backendCentre

# Vérifier le remote
git remote -v

# Si pas de remote, l'ajouter
git remote add origin https://github.com/VOTRE_USERNAME/VOTRE_REPO.git

# Pousser le code
git push -u origin main
```

### 1.2 Vérifier que tous les fichiers sont commités

Assurez-vous que tous les fichiers nécessaires sont dans le dépôt (sauf `.env` qui doit être ignoré).

---

## 🔐 Étape 2 : Se connecter au serveur LWS

### 2.1 Connexion SSH

```bash
ssh votre_utilisateur@votre_serveur.lws-hosting.com
# ou
ssh votre_utilisateur@IP_DU_SERVEUR
```

### 2.2 Vérifier la version PHP

```bash
php -v  # Doit être >= 8.2
```

### 2.3 Vérifier les extensions PHP requises

```bash
php -m | grep -E "pdo_mysql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|fileinfo"
```

Extensions requises :
- `pdo_mysql`
- `mbstring`
- `openssl`
- `tokenizer`
- `xml`
- `ctype`
- `json`
- `bcmath`
- `fileinfo`

---

## 📥 Étape 3 : Cloner le dépôt GitHub

### 3.1 Aller dans le répertoire web

```bash
# Le répertoire peut varier selon votre configuration LWS
cd ~/www
# ou
cd ~/public_html
# ou
cd /var/www/html
```

### 3.2 Cloner le dépôt

```bash
git clone https://github.com/VOTRE_USERNAME/VOTRE_REPO.git backendCentre
cd backendCentre
```

---

## 📦 Étape 4 : Installer les dépendances

### 4.1 Installer Composer (si pas déjà installé)

```bash
# Télécharger Composer
php -r "copy('https://getcomposer.org/installer', 'composer.phar');"
php composer.phar install --no-dev --optimize-autoloader

# Ou utiliser Composer global si disponible
composer install --no-dev --optimize-autoloader
```

### 4.2 Installer les dépendances PHP

```bash
composer install --no-dev --optimize-autoloader
```

---

## ⚙️ Étape 5 : Configuration de l'environnement

### 5.1 Créer le fichier .env

```bash
cp .env.example .env
nano .env
```

### 5.2 Configurer les variables d'environnement

Modifiez les valeurs suivantes dans `.env` :

```env
APP_NAME="Centre de Recherche"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://backend.creffpme.org

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=votre_base_de_donnees
DB_USERNAME=votre_utilisateur_db
DB_PASSWORD=votre_mot_de_passe_db

FRONTEND_URL=https://creffpme.org
SANCTUM_STATEFUL_DOMAINS=creffpme.org,www.creffpme.org,backend.creffpme.org
```

### 5.3 Générer la clé d'application

```bash
php artisan key:generate
```

---

## 🗄️ Étape 6 : Configuration de la base de données

### 6.1 Créer la base de données via le panel LWS

1. Connectez-vous au panel LWS
2. Allez dans "Bases de données" ou "MySQL"
3. Créez une nouvelle base de données
4. Créez un utilisateur avec tous les privilèges sur cette base

### 6.2 Ou créer via SSH

```bash
mysql -u root -p
```

```sql
CREATE DATABASE votre_base_de_donnees CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'votre_utilisateur_db'@'localhost' IDENTIFIED BY 'votre_mot_de_passe_db';
GRANT ALL PRIVILEGES ON votre_base_de_donnees.* TO 'votre_utilisateur_db'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 6.3 Exécuter les migrations

```bash
php artisan migrate --force
```

---

## 🔧 Étape 7 : Configuration des permissions

### 7.1 Donner les permissions nécessaires

```bash
# Permissions pour storage et cache
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Si nécessaire, changer le propriétaire
chown -R www-data:www-data storage bootstrap/cache
# ou
chown -R votre_utilisateur:votre_groupe storage bootstrap/cache
```

### 7.2 Créer le lien symbolique pour le storage

```bash
php artisan storage:link
```

---

## 🌐 Étape 8 : Configuration du serveur web

### 8.1 Vérifier le fichier .htaccess

Le fichier `public/.htaccess` doit déjà être présent. Vérifiez qu'il contient bien les règles de réécriture.

### 8.2 Configuration dans le panel LWS

1. **Créer un sous-domaine** `backend.creffpme.org` dans le panel LWS
2. **Pointer le document root** vers : `/chemin/vers/votre/projet/public`
   - Exemple : `~/www/backendCentre/public`

### 8.3 Configuration Apache (si accès disponible)

Si vous avez accès à la configuration Apache, créez un VirtualHost :

```apache
<VirtualHost *:80>
    ServerName backend.creffpme.org
    DocumentRoot /chemin/vers/backendCentre/public

    <Directory /chemin/vers/backendCentre/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/backend_error.log
    CustomLog ${APACHE_LOG_DIR}/backend_access.log combined
</VirtualHost>
```

### 8.4 Configuration Nginx (si LWS utilise Nginx)

```nginx
server {
    listen 80;
    server_name backend.creffpme.org;
    root /chemin/vers/backendCentre/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

---

## 🚀 Étape 9 : Optimisation pour la production

### 9.1 Optimiser l'application

```bash
# Optimiser la configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimiser l'autoloader
composer dump-autoload --optimize
```

### 9.2 Vérifier les permissions

```bash
# S'assurer que les permissions sont correctes
ls -la storage
ls -la bootstrap/cache
```

---

## 🔒 Étape 10 : Configuration SSL/HTTPS

### 10.1 Activer SSL dans le panel LWS

1. Allez dans la section SSL/TLS du panel LWS
2. Activez SSL pour `backend.creffpme.org`
3. Utilisez Let's Encrypt (gratuit) ou un certificat personnalisé

### 10.2 Mettre à jour APP_URL

Assurez-vous que `APP_URL=https://backend.creffpme.org` dans votre `.env`

---

## ✅ Étape 11 : Vérification

### 11.1 Tester l'API

```bash
# Tester depuis le serveur
curl https://backend.creffpme.org/api/actualities

# Ou depuis votre machine locale
curl https://backend.creffpme.org/api/actualities
```

### 11.2 Vérifier les logs

```bash
# Voir les logs en temps réel
tail -f storage/logs/laravel.log

# Vérifier les erreurs
cat storage/logs/laravel.log | grep ERROR
```

### 11.3 Tester l'authentification

```bash
curl -X POST https://backend.creffpme.org/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"ethberg@example.com","password":"password123"}'
```

---

## 🔄 Déploiement automatique

### Utiliser le script de déploiement

Un script `deploy.sh` est disponible pour automatiser le déploiement :

```bash
# Rendre le script exécutable
chmod +x deploy.sh

# Exécuter le déploiement
./deploy.sh
```

---

## 🛠️ Commandes utiles

### Voir les logs

```bash
tail -f storage/logs/laravel.log
```

### Nettoyer les caches

```bash
php artisan optimize:clear
```

### Vérifier la configuration

```bash
php artisan config:show
```

### Tester la connexion à la base de données

```bash
php artisan tinker
>>> DB::connection()->getPdo();
```

### Vérifier les routes

```bash
php artisan route:list
```

---

## 🔧 Dépannage

### Problème : Erreur 500

1. Vérifier les logs : `tail -f storage/logs/laravel.log`
2. Vérifier les permissions : `ls -la storage bootstrap/cache`
3. Vérifier le fichier `.env` : `cat .env`
4. Vérifier la clé d'application : `php artisan key:generate`

### Problème : Erreur de connexion à la base de données

1. Vérifier les identifiants dans `.env`
2. Tester la connexion : `php artisan tinker` puis `DB::connection()->getPdo();`
3. Vérifier que la base de données existe : `mysql -u root -p -e "SHOW DATABASES;"`

### Problème : Fichiers non accessibles (storage)

1. Créer le lien symbolique : `php artisan storage:link`
2. Vérifier les permissions : `chmod -R 755 storage`
3. Vérifier le propriétaire : `chown -R www-data:www-data storage`

### Problème : CORS

1. Vérifier la configuration dans `config/cors.php`
2. Vérifier `FRONTEND_URL` dans `.env`
3. Vérifier `SANCTUM_STATEFUL_DOMAINS` dans `.env`

---

## 📞 Support

En cas de problème :

1. Vérifier les logs Laravel : `storage/logs/laravel.log`
2. Vérifier les logs du serveur web (Apache/Nginx)
3. Contacter le support LWS pour la configuration serveur
4. Vérifier la documentation Laravel : https://laravel.com/docs

---

## 🔐 Sécurité

### Checklist de sécurité

- [ ] `APP_DEBUG=false` en production
- [ ] Fichier `.env` non accessible publiquement
- [ ] Permissions correctes sur `storage/` et `bootstrap/cache/`
- [ ] SSL/HTTPS activé
- [ ] Mots de passe forts pour la base de données
- [ ] Clé d'application générée (`APP_KEY`)
- [ ] `.env` dans `.gitignore` (ne pas commiter)

---

## 📝 Notes importantes

1. **Ne jamais commiter le fichier `.env`**
2. **Toujours utiliser HTTPS en production**
3. **Mettre à jour régulièrement les dépendances** : `composer update`
4. **Sauvegarder régulièrement la base de données**
5. **Surveiller les logs pour détecter les erreurs**

---

## 🎉 Félicitations !

Votre backend Laravel est maintenant déployé sur LWS ! 🚀

