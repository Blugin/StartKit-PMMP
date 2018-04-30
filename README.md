<img src="./assets/icon/index.svg" height="256" width="256">  

[![License](https://img.shields.io/github/license/PMMPPlugin/StartKit.svg?label=License)](LICENSE)
[![Poggit](https://poggit.pmmp.io/ci.shield/PMMPPlugin/StartKit/StartKit)](https://poggit.pmmp.io/ci/PMMPPlugin/StartKit)
[![Release](https://img.shields.io/github/release/PMMPPlugin/StartKit.svg?label=Release)](https://github.com/PMMPPlugin/StartKit/releases/latest)
[![Download](https://img.shields.io/github/downloads/PMMPPlugin/StartKit/total.svg?label=Download)](https://github.com/PMMPPlugin/StartKit/releases/latest)


A plugin give start kit to player

## Command
Main command : `/startkit <open | reset | lang | reload | save>`

| subcommand | arguments           | description              |
| ---------- | ------------------- | ------------------------ |
| Open       |                     | Open start kit inventory |
| Reset      |                     | Reset the supply history |
| Lang       | \<language prefix\> | Load default lang file   |
| Reload     |                     | Reload all data          |
| Save       |                     | Save all data            |
  
<br/><br/>
  
## Required API
- PocketMine-MP : higher than [Build #745](https://jenkins.pmmp.io/job/PocketMine-MP/745)
  
<br/><br/>
  
## Permission
| permission          | default  | description       |
| ------------------- | -------- | ----------------- |
| startkit.cmd        | OP       | main command      |
|                     |          |                   |
| startkit.cmd.open   | OP       | open subcommand   |
| startkit.cmd.reset  | OP       | reset subcommand  |
| startkit.cmd.lang   | OP       | lang subcommand   |
| startkit.cmd.reload | OP       | reload subcommand |
| startkit.cmd.save   | OP       | save subcommand   |