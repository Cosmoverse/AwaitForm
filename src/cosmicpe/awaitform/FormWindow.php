<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

use Generator;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use SOFe\AwaitGenerator\Await;
use function array_combine;

/**
 * @template TIndex
 */
final class FormWindow{

	public static function pathIcon(string $path) : array{
		return ["type" => "path", "data" => $path];
	}

	public static function urlIcon(string $url) : array{
		return ["type" => "url", "data" => $url];
	}

	/**
	 * @param PlayerManager $manager
	 * @param string $title
	 * @param array<TIndex, FormControl> $content
	 * @param array{type: "url"|"path", data: string}|null $icon
	 * @param string|null $submit_button_text
	 */
	public function __construct(
		readonly private PlayerManager $manager,
		public string $title,
		public array $content,
		public ?array $icon = null,
		public ?string $submit_button_text = null
	){}

	/**
	 * @param Player $player
	 * @return Generator<mixed, Await::RESOLVE|Await::REJECT, mixed, array<TIndex, mixed>>
	 */
	public function request(Player $player) : Generator{
		$attributes = [[], [], []];
		foreach($this->content as $index => $value){
			$attributes[0][] = $index;
			$attributes[1][] = $value->data;
			$attributes[2][] = $value->tags;
		}
		$response = yield from $this->manager->sendPlayerForm($player, [
			"type" => "custom_form",
			"title" => TextFormat::RESET . $this->title,
			"icon" => $this->icon,
			"content" => $attributes[1],
			"submit" => $this->submit_button_text
		], ResponseProcessor::form(), $attributes[2]);
		return array_combine($attributes[0], $response);
	}
}