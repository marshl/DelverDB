<?php

/// Used for simple search and deck maker
function CreateSearchArea($twig, $args)
{
	$template = $twig->loadTemplate('searcharea.twig');
	
	$args['blocks'] = array();
	
	class block
	{
		public $name;
		public $sets = array();
	};
	
	class set
	{
		public $name;
		public $setcode;
	};
	
	foreach(Defines::$CardBlocksToSetCodes as $blockname => $setcodes)
	{
		$block = new block();
		$block->name = $blockname;
		foreach($setcodes as $setcode)
		{
			$set = new set();
			$set->setcode = $setcode;
			$set->name = Defines::$SetCodeToNameMap[$setcode];
			$block->sets[] = $set;
		}
		$args['blocks'][] = $block;
	}
	
	$types = array
	(
		'Creature',
		'Planeswalker',
		'Artifact',
		'Enchantment',
		'Instant',
		'Sorcery',
		'Land',
	);
	$args['types'] = $types;
	
	class searchtype
	{
		public $symbol;
		public $name;
		public function searchtype($_symbol, $_name)
		{
			$this->symbol = $_symbol;
			$this->name = $_name;
		}
	}
	
	$Colours = array
	(
		new searchtype('w', 'White'),
		new searchtype('u','Blue'),
		new searchtype('b','Black'),
		new searchtype('r','Red'),
		new searchtype('g','Green'),
		new searchtype('cl','Colourless'),
	);
	$args['colours'] = $Colours;
	
	$Rarities = array
	(
		new searchtype('c', 'Common'),
		new searchtype('u','Uncommon'),
		new searchtype('r','Rare'),
		new searchtype('m','Mythic'),
	);
	$args['rarities'] = $Rarities;
	
	echo $template->render($args);
}

?>