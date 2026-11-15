# Dice & Dragons — XenForo 2.3+ Add-on Suite
*Diese Übersicht ist auch verfügbar auf: [English](https://github.com/Dice-Dragons)*

Herzlich willkommen im GitHub-Organisationsprofil von **Dice & Dragons**!

Hier werden die offiziellen XenForo 2.3+ Add-ons von [Hoffi](https://forum.dice-dragons.de/mitglieder/hoffi.1/) verwaltet, gepflegt und über CI/CD-Pipelines bereitgestellt.

---

## 🎲 [DD] RPG Suite — Ökosystem & Module

Die **[DD] RPG Suite (`cv6/RPG`)** bildet die zentrale Plattform für Tabletop- & Pen-&-Paper-Rollenspiele in XenForo 2.3 (Genres, Verlage, Kampagnen-Content-Bridge und RPG-Dashboard) und verbindet alle spezialisierten Rollenspiel-Module der Suite:

| Bereich | Add-on | ID | Edition | Version | Funktion innerhalb der RPG Suite |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Basis & Infrastruktur** | **[DD] - Core Library** | `cv6/Core` | Kostenlos | `1.6.4` | Gemeinsame UI-Komponenten, Icon-Picker, Vergleichsoperatoren & Basis-Helper |
| **Basis & Infrastruktur** | **[DD] RPG Suite** | `cv6/RPG` | RPG Suite (Kostenlos) | `1.0.0 Beta 3` | Zentrale Genres, Verlage (Publishers), Kampagnen-Content-Bridge & RPG-Dashboard |
| **Basis & Infrastruktur** | **[DD] Custom Fields Extension** | `cv6/CustomFields` | Kostenlos | `1.9.0 Release Candidate 2` | Erweiterte Zusatzfelder, Formel-Berechnungen, Feld-Abhängigkeiten & EntityChooser |
| **Kern-Modul** | **[DD] Character Database** | `cv6/CharEdit` | **RPG Suite Plus** | `1.2.0 Release Candidate 6` | Charakterbögen, Settings, Archetypen, Fähigkeiten, Zauber & Kampagnen |
| **Kern-Modul** | **[DD] Dice Roller** | `cv6/DiceRoller` | RPG Suite (Kostenlos) | `2.8.1` | Würfel-Engine, Regel-Pipelines, Wiresets, visuelle Würfel-Sets & Charakterbogen-Würfeltrigger |
| **Kern-Modul** | **[DD] BattleMap Generator** | `cv6/BattleMaps` | **RPG Suite Plus** | `1.0.0 Beta 1` | Prozeduraler Dungeon- & Gebäude-Generator, interaktiver Viewer & Karten-Galerie |
| **Kern-Modul** | **[DD] Handout Generator** | `cv6/Handout` | RPG Suite (Kostenlos) | `2.0.0 Beta 1` | In-Game-Briefe, Pergamente & geteilte Kampagnen-Handouts |
| **Empfohlene Erweiterung** | **[DD] Font Manager** | `cv6/FontManager` | Kostenlos | `1.0.0 Release Candidate 2` | Web- & RPG-Schriftarten für Handout-Layouts, Charakterbögen & Würfel-Sets |
| **Empfohlene Erweiterung** | **[DD] Timeline** | `cv6/Timeline` | Kostenlos | `1.0.0 Release Candidate 3` | Interaktive Zeitleisten & Chroniken für RPG-Kampagnen und Spielwelten |
| **Empfohlene Erweiterung** | **[DD] Prefix Tools** | `cv6/Prefix` | Kostenlos | `1.3.8` | Gestylte Badges & Präfix-Verwaltung für RPG-Setting-Editionen |
| **Empfohlene Erweiterung** | **[DD] Icon Interface: Game-Icons.net** | `cv6/IconsGame` | Kostenlos | `1.0.0 Beta 2` | Über 4.000 Tabletop-RPG-Icons von Game-Icons.net für Genres, Fertigkeiten & Würfel |


---

## 📊 Live Suite-Dashboard

Übersicht aller 25 aktiven Add-ons der Suite:

| Add-on | Edition | Version | Abhängigkeiten | Support |
| :--- | :--- | :--- | :--- | :--- |
| **[DD] - Core Library** | Kostenlos | `1.6.4` | `-` | [Forum](https://forum.dice-dragons.de/forum/core/) |
| **[DD] RPG Suite** | RPG Suite (Kostenlos) | `1.0.0 Beta 3` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/rpg-suite/) |
| **[DD] Admin Tools** | Kostenlos | `2.5.1` | `^1.5.1` | [Forum](https://forum.dice-dragons.de/forum/admin-tools/) |
| **[DD] Avatar Style Chooser** | **Premium** | `1.0.0 Beta 1` | `^1.3.1` | [Forum](https://forum.dice-dragons.de/forum/avatar-styles/) |
| **[DD] BattleMap Generator** | **RPG Suite Plus** | `1.0.0 Beta 1` | `^1.6.3 + RPG` | [Forum](https://forum.dice-dragons.de/forum/battle-map-generator/) |
| **[DD] Bump** | Kostenlos | `1.0.0` | `-` | [Forum](https://forum.dice-dragons.de/forum/bump/) |
| **[DD] Character Database** | **RPG Suite Plus** | `1.2.0 Release Candidate 6` | `^1.6.3 + CustomFields, RPG` | [Forum](https://forum.dice-dragons.de/forum/character/) |
| **[DD] Custom Fields Extension** | Kostenlos | `1.9.0 Release Candidate 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/custom-fields/) |
| **[DD] Dice Roller** | RPG Suite (Kostenlos) | `2.8.1` | `^1.6.1` | [Forum](https://forum.dice-dragons.de/forum/dice-roller/) |
| **[DD] Discord Hook Enhancements** | Kostenlos | `1.0.0` | `-` | - |
| **[DD] Font Manager** | Kostenlos | `1.0.0 Release Candidate 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/font-manager/) |
| **[DD] Game Icons** | Kostenlos | `1.0.0 Beta 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/icon-interfaces/) |
| **[DD] Google Material Icons Interface** | Kostenlos | `1.0.0 Beta 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/icon-interfaces/) |
| **[DD] Handout Generator** | RPG Suite (Kostenlos) | `2.0.0 Beta 1` | `^1.6.3 + RPG, FontManager` | [Forum](https://forum.dice-dragons.de/forum/handout/) |
| **[DD] Icon for new Posts** | Kostenlos | `1.1.2` | `^1.4.2` | [Forum](https://forum.dice-dragons.de/forum/new-post-icon/) |
| **[DD] Index for Forums** | Kostenlos | `1.3.0` | `^1.5.0` | [Forum](https://forum.dice-dragons.de/forum/abc-index/) |
| **[DD] Launchpad** | Kostenlos | `1.0.0 Release Candidate 1` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/) |
| **[DD] License** | Kostenlos | `1.1.1` | `^1.6.2` | [Forum](https://forum.dice-dragons.de/forum/license-checker/) |
| **[DD] MCP Server** | Kostenlos | `1.0.0 Beta 1` | `-` | [Forum](https://forum.dice-dragons.de/forum/mcp-server/) |
| **[DD] Node Icons & Tools** | Kostenlos | `1.5.12` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/node-icon/) |
| **[DD] Notebook** | Kostenlos | `1.1.0 Release Candidate 1` | `^1.4.2` | - |
| **[DD] Notice Countdown** | Kostenlos | `1.0.4` | `^1.4.2` | [Forum](https://forum.dice-dragons.de/forum/notice-countdown/) |
| **[DD] Prefix Tools** | Kostenlos | `1.3.8` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/prefix-tools/) |
| **[DD] Resource Gallery** | Kostenlos | `1.1.0 Release Candidate 2` | `^1.6.3 + CustomFields, XFRM` | [Forum](https://forum.dice-dragons.de/forum/resource-gallery/) |
| **[DD] Timeline BB-Code** | Kostenlos | `1.0.0 Release Candidate 3` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/) |


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