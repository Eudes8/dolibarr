# 🇨🇮 PayrollCI — Module de Paie Côte d'Ivoire pour Dolibarr

> **Version 2.0.0** — Gestion complète de la paie conforme au droit du travail ivoirien (2026)

<p align="center">
  <img src="https://img.shields.io/badge/Dolibarr-16%2B-blue" alt="Dolibarr 16+">
  <img src="https://img.shields.io/badge/PHP-7.4%2B-purple" alt="PHP 7.4+">
  <img src="https://img.shields.io/badge/Licence-GPL%20v3-green" alt="GPL v3">
  <img src="https://img.shields.io/badge/Pays-C%C3%B4te%20d'Ivoire-orange" alt="Côte d'Ivoire">
</p>

---

## 📖 Description

**PayrollCI** est un module personnalisé pour [Dolibarr ERP/CRM](https://www.dolibarr.org/) permettant de gérer intégralement la paie des salariés en Côte d'Ivoire. Il intègre tous les éléments de rémunération prévus par la **Convention Collective Interprofessionnelle (CCI)**, le **Code du Travail**, le **Code Général des Impôts** et la réglementation **CNPS/CMU** en vigueur en 2026.

### ✨ Fonctionnalités principales

- 📝 Création de bulletins de paie complets
- 📊 Calcul automatique de toutes les cotisations sociales et impôts
- 📄 Génération de bulletins de paie PDF conformes à l'Art. 46.2 CCI
- 📋 Liste et suivi de tous les bulletins
- ⚙️ Configuration par entreprise (secteur, ville, paramètres par défaut)

---

## 💰 Éléments de rémunération pris en charge

### Salaire de base
| Élément | Description |
|---------|-------------|
| Salaire catégoriel | Salaire de base selon la grille catégorielle |
| Sursalaire | Complément au-dessus du salaire catégoriel |

### 🏅 Primes CCI (12 primes)

| Prime | Description |
|-------|-------------|
| **Ancienneté** | Auto-calculée : 2% après 24 mois sur salaire catégoriel, +1% par année supplémentaire, plafond 25% |
| Rendement | Basée sur la performance individuelle |
| Technicité | Compétences techniques spécifiques |
| Fonction | Responsabilités liées au poste |
| Responsabilité | Niveau de responsabilité hiérarchique |
| Risque | Exposition à des conditions dangereuses |
| Outillage | Utilisation d'outils personnels |
| Salissure | Travaux salissants |
| Caisse | Manipulation de fonds |
| Assiduité | Régularité de présence |
| Panier | Repas lors de conditions spéciales |
| Gratification / 13ème mois | Max 75% du salaire catégoriel (prorata) |

### 🚗 Indemnités (6 types)

| Indemnité | Détails |
|-----------|---------|
| Transport | Exonéré selon la ville (voir tableau ci-dessous) |
| Logement | Compensation logement |
| Représentation | Frais de représentation |
| Expatriation | Prime d'expatriation |
| Déplacement | Frais de déplacement professionnel |
| Kilométrique | Remboursement kilométrique |

**Transport exonéré par ville :**

| Ville | Plafond exonéré |
|-------|----------------|
| Abidjan | 30 000 FCFA |
| Bouaké | 24 000 FCFA |
| Yamoussoukro, San-Pédro, Korhogo, Daloa, Autres | 20 000 FCFA |

### 🏠 Avantages en nature (5 types)

| Avantage | Description |
|----------|-------------|
| Logement | Valeur locative du logement fourni |
| Véhicule | Mise à disposition d'un véhicule |
| Domestique | Personnel de maison |
| Nourriture | Repas fournis |
| Autres | Tout autre avantage |

### ⏰ Heures supplémentaires (taux CCI)

| Type | Taux | Conditions |
|------|------|------------|
| HS 15% | +15% | 41ème à 46ème heure hebdomadaire |
| HS 50% | +50% | Au-delà de 46 heures |
| HS 75% | +75% | Nuit ou Dimanche/Jour férié |
| HS 100% | +100% | Nuit **et** Dimanche/Jour férié |

> **Base de calcul HS** = Salaire catégoriel + Sursalaire + Technicité + Rendement + Fonction + Responsabilité
> *(Excluant : ancienneté, assiduité, transport, etc.)*

---

## 🏦 Cotisations sociales CNPS

| Cotisation | Taux salarié | Taux patronal | Plafond mensuel |
|-----------|:------------:|:-------------:|:---------------:|
| Retraite | 6,3% | 7,7% | 3 375 000 FCFA |
| Prestations Familiales | — | 5,75% | 70 000 FCFA |
| Accident du Travail | — | 2% à 5% ¹ | 70 000 FCFA |
| CMU | 500 F/mois | 500 F/mois | forfaitaire |

¹ *Le taux AT varie selon le secteur d'activité (voir ci-dessous)*

### Taux AT par secteur d'activité

| Secteur | Taux AT |
|---------|:-------:|
| Commerce, Services, Banque/Assurance, Télécoms | 2,0% |
| Hôtellerie / Restauration | 2,5% |
| Industrie légère, Agriculture, Santé | 3,0% |
| Transport | 3,5% |
| BTP / Construction, Pétrole / Énergie | 4,0% |
| Mines / Extraction | 5,0% |

---

## 🏛️ Charges fiscales patronales

| Charge | Taux | Assiette |
|--------|:----:|----------|
| Impôt Employeur (IE) | 1,2% | Brut imposable |
| FDFP / Taxe d'Apprentissage (TA) | 0,4% | Masse salariale |
| FDFP / Formation Prof. Continue (FPC) | 0,6% | Masse salariale |

---

## 📊 ITS — Impôts sur Traitements et Salaires

### Impôt sur Salaire (IS)
- Taux : **1,5%** sur 80% du brut imposable

### Contribution Nationale (CN) — Barème progressif

| Tranche mensuelle (FCFA) | Taux |
|---------------------------|:----:|
| 0 — 50 000 | 0% |
| 50 001 — 130 000 | 1,5% |
| 130 001 — 200 000 | 5% |
| Au-delà de 200 000 | 10% |

### Impôt Général sur le Revenu (IGR) — Barème progressif

Calcul avec **quotient familial** (1 à 4 parts) :

| Quotient Q = R/N (FCFA) | Formule |
|--------------------------|---------|
| 0 — 25 000 | 0 |
| 25 001 — 45 583 | (R × 10/110) − 2 273 |
| 45 584 — 81 583 | (R × 15/115) − 4 076 |
| 81 584 — 126 583 | (R × 20/120) − 7 031 |
| 126 584 — 220 333 | (R × 25/125) − 11 250 |
| 220 334 — 389 083 | (R × 35/135) − 24 306 |
| 389 084 — 842 166 | (R × 45/145) − 44 181 |
| Au-delà de 842 166 | (R × 60/160) − 98 633 |

**Nombre de parts :**
| Situation | Parts |
|-----------|:-----:|
| Célibataire sans enfant | 1,0 |
| Marié(e) sans enfant | 2,0 |
| Par enfant à charge | +0,5 |
| Maximum | 4,0 |

---

## ➖ Déductions

| Déduction | Description |
|-----------|-------------|
| Avance sur salaire | Remboursement d'avance |
| Prêt | Remboursement de prêt employeur |
| Pension alimentaire | Retenue judiciaire |
| Saisie-arrêt | Saisie sur salaire |
| Mutuelle complémentaire | Cotisation mutuelle |
| Autres retenues | Toute autre déduction |

---

## 🛠️ Installation

### Prérequis

- **Dolibarr** 16.0 ou supérieur
- **PHP** 7.4+
- **MySQL** 5.7+ / **MariaDB** 10.3+

### Méthode 1 : Installation manuelle

1. **Cloner le dépôt** (ou télécharger le ZIP) :
   ```bash
   git clone https://github.com/Eudes8/dolibarr.git
   cd dolibarr
   ```

2. **Copier le module** dans le dossier `custom` de votre Dolibarr :
   ```bash
   cp -r htdocs/custom/payrollci /chemin/vers/dolibarr/htdocs/custom/
   ```

3. **Exécuter le script SQL** pour créer la table :
   ```bash
   mysql -u root -p votre_base < htdocs/custom/payrollci/sql/llx_payrollci_payslip.sql
   mysql -u root -p votre_base < htdocs/custom/payrollci/sql/llx_payrollci_payslip.key.sql
   ```

4. **Activer le module** dans Dolibarr :
   - Aller dans `Accueil → Configuration → Modules/Applications`
   - Chercher "PayrollCI" dans la catégorie "Ressources Humaines"
   - Cliquer sur le bouton pour activer

### Méthode 2 : Docker (recommandé pour tester)

```bash
docker run -d \
  --name dolibarr \
  -p 8080:80 \
  -e DOLI_DB_HOST=db \
  -e DOLI_DB_USER=dolibarr \
  -e DOLI_DB_PASSWORD=dolibarr \
  -e DOLI_DB_NAME=dolibarr \
  -e DOLI_ADMIN_LOGIN=admin \
  -e DOLI_ADMIN_PASSWORD=admin \
  -e DOLI_URL_ROOT=http://localhost:8080 \
  --link dolibarr-db:db \
  tuxgasy/dolibarr:latest
```

Puis copier le dossier `payrollci` dans le conteneur :
```bash
docker cp htdocs/custom/payrollci dolibarr:/var/www/html/custom/
```

---

## 📁 Structure du module

```
htdocs/custom/payrollci/
├── admin/
│   └── setup.php                          # Page de configuration
├── card.php                               # Formulaire création/édition bulletin
├── class/
│   ├── payrollci_calc.class.php           # 🧮 Moteur de calcul (toutes les constantes, taux, barèmes)
│   └── payslip.class.php                  # 💾 Classe CRUD (create/read/update/delete)
├── core/
│   └── modules/
│       ├── modPayrollCI.class.php         # 📦 Descripteur du module
│       └── payrollci/
│           └── doc/
│               └── pdf_bulletinpaie.modules.php  # 📄 Générateur PDF bulletin de paie
├── index.php                              # Redirection vers list.php
├── langs/
│   └── fr_FR/
│       └── payrollci.lang                 # 🌐 Traductions françaises
├── lib/
│   └── payrollci.lib.php                  # 🔧 Fonctions utilitaires
├── list.php                               # 📋 Liste des bulletins
└── sql/
    ├── llx_payrollci_payslip.sql          # 🗃️ Schéma de la table (~70 colonnes)
    └── llx_payrollci_payslip.key.sql      # 🔑 Index et clés
```

---

## 🧪 Exemple de test

**Scénario : Employé à Abidjan, marié, 2 enfants**

| Paramètre | Valeur |
|-----------|--------|
| Salaire catégoriel | 300 000 FCFA |
| Sursalaire | 50 000 FCFA |
| Ancienneté | 36 mois |
| Prime de rendement | 25 000 FCFA |
| Prime de technicité | 15 000 FCFA |
| Indemnité transport | 35 000 FCFA |
| Situation familiale | Marié(e) |
| Enfants à charge | 2 |
| Ville | Abidjan |
| Secteur | Commerce (AT 2%) |

**Résultat attendu :**
- Prime d'ancienneté auto-calculée : 2% + 1% = 3% × 300 000 = 9 000 FCFA
- Transport exonéré : 30 000 FCFA (Abidjan)
- Transport imposable : 35 000 − 30 000 = 5 000 FCFA
- Nombre de parts IGR : 3,0 (marié + 2 enfants × 0,5)
- Le module calcule automatiquement le brut, les cotisations CNPS, l'ITS et le net à payer

---

## ⚙️ Configuration

Accéder à **Accueil → Configuration → Modules → PayrollCI → Configuration** pour définir :

- **Ville par défaut** (pour le transport exonéré)
- **Secteur d'activité par défaut** (pour le taux AT)
- Consultation des barèmes de référence (CN, IGR, CNPS)

---

## 📜 Sources juridiques

| Texte | Contenu |
|-------|---------|
| Convention Collective Interprofessionnelle (CCI) | Primes, indemnités, heures supplémentaires, bulletin de paie (Art. 46.2) |
| Code du Travail de Côte d'Ivoire | Durée du travail, congés, SMIG |
| Code Général des Impôts (CGI) | ITS (IS, CN, IGR), charges fiscales patronales |
| Code de Prévoyance Sociale | CNPS : retraite, PF, AT |
| Loi CMU | Cotisation universelle maladie |
| Décrets FDFP | Taxe d'apprentissage, Formation professionnelle continue |

---

## 🚀 Évolutions futures

- [ ] Gestion multi-employés (paie en masse)
- [ ] Import/export des bulletins (CSV, Excel)
- [ ] Historique et comparaison mois par mois
- [ ] Déclarations CNPS automatiques
- [ ] Déclarations fiscales annuelles (DISA)
- [ ] Gestion des congés payés
- [ ] Interface de saisie des heures

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forker le projet
2. Créer une branche (`git checkout -b feature/amelioration`)
3. Committer vos modifications (`git commit -m 'Ajout de fonctionnalité'`)
4. Pousser la branche (`git push origin feature/amelioration`)
5. Ouvrir une Pull Request

---

## 📝 Licence

Ce projet est sous licence **GPL v3.0** — voir le fichier [LICENSE](LICENSE) pour plus de détails.

---

## 👨‍💻 Auteur

- **Eudes8** — [GitHub](https://github.com/Eudes8)

---

<p align="center">
  <em>Fait avec ❤️ pour la Côte d'Ivoire 🇨🇮</em>
</p>
