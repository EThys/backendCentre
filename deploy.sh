#!/bin/bash

# Script de déploiement automatique Laravel sur LWS
# Usage: ./deploy.sh

set -e  # Arrêter en cas d'erreur

# Couleurs pour les messages
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}🚀 Déploiement Laravel sur LWS${NC}"
echo "================================"

# Vérifier qu'on est dans le bon répertoire
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Erreur: Ce script doit être exécuté depuis la racine du projet Laravel${NC}"
    exit 1
fi

# Aller dans le répertoire du projet
PROJECT_DIR=$(pwd)
echo -e "${YELLOW}📁 Répertoire: $PROJECT_DIR${NC}"

# 1. Récupérer les dernières modifications depuis GitHub
echo -e "\n${YELLOW}📥 Récupération des dernières modifications...${NC}"
if git pull origin main; then
    echo -e "${GREEN}✅ Code mis à jour${NC}"
else
    echo -e "${RED}❌ Erreur lors de la récupération du code${NC}"
    exit 1
fi

# 2. Installer/Mettre à jour les dépendances
echo -e "\n${YELLOW}📦 Installation des dépendances...${NC}"
if composer install --no-dev --optimize-autoloader; then
    echo -e "${GREEN}✅ Dépendances installées${NC}"
else
    echo -e "${RED}❌ Erreur lors de l'installation des dépendances${NC}"
    exit 1
fi

# 3. Vérifier que le fichier .env existe
if [ ! -f ".env" ]; then
    echo -e "\n${YELLOW}⚠️  Fichier .env non trouvé. Création depuis .env.example...${NC}"
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo -e "${YELLOW}⚠️  IMPORTANT: Veuillez configurer le fichier .env avant de continuer${NC}"
        echo -e "${YELLOW}   Puis exécutez: php artisan key:generate${NC}"
        exit 1
    else
        echo -e "${RED}❌ Fichier .env.example non trouvé${NC}"
        exit 1
    fi
fi

# 4. Exécuter les migrations
echo -e "\n${YELLOW}🗄️  Exécution des migrations...${NC}"
if php artisan migrate --force; then
    echo -e "${GREEN}✅ Migrations exécutées${NC}"
else
    echo -e "${RED}❌ Erreur lors des migrations${NC}"
    exit 1
fi

# 5. Créer le lien symbolique pour le storage
echo -e "\n${YELLOW}🔗 Création du lien symbolique storage...${NC}"
if [ ! -L "public/storage" ]; then
    if php artisan storage:link; then
        echo -e "${GREEN}✅ Lien symbolique créé${NC}"
    else
        echo -e "${YELLOW}⚠️  Le lien symbolique existe déjà ou erreur lors de la création${NC}"
    fi
else
    echo -e "${GREEN}✅ Lien symbolique déjà existant${NC}"
fi

# 6. Optimiser l'application
echo -e "\n${YELLOW}⚡ Optimisation de l'application...${NC}"

# Nettoyer les anciens caches
php artisan optimize:clear

# Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimiser l'autoloader
composer dump-autoload --optimize

echo -e "${GREEN}✅ Application optimisée${NC}"

# 7. Vérifier les permissions
echo -e "\n${YELLOW}🔐 Vérification des permissions...${NC}"
chmod -R 755 storage bootstrap/cache
echo -e "${GREEN}✅ Permissions configurées${NC}"

# 8. Vérifier la configuration
echo -e "\n${YELLOW}🔍 Vérification de la configuration...${NC}"
if php artisan config:show > /dev/null 2>&1; then
    echo -e "${GREEN}✅ Configuration valide${NC}"
else
    echo -e "${YELLOW}⚠️  Vérifiez la configuration${NC}"
fi

# Résumé
echo -e "\n${GREEN}================================${NC}"
echo -e "${GREEN}✅ Déploiement terminé avec succès !${NC}"
echo -e "${GREEN}================================${NC}"
echo ""
echo -e "${YELLOW}📝 Prochaines étapes:${NC}"
echo "1. Vérifier que l'application fonctionne: curl https://backend.creffpme.org/api/actualities"
echo "2. Vérifier les logs: tail -f storage/logs/laravel.log"
echo "3. Tester l'authentification"
echo ""
echo -e "${GREEN}🎉 Votre backend est maintenant déployé !${NC}"

