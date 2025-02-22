<?php

declare(strict_types=1);

namespace cosmicpe\awaitform;

use InvalidArgumentException;
use RuntimeException;
use function array_is_list;
use function array_key_exists;
use function assert;
use function count;
use function gettype;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_string;

/**
 * Validates and processes user responses for dialogs, forms, and menus.
 * @template TReturn the value returned post-processing.
 */
final class ResponseProcessor{

	/**
	 * @return self<bool>
	 */
	public static function dialog() : self{
		/** @var self<bool> $instance */
		static $instance = new self(0);
		return $instance;
	}

	/**
	 * @return self<int<0, max>>
	 */
	public static function form() : self{
		/** @var self<int<0, max>> $instance */
		static $instance = new self(1);
		return $instance;
	}

	/**
	 * @return self<list<mixed>>
	 */
	public static function menu() : self{
		/** @var self<list<mixed>> $instance */
		static $instance = new self(2);
		return $instance;
	}

	/**
	 * @param int<0, 2> $type
	 */
	private function __construct(
		readonly private int $type
	){}

	/**
	 * @param array<string, mixed> $request
	 * @param array $tags
	 * @param mixed $response
	 * @return TReturn
	 * @throws InvalidArgumentException
	 */
	public function process(array $request, array $tags, mixed $response) : mixed{
		if($this->type === 0){
			is_bool($response) || throw new InvalidArgumentException("Unexpected response: {$response}, expected boolean");
			return $response;
		}
		if($this->type === 2){
			is_int($response) || throw new InvalidArgumentException("Unexpected response: " . gettype($response) . ", expected integer");
			isset($request["buttons"][$response]) || throw new InvalidArgumentException("Unexpected response: {$response}, index out of range");
			assert($response >= 0);
			return $response;
		}
		if($this->type === 1){
			is_array($response) || throw new InvalidArgumentException("Unexpected response: " . gettype($response) . ", expected array");
			array_is_list($response) || throw new InvalidArgumentException("Unexpected response, expected array to be a list");
			count($response) === count($request["content"]) || throw new InvalidArgumentException("Unexpected response, expected receiving " . count($request["content"]) .  " values, got " . count($response) . " values");
			foreach($request["content"] as $index => $request_data){
				array_key_exists($index, $response) || throw new InvalidArgumentException("Unexpected response, no value supplied for {$request_data["type"]} {$request_data["text"]}");
				$value = $response[$index];
				switch($request_data["type"]){
					case "dropdown":
						is_int($value) || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, expected integer");
						$value >= 0 || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, value is negative");
						$value < count($request_data["options"]) || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, value exceeds range");
						$response[$index] = match($tags[$index]["return"]){
							"key" => $value,
							"value" => $request_data["options"][$value],
							"mapping" => $tags[$index]["mapping"][$value],
							default => throw new RuntimeException("Unexpected dropdown return tag: {$tags[$index]["return"]}")
						};
						break;
					case "input":
						is_string($value) || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, expected string");
						break;
					case "label":
						$response[$index] === null || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, expected null");
						break;
					case "slider":
						is_int($value) || is_float($value) || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, expected numeric");
						$value >= $request_data["min"] || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, expected value >= {$request_data["min"]}, got {$value}");
						$value <= $request_data["max"] || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, expected value <= {$request_data["max"]}, got {$value}");
						break;
					case "step_slider":
						is_int($value) || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, expected integer");
						$value >= 0 || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, value is negative");
						$value < count($request_data["steps"]) || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, value exceeds range");
						$response[$index] = $request_data["steps"][$value];
						break;
					case "toggle":
						is_bool($value) || throw new InvalidArgumentException("Unexpected response for {$request_data["type"]} {$request_data["text"]}, expected boolean");
						break;
				}
			}
			return $response;
		}
		throw new InvalidArgumentException("Invalid type: {$this->type}");
	}
}