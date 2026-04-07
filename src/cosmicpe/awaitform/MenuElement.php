<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

final class MenuElement{

	public const BUTTON_IMAGE_TYPE_PATH = "path";
	public const BUTTON_IMAGE_TYPE_URL = "url";

	/**
	 * Creates a button with text and no image.
	 *
	 * @param string $text
	 * @return self
	 */
	public static function button(string $text) : self{
		return new self(["type" => "button", "text" => $text, "image" => null], ["interactable" => true]);
	}

	/**
	 * Creates a button with text and an image path (game resources).
	 *
	 * @param string $text
	 * @param non-empty-string $path
	 * @return self
	 */
	public static function buttonWithImagePath(string $text, string $path) : self{
		return new self(["type" => "button", "text" => $text, "image" => ["type" => self::BUTTON_IMAGE_TYPE_PATH, "data" => $path]], ["interactable" => true]);
	}

	/**
	 * Creates a button with text and an image URL (HTTP source).
	 *
	 * @param string $text
	 * @param non-empty-string $url
	 * @return self
	 */
	public static function buttonWithImageUrl(string $text, string $url) : self{
		return new self(["type" => "button", "text" => $text, "image" => ["type" => self::BUTTON_IMAGE_TYPE_URL, "data" => $url]], ["interactable" => true]);
	}

	/**
	 * Creates a horizontal divider. Useful when organizing sections.
	 *
	 * @return self
	 */
	public static function divider() : self{
		return new self(["type" => "divider", "text" => ""], ["interactable" => false]);
	}

	/**
	 * Creates a heading with a larger font size than normal text.
	 *
	 * @param string $label
	 * @return self
	 */
	public static function header(string $label) : self{
		return new self(["type" => "header", "text" => $label], ["interactable" => false]);
	}

	/**
	 * Creates a text box that display some given text.
	 *
	 * @param string $label
	 * @return self
	 */
	public static function label(string $label) : self{
		return new self(["type" => "label", "text" => $label], ["interactable" => false]);
	}

	/**
	 * @param array{type: string, text: string, image?: array{type: self::IMAGE_TYPE_*, data: non-empty-string}|null} $data
	 * @param array<string, mixed> $tags
	 */
	private function __construct(
		readonly public array $data,
		readonly public array $tags = []
	){}
}