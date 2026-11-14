# Dice & Dragons — XenForo 2.3+ Add-on Suite
*Diese Übersicht ist auch verfügbar auf: [English](https://github.com/Dice-Dragons)*

Herzlich willkommen im GitHub-Organisationsprofil von **Dice & Dragons**!

Hier werden die offiziellen XenForo 2.3+ Add-ons von [Hoffi](https://forum.dice-dragons.de/mitglieder/hoffi.1/) verwaltet, gepflegt und über CI/CD-Pipelines bereitgestellt.

---

## 🎲 [DD] RPG Suite — Ökosystem & Module

Die **[DD] RPG Suite (`cv6/RPG`)** bildet die zentrale Plattform für Tabletop- & Pen-&-Paper-Rollenspiele in XenForo 2.3 (Genres, Verlage, Kampagnen-Content-Bridge und RPG-Dashboard) und verbindet alle spezialisierten Rollenspiel-Module der Suite:

| Bereich | Add-on | ID | Version | Funktion innerhalb der RPG Suite |
| :--- | :--- | :--- | :--- | :--- |
| **Basis & Infrastruktur** | **[DD] - Core Library** | `cv6/Core` | `1.6.3` | Gemeinsame UI-Komponenten, Icon-Picker, Vergleichsoperatoren & Basis-Helper |
| **Basis & Infrastruktur** | **[DD] RPG Suite** | `cv6/RPG` | `1.0.0 Beta 1` | Zentrale Genres, Verlage (Publishers), Kampagnen-Content-Bridge & RPG-Dashboard |
| **Basis & Infrastruktur** | **[DD] Custom Fields Extension** | `cv6/CustomFields` | `1.9.0 Release Candidate 1` | Erweiterte Zusatzfelder, Formel-Berechnungen, Feld-Abhängigkeiten & EntityChooser |
| **Kern-Modul** | **[DD] Character Database** | `cv6/CharEdit` | `1.2.0 Release Candidate 5` | Charakterbögen, Settings, Archetypen, Fähigkeiten, Zauber & Kampagnen |
| **Kern-Modul** | **[DD] Dice Roller** | `cv6/DiceRoller` | `3.0.0 Beta 1` | Würfel-Engine, Regel-Pipelines, Wiresets, visuelle Würfel-Sets & Charakterbogen-Würfeltrigger |
| **Kern-Modul** | **[DD] BattleMap Generator** | `cv6/BattleMaps` | `1.0.0 Beta 1` | Prozeduraler Dungeon- & Gebäude-Generator, interaktiver Viewer & Karten-Galerie |
| **Kern-Modul** | **[DD] Handout Generator** | `cv6/Handout` | `2.0.0 Beta 1` | In-Game-Briefe, Pergamente & geteilte Kampagnen-Handouts |
| **Empfohlene Erweiterung** | **[DD] Font Manager** | `cv6/FontManager` | `1.0.0 Release Candidate 2` | Web- & RPG-Schriftarten für Handout-Layouts, Charakterbögen & Würfel-Sets |
| **Empfohlene Erweiterung** | **[DD] Timeline** | `cv6/Timeline` | `1.0.1` | Interaktive Zeitleisten & Chroniken für RPG-Kampagnen und Spielwelten |
| **Empfohlene Erweiterung** | **[DD] Prefix Tools** | `cv6/Prefix` | `1.3.8` | Gestylte Badges & Präfix-Verwaltung für RPG-Setting-Editionen |
| **Empfohlene Erweiterung** | **[DD] Icon Interface: Game-Icons.net** | `cv6/IconsGame` | `1.0.0 Beta 2` | Über 4.000 Tabletop-RPG-Icons von Game-Icons.net für Genres, Fertigkeiten & Würfel |


---

## 📊 Live Suite-Dashboard

Übersicht aller 22 aktiven Add-ons der Suite:

| Add-on | Version | Abhängigkeiten | Support |
| :--- | :--- | :--- | :--- |
| **[DD] - Core Library** | `1.6.3` | `-` | [Forum](https://forum.dice-dragons.de/forum/core/) |
| **[DD] RPG Suite** | `1.0.0 Beta 1` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/rpg-suite/) |
| **[DD] Admin Tools** | `2.5.1` | `^1.5.1` | [Forum](https://forum.dice-dragons.de/forum/admin-tools/) |
| **[DD] Avatar Style Chooser** | `1.0.0 Beta 1` | `^1.3.1` | [Forum](https://forum.dice-dragons.de/forum/avatar-styles/) |
| **[DD] Bump** | `1.0.0` | `-` | [Forum](https://forum.dice-dragons.de/forum/bump/) |
| **[DD] Character Database** | `1.2.0 Release Candidate 5` | `^1.6.3 + CustomFields, RPG` | [Forum](https://forum.dice-dragons.de/forum/character/) |
| **[DD] Custom Fields Extension** | `1.9.0 Release Candidate 1` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/custom-fields/) |
| **[DD] Dice Roller** | `3.0.0 Beta 1` | `^1.6.3 + RPG` | [Forum](https://forum.dice-dragons.de/forum/dice-roller/) |
| **[DD] Discord Hook Enhancements** | `1.0.0` | `-` | - |
| **[DD] Font Manager** | `1.0.0 Release Candidate 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/font-manager/) |
| **[DD] Icon Interface: Game-Icons.net** | `1.0.0 Beta 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/icon-interfaces/) |
| **[DD] Icon Interface: Google Material** | `1.0.0 Beta 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/icon-interfaces/) |
| **[DD] Icon for new Posts** | `1.1.2` | `^1.4.2` | [Forum](https://forum.dice-dragons.de/forum/new-post-icon/) |
| **[DD] Index for Forums** | `1.3.0` | `^1.5.0` | [Forum](https://forum.dice-dragons.de/forum/abc-index/) |
| **[DD] Launchpad** | `1.0.0 Release Candidate 1` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/) |
| **[DD] License** | `1.1.1` | `^1.6.2` | [Forum](https://forum.dice-dragons.de/forum/license-checker/) |
| **[DD] Node Icons & Tools** | `1.5.12` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/node-icon/) |
| **[DD] Notebook** | `1.1.0 Release Candidate 1` | `^1.4.2` | - |
| **[DD] Notice Countdown** | `1.0.4` | `^1.4.2` | [Forum](https://forum.dice-dragons.de/forum/notice-countdown/) |
| **[DD] Prefix Tools** | `1.3.8` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/prefix-tools/) |
| **[DD] Resource Gallery** | `1.1.0 Release Candidate 2` | `^1.6.3 + CustomFields, XFRM` | [Forum](https://forum.dice-dragons.de/forum/resource-gallery/) |
| **[DD] Timeline** | `1.0.1` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/) |


---

## ⚙️ Technische Richtlinien

* **Framework**: XenForo 2.3.0+
* **PHP Baseline**: PHP 8.2.0+ (optimiert für PHP 8.4)
* **Frontend**: Vanilla JS (ES2020), LESS, FontAwesome 5.x Baseline
* **Lizenz**: Proprietary (Nutzung für Inhaber einer gültigen XenForo-Lizenz)

---

## 💬 Community & Support

* 🌐 **Hauptforum**: [https://forum.dice-dragons.de](https://forum.dice-dragons.de)
* 📥 **Downloads & Ressourcen**: [https://forum.dice-dragons.de/downloads/](https://forum.dice-dragons.de/downloads/)
* 👤 **Entwickler-Profil**: [Hoffi auf Dice & Dragons](https://forum.dice-dragons.de/mitglieder/hoffi.1/) | [Hoffi auf XenForo.com](https://xenforo.com/community/members/hoffi.214/)