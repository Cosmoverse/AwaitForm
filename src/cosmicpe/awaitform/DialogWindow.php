<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

use Generator;
use pocketmine\player\Player;
use SOFe\AwaitGenerator\Await;

final class DialogWindow{

	public function __construct(
		readonly private PlayerManager $manager,
		public string $title,
		public string $content,
		public string $button1,
		public string $button2
	){}

	/**
	 * @param Player $player
	 * @return Generator<mixed, Await::RESOLVE|Await::REJECT, mixed, bool>
	 */
	public function request(Player $player) : Generator{
		return yield from $this->manager->sendPlayerForm($player, [
			"type" => "modal",
			"title" => $this->title,
			"content" => $this->content,
			"button1" => $this->button1,
			"button2" => $this->button2
		], ResponseProcessor::dialog());
	}

	/**
	 * @template TFallback
	 * @param Player $player
	 * @param TFallback $fallback
	 * @return Generator<mixed, Await::RESOLVE, mixed, bool|TFallback>
	 */
	public function requestOrFallback(Player $player, mixed $fallback) : Generator{
		try{
			return yield from $this->request($player);
		}catch(AwaitFormException){
			return $fallback;
		}
	}
}