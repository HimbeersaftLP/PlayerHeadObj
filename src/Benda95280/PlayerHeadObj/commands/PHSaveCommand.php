<?php

/*
 *  Original Source: https://github.com/Enes5519/PlayerHead
 *  PlayerHeadObj - a Altay and PocketMine-MP plugin to add player head on server
 *  Copyright (C) 2018 Enes Yıldırım
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Benda95280\PlayerHeadObj\commands;

use Benda95280\PlayerHeadObj\PlayerHeadObj;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use pocketmine\Player;
use pocketmine\scheduler\BulkCurlTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Internet;
use pocketmine\utils\InternetException;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Utils;


class PHSaveCommand extends Command{
	/** @var PlayerHeadObj */
	private $plugin;

	public function __construct(PlayerHeadObj $plugin){
		$this->plugin = $plugin;
		parent::__construct('PlayerHeadObjSave', 'Add a player head to PlayerHeadObj', '/PlayerHeadObjSave <server|mojang> <name:string>', ['phosave']);
		$this->setPermission('playerheadObj.save');
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(!$this->testPermission($sender)){
			return true;
		}

		if(count($args) < 2){
			throw new InvalidCommandSyntaxException();
		}

		$source = $args[0];
		$name = $args[1];

		if ($source === "server") {
			if (!$sender->hasPermission("playerheadObj.save.fromserver")) {
				$sender->sendMessage("You aren't allowed to save skins from players on this server!");
			}
			$player = $this->plugin->getServer()->getPlayer($name);
			if (!$player) {
				$sender->sendMessage("Player not found!");
				return true;
			}
			$skinData = $player->getSkin()->getSkinData();
			try {
				$fileName = PlayerHeadObj::skinByteArrToPng($skinData, $player->getLowerCaseName());
				$sender->sendMessage("Saved as {$fileName}");
			} catch (\Exception $e) {
				$sender->sendMessage("Error while saving image file");
			}
		} else if ($source === "mojang") {
			if (!$sender->hasPermission("playerheadObj.save.frommojang")) {
				$sender->sendMessage("You aren't allowed to download skins from Minecraft: Java Edition!");
			}
			$task = new class([[
				"page" => "https://api.mojang.com/users/profiles/minecraft/{$name}"
			]], $sender) extends BulkCurlTask {
				public function __construct(array $operations, $complexData = null) {
					parent::__construct($operations, $complexData);
				}

				public function onCompletion(Server $server) {
					/** @var CommandSender $sender */
					$sender = $this->fetchLocal();
					if ($this->getResult()[0] && !$this->getResult()[0] instanceof InternetException) {
						$response = $this->getResult()[0];
						$sender->sendMessage(print_r($response, true));
					}
				}
			};
			$this->plugin->getServer()->getAsyncPool()->submitTask($task);
		} else {
			throw new InvalidCommandSyntaxException();
		}
		return true;
	}
}

