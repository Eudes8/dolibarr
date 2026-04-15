# PayrollCI - Module de Paie Côte d'Ivoire pour Dolibarr

[![Version](https://img.shields.io/badge/version-4.0.0-blue.svg)](https://github.com/Eudes8/dolibarr)
[![Dolibarr](https://img.shields.io/badge/Dolibarr-16.0+-green.svg)](https://www.dolibarr.org)
[![Licence](https://img.shields.io/badge/licence-GPL--3.0-orange.svg)](LICENSE)
[![Pays](https://img.shields.io/badge/pays-C%C3%B4te%20d'Ivoire-brightgreen.svg)]()

## Description

**PayrollCI** est un module complet de gestion de la paie pour Dolibarr, conforme à la législation ivoirienne en vigueur (Ordonnance n° 2023-719 du 13/09/2023). Il prend en charge le calcul automatique de toutes les cotisations sociales et fiscales, la génération de bulletins de paie professionnels au format PDF (style Sage Paie), ainsi que les états réglementaires obligatoires.

## Fonctionnalités principales

### Calculs conformes à la loi ivoirienne

- **IBS (Impôt sur les Bénéfices et Salaires)** — Barème progressif à 6 tranches :
  | Tranche mensuelle | Taux |
  |---|---|
  | 0 – 75 000 FCFA | 0% |
  | 75 001 – 240 000 FCFA | 16% |
  | 240 001 – 800 000 FCFA | 21% |
  | 800 001 – 2 400 000 FCFA | 24% |
  | 2 400 001 – 8 000 000 FCFA | 28% |
  | > 8 000 000 FCFA | 32% |

- **RICF (Réduction d'Impôt pour Charges de Famille)** — 11 000 × (N − 1) / mois, max 5 parts
- **CNPS** — Retraite 6,3% (salarié) + 7,7% (employeur), plafond 2 700 000 FCFA/mois
- **Prestations Familiales** — 5,75% employeur
- **Accidents du Travail** — 2% à 5% employeur selon secteur
- **Contribution Employeur** — 2,8% local / 12% expatrié
- **FDFP** — TA 0,6% + FPC 1,2% employeur

### 13 Secteurs d'activité

Agriculture, BTP, Industrie, Commerce, Transport, Hôtellerie, Banque/Assurance, Télécoms, Mines, Pétrole, Santé, Éducation, Services.

### Calcul automatique de l'ancienneté

- Saisie de la **date d'embauche** (pas de saisie manuelle de l'ancienneté)
- Calcul automatique du nombre d'années
- Prime d'ancienneté selon la Convention Collective :
  - 2% après 2 ans, +1% par an jusqu'à 25% maximum

### Bulletin de paie PDF professionnel (style Sage Paie)

- En-tête avec bandeau bleu "BULLETIN DE PAIE"
- Lignes numérotées (10 à 37) identiques au format Sage
- 10 colonnes : N° | Désignation | Nombre | Base | Taux sal. | Gain | Retenue sal. | Taux pat. | Cotis. pat.(+) | Cotis. pat.(-)
- Sections : TOTAL BRUT IMPOSABLE / TOTAL COTISATIONS SALARIALES
- Tableau CUMULS (période + année)
- Zones VISA EMPLOYEUR / VISA EMPLOYÉ
- NET A PAYER en surbrillance

### États et rapports réglementaires

| Rapport | Description |
|---|---|
| **Livre de Paie** | Registre mensuel de tous les bulletins avec totaux |
| **État 301 (CDIR)** | Déclaration annuelle des salaires — échéance 30 mai / 30 juin |
| **Journal de Paie** | Écritures comptables de la paie pour intégration |

### Interface Dolibarr native

- Icônes professionnelles `img_picto()` (pas d'emojis)
- Onglets : Bulletin | Notes | Documents | Agenda | Liens
- Tableau de bord avec statistiques et graphiques
- Liste avec filtres, tri et pagination
- Administration : Configuration, Champs extra, À propos

## Structure du module

```
htdocs/custom/payrollci/
├── admin/
│   ├── about.php              # Page À propos (v4.0.0)
│   ├── extrafields.php        # Champs personnalisés
│   └── setup.php              # Configuration (barème IBS, secteurs)
├── class/
│   ├── payrollci_calc.class.php   # Moteur de calcul (IBS/RICF/CNPS/FDFP)
│   └── payslip.class.php         # Classe métier bulletin de paie
├── core/modules/
│   ├── modPayrollCI.class.php     # Descripteur du module
│   └── payrollci/doc/
│       └── pdf_bulletinpaie.modules.php  # Générateur PDF (style Sage)
├── langs/fr_FR/
│   └── payrollci.lang             # Traductions françaises (~90 clés)
├── lib/
│   └── payrollci.lib.php          # Bibliothèque partagée
├── sql/
│   ├── llx_payrollci_payslip.sql              # Table principale
│   ├── llx_payrollci_payslip.key.sql          # Index et clés
│   └── llx_payrollci_payslip_extrafields.sql  # Table champs extra
├── agenda.php                 # Onglet Agenda
├── card.php                   # Fiche bulletin (création/édition)
├── dashboard.php              # Tableau de bord
├── document.php               # Onglet Documents
├── index.php                  # Page d'accueil du module
├── linked.php                 # Onglet Éléments liés
├── list.php                   # Liste des bulletins
├── note.php                   # Onglet Notes
├── report_etat301.php         # Rapport État 301
├── report_journal.php         # Rapport Journal de paie
├── report_livrepaie.php       # Rapport Livre de paie
└── tab_user.php               # Onglet utilisateur
```

## Installation

1. Copier le dossier `payrollci/` dans `htdocs/custom/`
2. Aller dans **Accueil > Configuration > Modules**
3. Rechercher "PayrollCI" et activer le module
4. Configurer dans **Configuration > Modules > PayrollCI > Configuration**

## Prérequis

- Dolibarr 16.0 ou supérieur
- PHP 7.4 ou supérieur
- MySQL 5.7+ / MariaDB 10.3+

## Configuration

### Paramètres principaux
- **Secteur d'activité** — Détermine le taux AT (accidents du travail)
- **Barème IBS** — Affiché en lecture seule (conforme à l'ordonnance 2023)
- **Numérotation** — Format automatique des bulletins

## Changelog

### v4.0.0 (2026-04-15)
- **NOUVEAU** : Barème IBS 6 tranches (Ordonnance n° 2023-719)
- **NOUVEAU** : RICF remplace l'ancien système IS/CN/IGR
- **NOUVEAU** : Contribution employeur 2,8% local / 12% expatrié
- **NOUVEAU** : Calcul automatique ancienneté via date d'embauche
- **NOUVEAU** : PDF bulletin style Sage Paie (lignes numérotées 10-37)
- **NOUVEAU** : Rapport Livre de Paie
- **NOUVEAU** : Rapport État 301 (CDIR)
- **NOUVEAU** : Rapport Journal de Paie
- **CORRECTION** : Icônes professionnelles img_picto() (fini les emojis)
- **CORRECTION** : Intégration complète avec le cœur Dolibarr

### v3.0.0
- Intégration complète dans Dolibarr (21 fichiers)
- Onglets natifs, tableau de bord, génération PDF

### v2.0.0
- Refonte complète : CCI, primes, 13 secteurs d'activité

### v1.0.0
- Version initiale avec calculs de base

## Licence

Ce module est distribué sous licence [GPL-3.0](https://www.gnu.org/licenses/gpl-3.0.html).

## Auteur

Développé par **Eudes8** — Côte d'Ivoire

---

*Module conforme à la législation fiscale et sociale ivoirienne en vigueur au 15 avril 2026.*
