# <img src="https://rawgit.com/PresentKim/SVG-files/master/plugin-icons/startkit.svg" height="50" width="50"> StartKit
__A plugin for [PMMP](https://pmmp.io) :: Give start kit to player!__  
  
[![license](https://img.shields.io/github/license/organization/StartKit-PMMP.svg?label=License)](LICENSE)
[![release](https://img.shields.io/github/release/organization/StartKit-PMMP.svg?label=Release)](../../releases/latest)
[![download](https://img.shields.io/github/downloads/organization/StartKit-PMMP/total.svg?label=Download)](../../releases/latest)
[![Build status](https://ci.appveyor.com/api/projects/status/93r5ui5bx74jslbt/branch/master?svg=true)](https://ci.appveyor.com/project/PresentKim/startkit-pmmp/branch/master)
  
## What is this?   
Give start kit to player when player first join.  

  
  
## Features  
- [x] OP can modify start kit with chest the shown when execute command  
- [x] Store the whether kit supplied to each player's NBT data  
- [x] Save plugin data in NBT format  
- [x] Support configurable things  
- [x] Check that the plugin is not latest version  
  - [x] If not latest version, show latest release download url  
  
## Configurable things  
- [x] Configure the whether allows to open the box only once  
  - [x] in `config.yml` file  
- [x] Configure the language for messages  
  - [x] in `{SELECTED LANG}/lang.ini` file  
  - [x] Select language in `config.yml` file  
- [x] Configure the command (include subcommands)  
  - [x] in `config.yml` file  
- [x] Configure the permission of command  
  - [x] in `config.yml` file  
- [x] Configure the whether the update is check (default "false")
  - [x] in `config.yml` file  
  
  
## Command
Main command : `/startkit`  
  
  
## Permission
| permission   | default  | description  |
| :----------- | :------: | :----------- |
| startkit.cmd | OP       | main command |
