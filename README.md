# AwaitForm
Write clean form navigation flows in PocketMine-MP using async/await pattern in PHP.

## Motive
Form navigation flows are inherently asynchronous. Existing libraries use callbacks or specialized Form classes to
handle responses. Control flow syntax (e.g., while, for, continue, break) cannot be fully utilized as each form handler
gets its own isolated context. This makes several tasks challenging.

Navigation flow becomes incomprehensible in code when Form A sends user to Form B before bringing them back to Form A,
but this time with different parameters for Form A. This is often encountered with pagination ('Previous Page' and 'Next
Page' buttons), refresh mechanisms (e.g., a 'Refresh' button), and non-dismissible forms (i.e., disallowing users to
back away).

Other issues left unaddressed by conventional APIs is no way to detect and handle failure when sending forms, no defined
cleanup/finalization routine for navigation flows, and no shared state for nested forms (Form A→B→A). Existing libraries
have incorporated specialized paginated forms to avoid boilerplate, and explicit mechanisms in nested forms to allow
navigating back from child to parent form.

## Approach
AwaitForm addresses existing issues through an alternative async/await based form-handling syntax using
[await-generator](https://github.com/SOF3/await-generator).
```php
$form = AwaitForm::form("Create a Ban Report", [
	FormControl::input("Player", "Enter their gamertag"),
	FormControl::dropdown("Ban Reason", ["Hacking", "Spamming", "Toxicity"]),
	FormControl::input("Comment", "Any further comments...")
]);
[$gamertag, $reason, $comment] = yield from $form->request($player);
```
<details align="center">
	<summary>See demo</summary>
	<img alt="Ban report form" src="https://i.imgur.com/PxNGyzJ.png"/>
</details>

Users get to utilize native PHP control flow syntax (while, for, continue, break, etc.; see
[Retry Logic in Form](#1-retry-logic-in-form)) instead of a costly reimplementation of existing control structures which
existing libraries achieve using callbacks. AwaitForm features no additional special-purpose mechanism, but still aids
users in making otherwise complex [paginated](#3-paginated-button-menu) and [nested](#4-nested-forms) navigation flows.
```php
// -- initialization: e.g., make player immobile when viewing form --
$player->setNoClientPredictions(true);
while(true){
	$form = AwaitForm::form("Set home here?", [FormControl::input("Home Name:")]);
	// -- request: send form and wait for response --
	try{
		[$name] = yield from $form->request($player);
	}catch(AwaitFormException){
		// -- failure: exit loop if player closes form or disconnects --
		break;
	}
	// -- evaluate: handle response --
	if(trim($name) === ""){
		$player->sendToastNotification("Invalid Name", "Home name cannot be empty");
		continue;
	}
	$player->sendMessage("Home '{$name}' set at your location!");
	break;
}
// -- finalization/cleanup: e.g., revert player movement restriction --
$player->setNoClientPredictions(false);
```
<details align="center">
	<summary>See demo</summary>
	<video src="https://i.imgur.com/5CN4OUB.mp4"></video>
</details>

### When the user does not respond
Player disconnects, server shutdowns, validation errors, and 'busy status' throw an `AwaitFormException`. Read
`AwaitFormException::getCode()` to narrow down the cause to `ERR_VALIDATION_FAILED`, `ERR_PLAYER_REJECTED`, or
`ERR_PLAYER_QUIT`.
```php
try{
	$response = yield from $form->request($player);
}catch(AwaitFormException){
	return;
}
$player->sendMessage("Response: " . json_encode($response));
$player->sendMessage("Report Received, thank you!");
```

## Example Design Models
### 1. Retry logic in form
Revisiting the example above (creating a ban report), player gamertags require validation. In this example, the player
is sent the form again when they enter a wrong gamertag. This design includes State Persistence whereby the user's input
is not lost upon entering a wrong gamertag.
```php
$gamertag = "";
$reason = null;
$comment = "";
while(true){
	$form = AwaitForm::form("Create a Ban Report", [
		FormControl::input("Player", "Enter their gamertag", $gamertag),
		FormControl::dropdown("Ban Reason", ["Hacking", "Spamming", "Toxicity"], $reason),
		FormControl::input("Comment", "Any further comments...", $comment)
	]);
	try{
		[$gamertag, $reason, $comment] = yield from $form->request($player);
	}catch(AwaitFormException){
		break;
	}
	if(!$server->hasOfflinePlayerData($gamertag)){
		$player->sendToastNotification("Player Not Found", "'{$gamertag}' never joined this server.");
		continue;
	}
	$player->sendMessage("Response: " . json_encode([$gamertag, $reason, $comment]));
	$player->sendMessage("Report Received, thank you!");
	break;
}
```
<details align="center">
	<summary>See demo</summary>
	<video src="https://i.imgur.com/2pj5lb2.mp4"></video>
</details>

### 2. Non-dismissible form
A player is banned and is forced to acknowledge their ban. If they close the form, the form is sent again -
they cannot back away. They are also given permanent blindness until then.
```php
// -- initialization: happens before main loop --
$player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), Limits::INT32_MAX));
while(true){
	$form = AwaitForm::form("You are BANNED!", [
		FormControl::toggle("I acknowledge my ban."),
		FormControl::input("Comments", "Type any comments you have...")
	]);
	try{
		[$acknowledged, $comments] = yield from $form->request($player);
	}catch(AwaitFormException $e){
		if($e->getCode() === AwaitFormException::ERR_PLAYER_QUIT){
			break;
		}
		continue;
	}
	if($acknowledged){
		echo "Comments: ", $comments, PHP_EOL;
		break;
	}
	$player->sendToastNotification("Try Again", "Acknowledgement is needed.");
}
$player->getEffects()->remove(VanillaEffects::BLINDNESS());
```
<details align="center">
	<summary>See demo</summary>
	<video src="https://i.imgur.com/CIXXrw9.mp4"></video>
</details>

### 3. Paginated button menu
Players can spawn combat items on a PvP server. 10 items are listed at a time in a menu.
For pagination, there is a 'Previous Page' and a 'Next Page' button at the very end of
the menu.
```php
// -- initialization: shared state variables used across all pages --
$items = array_filter(VanillaItems::getAll(), fn($item) => $item instanceof Durable);
$offset = 0;
$length = 10;
while(true){
	$sublist = array_slice($items, $offset, $length);
	$buttons = [];
	foreach($sublist as $id => $item){
		$buttons[$id] = Button::simple($item->getName());
	}
	if($offset > 0) $buttons["prev"] = Button::simple("[Previous Page]");
	if($offset + $length < count($items)) $buttons["next"] = Button::simple("[Next Page]");
	$form = AwaitForm::menu("Free Items!", "Have fun soldier :)", $buttons);
	try{
		$response = yield from $form->request($player);
	}catch(AwaitFormException){
		break;
	}
	if($response === "prev"){
		$offset -= $length; // validation by-design: can never go negative
	}elseif($response === "next"){
		$offset += $length;
	}else{
		$item = $sublist[$response];
		$player->getInventory()->addItem($item);
	}
}
```
<details align="center">
	<summary>See demo</summary>
	<video src="https://i.imgur.com/o77eXSJ.mp4"></video>
</details>

### 4. Nested forms
Revisiting the first example (creating a ban report), this change adds a confirmation form and a mechanism to store
reports using a Finite State Machine.

Finite State Machines in modeling user interfaces allow you to think at a higher level of abstraction. Instead of
thinking _"After player fills a ban report; the gamertag and the reason is displayed with a yes/no button to confirm
filing the report"_, you think _"The UI is put in a CONFIRM state upon filing the report"_ and entering the state means
certain things happen.

```php
$gamertag = "";
$reason = null;
$comment = "";
$state = "CREATE";
while($state !== "DESTROY"){
	if($state === "CREATE"){
		$form = AwaitForm::form("Create a Ban Report", [
			FormControl::input("Player", "Enter their gamertag", $gamertag),
			FormControl::dropdown("Ban Reason", ["Hacking", "Spamming", "Toxicity"], $reason),
			FormControl::input("Comment", "Any further comments...", $comment)
		]);
		try{
			[$gamertag, $reason, $comment] = yield from $form->request($player);
		}catch(AwaitFormException){
			$state = "DESTROY";
			continue;
		}
		if(!$server->hasOfflinePlayerData($gamertag)){
			$player->sendToastNotification("Player Not Found", "'{$gamertag}' never joined this server.");
			continue;
		}
		$state = "CONFIRM";
	}elseif($state === "CONFIRM"){
		$message = ["Are you sure you would like to file this report? Review your details:"];
		$message[] = "Gamertag: {$gamertag}";
		$message[] = "Reason: {$reason}";
		$message[] = "Comment: {$comment}";
		$form = AwaitForm::menu("Confirm Filing Report?", implode(TextFormat::EOL, $message), [
			"yes" => Button::simple("Confirm"),
			"edit" => Button::simple("Make Changes"),
			"no" => Button::simple("Cancel")
		]);
		$response = yield from $form->requestOrFallback($player, "no");
		$state = match($response){
			"yes" => "WRITE",
			"edit" => "CREATE",
			"no" => "DESTROY"
		};
	}elseif($state === "WRITE"){
		yield from $database->asyncInsert("myplugin.ban_reports", ["offender" => $gamertag, "reason" => $reason, "comment" => $comment]);
		if($player->isConnected()){
			$player->sendToastNotification("Report Successful!", "Thank you very much.");
		}
		$gamertag = "";
		$reason = null;
		$comment = "";
		$state = "CREATE";
	}
}
```
<details align="center">
	<summary>See demo</summary>
	<video src="https://i.imgur.com/kXntqTB.mp4"></video>
</details>

## Reusing Forms
Form windows store only display properties and not player state. A form window (i.e., `AwaitForm::dialog()`,
`AwaitForm::form()`, `AwaitForm::menu()`) may be instantiated once and reused multiple times. All window properties that
are not readonly are allowed to be mutated.
```php
$form = AwaitForm::menu("title", "content", []);
$form->title = "New title";
$form->buttons[] = [Button::simple("Get free food"), "food"];
$form->buttons[] = [Button::simple("Get free block"), "block"];
while(true){
	try{
		yield from $form->request($player);
	}catch(AwaitFormException){
		break;
	}
}
```