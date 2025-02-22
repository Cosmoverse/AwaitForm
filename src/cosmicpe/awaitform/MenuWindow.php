<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

use Generator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;
use function array_map;

/**
 * @template TResult
 */
final class MenuWindow{

	/**
	 * @param PlayerManager $manager
	 * @param string $title
	 * @param string $content
	 * @param list<array{Button, TResult}> $buttons
	 */
	public function __construct(
		readonly private PlayerManager $manager,
		public string $title,
		public string $content,
		public array $buttons
	){}

	/**
	 * @param Player $player
	 * @return Generator<mixed, Await::RESOLVE|Await::REJECT, mixed, TResult>
	 */
	public function request(Player $player) : Generator{
		$response = yield from $this->manager->sendPlayerForm($player, [
			"type" => "form",
			"title" => TextFormat::RESET . $this->title,
			"content" => $this->content,
			"buttons" => array_map(static fn($x) => $x[0]->data, $this->buttons)
		], ResponseProcessor::menu());
		return $this->buttons[$response][1];
	}

	/**
	 * @template TFallback
	 * @param Player $player
	 * @param TFallback $fallback
	 * @return Generator<mixed, Await::RESOLVE, mixed, int|TFallback>
	 */
	public function requestOrFallback(Player $player, mixed $fallback) : Generator{
		try{
			return yield from $this->request($player);
		}catch(AwaitFormException){
			return $fallback;
		}
	}
}