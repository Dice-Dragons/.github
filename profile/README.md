# Dice & Dragons — XenForo 2.3+ Add-on Suite
*This overview is also available in: [Deutsch](https://github.com/Dice-Dragons/.github/blob/main/profile/README.de.md)*

Welcome to the official GitHub organization profile of **Dice & Dragons**!

This organization hosts and maintains the official XenForo 2.3+ add-on suite developed by [Hoffi](https://forum.dice-dragons.de/mitglieder/hoffi.1/), built and deployed via automated CI/CD pipelines.

---

## 🎲 [DD] RPG Suite — Ecosystem & Modules

**[DD] RPG Suite (`cv6/RPG`)** is the central domain platform for tabletop & pen-and-paper roleplaying games in XenForo 2.3 (genres, publishers, campaign content bridge, and RPG dashboard), connecting all specialized RPG modules across the suite:

| Layer | Add-on | ID | Edition | Version | Role in the RPG Suite |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Foundation** | **[DD] - Core Library** | `cv6/Core` | Free | `1.6.3` | Shared UI components, icon chooser, operators, and base helpers |
| **Foundation** | **[DD] RPG Suite** | `cv6/RPG` | RPG Suite (Free) | `1.0.0 Beta 1` | Central genres, publishers, campaign content bridge, and RPG dashboard |
| **Foundation** | **[DD] Custom Fields Extension** | `cv6/CustomFields` | Free | `1.9.0 Release Candidate 1` | Extended custom field types, calculations, dependencies, and EntityChooser |
| **Core Module** | **[DD] Character Database** | `cv6/CharEdit` | **RPG Suite Plus** | `1.2.0 Release Candidate 5` | Character sheets, settings, archetypes, abilities, spells, and campaigns |
| **Core Module** | **[DD] Dice Roller** | `cv6/DiceRoller` | RPG Suite (Free) | `3.0.0 Beta 1` | Dice rolling engine, rule pipelines, wiresets, visual dice sets, and character sheet roll triggers |
| **Core Module** | **[DD] BattleMap Generator** | `cv6/BattleMaps` | **RPG Suite Plus** | `1.0.0 Beta 1` | Procedural dungeon & building generator, interactive viewer, and map gallery |
| **Core Module** | **[DD] Handout Generator** | `cv6/Handout` | RPG Suite (Free) | `2.0.0 Beta 1` | In-game letters, parchments, and shared campaign handouts |
| **Recommended Add-on** | **[DD] Font Manager** | `cv6/FontManager` | Free | `1.0.0 Release Candidate 2` | Custom web & RPG fonts for handout layouts, character sheets, and dice sets |
| **Recommended Add-on** | **[DD] Timeline** | `cv6/Timeline` | Free | `1.0.1` | Interactive timelines and chronicles for RPG campaigns and worlds |
| **Recommended Add-on** | **[DD] Prefix Tools** | `cv6/Prefix` | Free | `1.3.8` | Styled badges and prefix management for RPG setting editions |
| **Recommended Add-on** | **[DD] Icon Interface: Game-Icons.net** | `cv6/IconsGame` | Free | `1.0.0 Beta 2` | Over 4,000 tabletop RPG icons from Game-Icons.net for genres, skills, and dice |


---

## 📊 Live Suite Dashboard

Overview of all 24 active add-ons in the suite:

| Add-on | Edition | Version | Dependencies | Support |
| :--- | :--- | :--- | :--- | :--- |
| **[DD] - Core Library** | Free | `1.6.3` | `-` | [Forum](https://forum.dice-dragons.de/forum/core/) |
| **[DD] RPG Suite** | RPG Suite (Free) | `1.0.0 Beta 1` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/rpg-suite/) |
| **[DD] Admin Tools** | Free | `2.5.1` | `^1.5.1` | [Forum](https://forum.dice-dragons.de/forum/admin-tools/) |
| **[DD] Avatar Style Chooser** | **Premium** | `1.0.0 Beta 1` | `^1.3.1` | [Forum](https://forum.dice-dragons.de/forum/avatar-styles/) |
| **[DD] BattleMap Generator** | **RPG Suite Plus** | `1.0.0 Beta 1` | `^1.6.3 + RPG` | [Forum](https://forum.dice-dragons.de/forum/battle-map-generator/) |
| **[DD] Bump** | Free | `1.0.0` | `-` | [Forum](https://forum.dice-dragons.de/forum/bump/) |
| **[DD] Character Database** | **RPG Suite Plus** | `1.2.0 Release Candidate 5` | `^1.6.3 + CustomFields, RPG` | [Forum](https://forum.dice-dragons.de/forum/character/) |
| **[DD] Custom Fields Extension** | Free | `1.9.0 Release Candidate 1` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/custom-fields/) |
| **[DD] Dice Roller** | RPG Suite (Free) | `3.0.0 Beta 1` | `^1.6.3 + RPG` | [Forum](https://forum.dice-dragons.de/forum/dice-roller/) |
| **[DD] Discord Hook Enhancements** | Free | `1.0.0` | `-` | - |
| **[DD] Font Manager** | Free | `1.0.0 Release Candidate 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/font-manager/) |
| **[DD] Handout Generator** | RPG Suite (Free) | `2.0.0 Beta 1` | `^1.6.3 + RPG, FontManager` | [Forum](https://forum.dice-dragons.de/forum/handout/) |
| **[DD] Icon Interface: Game-Icons.net** | Free | `1.0.0 Beta 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/icon-interfaces/) |
| **[DD] Icon Interface: Google Material** | Free | `1.0.0 Beta 2` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/icon-interfaces/) |
| **[DD] Icon for new Posts** | Free | `1.1.2` | `^1.4.2` | [Forum](https://forum.dice-dragons.de/forum/new-post-icon/) |
| **[DD] Index for Forums** | Free | `1.3.0` | `^1.5.0` | [Forum](https://forum.dice-dragons.de/forum/abc-index/) |
| **[DD] Launchpad** | Free | `1.0.0 Release Candidate 1` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/) |
| **[DD] License** | Free | `1.1.1` | `^1.6.2` | [Forum](https://forum.dice-dragons.de/forum/license-checker/) |
| **[DD] Node Icons & Tools** | Free | `1.5.12` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/node-icon/) |
| **[DD] Notebook** | Free | `1.1.0 Release Candidate 1` | `^1.4.2` | - |
| **[DD] Notice Countdown** | Free | `1.0.4` | `^1.4.2` | [Forum](https://forum.dice-dragons.de/forum/notice-countdown/) |
| **[DD] Prefix Tools** | Free | `1.3.8` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/prefix-tools/) |
| **[DD] Resource Gallery** | Free | `1.1.0 Release Candidate 2` | `^1.6.3 + CustomFields, XFRM` | [Forum](https://forum.dice-dragons.de/forum/resource-gallery/) |
| **[DD] Timeline** | Free | `1.0.1` | `^1.6.3` | [Forum](https://forum.dice-dragons.de/forum/) |


---

## ⚙️ Technical Standards

* **Framework**: XenForo 2.3.0+
* **PHP Baseline**: PHP 8.2.0+ (optimized for PHP 8.4)
* **Frontend**: Vanilla JS (ES2020), LESS, FontAwesome 5.x Baseline
* **License**: Proprietary (for holders of a valid XenForo license)

---

## 💬 Community & Support

* 🌐 **Main Forum**: [https://forum.dice-dragons.de](https://forum.dice-dragons.de)
* 📥 **Downloads & Resources**: [https://forum.dice-dragons.de/downloads/](https://forum.dice-dragons.de/downloads/)
* 👤 **Developer Profile**: [Hoffi on Dice & Dragons](https://forum.dice-dragons.de/mitglieder/hoffi.1/) | [Hoffi on XenForo.com](https://xenforo.com/community/members/hoffi.214/)