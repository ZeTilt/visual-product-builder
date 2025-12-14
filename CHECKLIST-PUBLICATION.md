# Checklist de Publication WordPress.org

## État actuel : Prêt à 85%

---

## ✅ Déjà fait

- [x] Fichier `readme.txt` (format WordPress.org)
- [x] Fichier `uninstall.php` (nettoyage désinstallation)
- [x] Fichier `LICENSE` (GPL v2)
- [x] Internationalisation complète (anglais de base)
- [x] Text domain : `visual-product-builder`
- [x] Fonction `load_plugin_textdomain()`
- [x] Headers du plugin complets
- [x] Version 1.0.0
- [x] Auteur : Alré Web (alre-web.bzh)

---

## ⏳ À faire avant soumission

### 1. Fichier de traduction française (optionnel mais recommandé)

```bash
# Installer WP-CLI si pas déjà fait
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Générer le fichier .pot
cd /chemin/vers/visual-product-builder
wp i18n make-pot . languages/visual-product-builder.pot
```

Puis utiliser **Poedit** ou **Loco Translate** pour créer :
- `languages/visual-product-builder-fr_FR.po`
- `languages/visual-product-builder-fr_FR.mo`

---

### 2. Assets visuels (OBLIGATOIRE)

Créer ces images et les mettre dans un dossier `assets/` (pour WordPress.org SVN, pas dans le plugin) :

| Fichier | Dimensions | Description |
|---------|------------|-------------|
| `banner-772x250.png` | 772 × 250 px | Bannière standard |
| `banner-1544x500.png` | 1544 × 500 px | Bannière Retina |
| `icon-128x128.png` | 128 × 128 px | Icône standard |
| `icon-256x256.png` | 256 × 256 px | Icône Retina |
| `screenshot-1.png` | ~1200 × 900 px | Configurateur frontend |
| `screenshot-2.png` | ~1200 × 900 px | Interface admin |
| `screenshot-3.png` | ~1200 × 900 px | Gestion des collections |

**Conseils design :**
- Bannière : fond avec logo + tagline + illustration du produit
- Icône : simple, reconnaissable, pas trop de détails
- Screenshots : annoter si nécessaire

---

### 3. Compte WordPress.org

1. Créer un compte sur https://login.wordpress.org/register
2. Compléter le profil développeur
3. Activer 2FA (recommandé)

---

### 4. Compte Ko-fi (pour les dons)

1. Créer un compte sur https://ko-fi.com
2. Configurer ta page
3. Mettre à jour l'URL dans `readme.txt` ligne 3 :
   ```
   Donate link: https://ko-fi.com/ton-pseudo
   ```

---

### 5. Créer le ZIP de soumission

```bash
cd /chemin/vers/
zip -r visual-product-builder.zip visual-product-builder \
    -x "*.git*" \
    -x "*.DS_Store" \
    -x "*node_modules*" \
    -x "*.log" \
    -x "CHECKLIST-PUBLICATION.md"
```

---

## 🚀 Processus de soumission

### Étape 1 : Soumettre le plugin
1. Aller sur https://wordpress.org/plugins/developers/add/
2. Uploader le fichier ZIP
3. Remplir les informations
4. Soumettre

### Étape 2 : Attendre la review
- Délai : 1 à 14 jours
- Tu recevras un email si modifications demandées
- Répondre rapidement augmente les chances

### Étape 3 : Après approbation
1. Tu reçois un accès SVN
2. Commiter le code dans `trunk/`
3. Créer un tag `tags/1.0.0/`
4. Uploader les assets dans `assets/`

---

## 📋 Vérifications finales

Avant de soumettre, vérifier :

- [ ] Plugin testé sur WordPress 6.4+
- [ ] Plugin testé sur WooCommerce 8.0+
- [ ] Plugin testé sur PHP 7.4 et 8.x
- [ ] Pas d'erreurs PHP dans les logs
- [ ] Pas de notices WordPress
- [ ] Toutes les fonctionnalités marchent
- [ ] Screenshots à jour

---

## 📞 Support

- Forum WordPress.org (après publication)
- Email : à définir
- Documentation : à créer (optionnel pour v1.0)

---

## Temps estimé restant

| Tâche | Temps |
|-------|-------|
| Assets visuels | 2-4h |
| Fichier .pot + traduction FR | 1h |
| Comptes (WP.org + Ko-fi) | 30min |
| Tests finaux | 1h |
| **Total** | **~5-7h** |

---

*Dernière mise à jour : 14 décembre 2024*
