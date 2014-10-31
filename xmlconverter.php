<?php

include_once "passwords.php";
include_once "defines.php";
//include_once "users.php";

$SQLUser = $SQLUsers['oracle_search'];
$DelverDBLink = new mysqli("localhost", $SQLUser->username, $SQLUser->password, "magic_db");
if ( $DelverDBLink->connect_errno )
{
	$str = "Database connection failure ($DelverDBLink->connect_errno): $DelverDBLink->connect_error";
	$DBLog->err( $str );
	ReturnXMLError( 0, "Internal error: $str" );
}

$mode = "oracle";

if ( $mode == "oracle" )
{
	$OracleStmt = $DelverDBLink->prepare( "SELECT * FROM oracle" );
	$OracleStmt->execute();
	$OracleResults = $OracleStmt->get_result();
	
	$SetsStmt = $DelverDBLink->prepare( "SELECT * FROM cardsets" );
	$SetsStmt->execute();
	$SetResults = $SetsStmt->get_result();
	
	$UserCardStmt = $DelverDBLink->prepare( "SELECT * FROM usercards WHERE ownerid = 1" );
	$UserCardStmt->execute();
	$UserCardResults = $UserCardStmt->get_result();
	
	$SetArray = array();
	$UserCardArray = array();
	
	while ( $row = $SetResults->fetch_assoc() )
	{
		if ( !array_key_exists( $row['cardid'], $SetArray ) )
		{
			$SetArray[$row['cardid']] = array();	
		}
		
		$Set = array( 'setcode' => $row['setcode'], 'rarity' => $row['rarity'] );
		
		$SetArray[$row['cardid']][] = $Set;
	}
	
	while ( $row = $UserCardResults->fetch_assoc() )
	{
		$cardID = $row['cardid'];
		$setcode = $row['setcode'];
		$count = $row['count'];
		if ( !array_key_exists( $cardID, $UserCardArray ) )
		{
			$UserCardArray[$cardID] = array();
		}
		
		$UserCardArray[$cardID][$setcode] = $count;
	}
	
	$responseXML = new SimpleXMLElement("<oracle></oracle>");
	
	$Replacements = array( 
		"cardid"=>"id",
		"numcolours"=>"nc",
		"colouridentity"=>"ci",
		"subtype"=>"st",
		"type"=>"t",
		"rating"=>"rt",
		"power"=>"pw",
		"toughness"=>"tf",
		"loyalty"=>"ly",
		"rules"=>"r",
		"colour"=>"cl",
		"name"=>"n",
		"cost"=>"cs",
		"numpower"=>"npw",
		"numtoughness"=>"ntf",
		"watermark"=>"wm",
		"handmod"=>"hm",
		"lifemod"=>"lm",
		"linktype"=>"lt", 
		"linkid"=>"lid", );
	
	$i = 0;
	while ( ($row = $OracleResults->fetch_assoc()) )//&& ++$i < 100 )
	{	
		if ( $row['type'] == NULL )
		{
			continue;	
		}
		
		$cardXML = $responseXML->addChild('card');
		
		$cardID = $row['cardid'];
		foreach ( $row as $key => $value )
		{
			if ( $key == "numpower" && (string)$value == (string)$row['power'] 
			  || $key == "numtoughness" && (string)$value == (string)$row['toughness'] )
			{
				continue;	
			}
			
			if ( !is_null( $value ) )
			{
				$newKey = $key;
				if ( array_key_exists( $key, $Replacements ) )
				{
					$newKey = $Replacements[$key];
				}
				
				$value = str_replace( "&", "&amp;", $value );
				$cardXML->addChild( $newKey, utf8_encode( $value ) );
			}
		}
		
		$setListElement = $cardXML->addChild( "sets" );
		foreach ( $SetArray[$cardID] as $key => $set )
		{
			$setElement = $setListElement->addChild( "s" );
			$setcode = $set['setcode'];
			$setElement->addChild( "cd", $setcode );
			$setElement->addChild( "r", $set['rarity'] );
			
			if ( array_key_exists( $cardID, $UserCardArray )
			  && array_key_exists( $setcode, $UserCardArray[$cardID] ) )
			{
				$setElement->addChild( "c", $UserCardArray[$cardID][$setcode] );
			}
		}
		
	}
	//header('Content-Type: text/xml;  charset=utf-8');
	echo $responseXML->asXML();
}
else if ( $mode == "cardsets" )
{
	$SearchStmt = $DelverDBLink->prepare( "SELECT * FROM cardsets" );
	$SearchStmt->execute();
	$SearchResults = $SearchStmt->get_result();
	
	$responseXML = new SimpleXMLElement("<cardsets></cardsets>");
	
	while ( $row = $SearchResults->fetch_assoc() )
	{
		$cardXML = $responseXML->addChild('cardset');
	
		foreach ( $row as $key => $value )
		{
			if ( !is_null( $value ) )
			{
				$value = str_replace( "&", "&amp;", $value );
				$cardXML->addChild( $key, utf8_encode( $value ) );
			}
		}
	
	}
	header('Content-Type: text/xml;  charset=utf-8');
	echo $responseXML->asXML();
}
else if ( $mode == "subtype" || $mode == "type" )
{
	
	$SearchStmt = $DelverDBLink->prepare( "SELECT DISTINCT $mode FROM oracle" );
	$SearchStmt->execute();
	$SearchResults = $SearchStmt->get_result();
	
	$allTypes = array();
	
	$i = 0;
	while ( $row = $SearchResults->fetch_assoc() )
	{
		$types = explode( ' ', $row[$mode] );
		foreach ( $types as $key => $type )
		{
			$allTypes[$type] = ++$i;
		}
	}
	
	$allTypes = array_flip( $allTypes );
	sort( $allTypes );
	foreacH ( $allTypes as $type => $value )
	{
		print "\"$value\",  ";
	}
	
}
else if ( $mode == "setlist" )
{
	$setListXml = new SimpleXMLElement( "<setlist></setlist>" );
	
	foreach ( Defines::$CardBlocksToSetCodes as $blockname => $setarray )
	{
		$blockElement = $setListXml->addChild( "format" );
		$blockElement->addChild( "name", $blockname );
		
		foreach ( $setarray as $index => $setcode )
		{
			$setname = Defines::$SetCodeToNameMap[$setcode];	
			
			$setElement = $blockElement->addChild( "set" );
			$setElement->addChild( "code", $setcode );
			$setElement->addChild( "name", $setname );
		}
			
	}
	echo $setListXml->asXML();
}

exit;

?>