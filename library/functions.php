<?php
/*
*************************************************************************
*                                                                       *
* ProSpamFilter2                                                        *
* Bridge between Webhosting panels & SpamExperts filtering			    *
*                                                                       *
* Copyright (c) 2010 SpamExperts B.V. All Rights Reserved,              *
*                                                                       *
*************************************************************************
*                                                                       *
* Email: support@spamexperts.com                                        *
* Website: htttp://www.spamexperts.com                                  *
*                                                                       *
*************************************************************************
*                                                                       *
* This software is furnished under a license and may be used and copied *
* only in accordance with the  terms of such license and with the       *
* inclusion of the above copyright notice. No title to and ownership    *
* of the software is  hereby  transferred.                              *
*                                                                       *
* You may not reverse engineer, decompile or disassemble this software  * 
* product or software product license.                                  *
*                                                                       *
* SpamExperts may terminate this license if you don't comply with any   *
* of the terms and conditions set forth in our end user                 *
* license agreement (EULA). In such event, licensee agrees to return    *
* licensor  or destroy  all copies of software upon termination of the  *
* license.                                                              *
*                                                                       *
* Please see the EULA file for the full End User License Agreement.     *
*                                                                       *
*************************************************************************
*/
	if( !function_exists( "wl" ) )
	{
		function wl($txt)
		{
			echo "{$wl}\n";
		}
	}
	
	if( !function_exists( "bcdiv" ) ) //<-- This function *might* exist in PHP already. Or atleas customers' PHP
	{
		function bcdiv( $first, $second, $scale = 0 )
		{
			$res = $first / $second;
			return round( $res, $scale );
		}
	}

	if( !function_exists( "_sort_by_dots_qty_rev" ) )
	{		
		function _sort_by_dots_qty_rev($d1, $d2)
		{
			$dots1 = substr_count($d1, '.');
			$dots2 = substr_count($d2, '.');

			if ($dots1 == $dots2) {
				return 0;
			} elseif ($dots1 > $dots2) {
				return -1;
			} else {
				return 1;
			}
		}
	}

	if( !function_exists( "simpleXMLToArray" ) )
	{			
		function simpleXMLToArray($xml,$flattenValues=true,$flattenAttributes = true,$flattenChildren=true,$valueKey='@value',$attributesKey='@attributes',	$childrenKey='@children')
		{
			$return = array();
			if(!($xml instanceof SimpleXMLElement)){return $return;}
			$name = $xml->getName();
			$_value = trim((string)$xml);
			if(strlen($_value)==0){$_value = null;};

			if($_value!==null){
				if(!$flattenValues){$return[$valueKey] = $_value;}
				else{$return = $_value;}
			}

			$children = array();
			$first = true;
			foreach($xml->children() as $elementName => $child){
				$value = simpleXMLToArray($child, $flattenValues, $flattenAttributes, $flattenChildren, $valueKey, $attributesKey, $childrenKey);
				if(isset($children[$elementName])){
					if($first){
						$temp = $children[$elementName];
						unset($children[$elementName]);
						$children[$elementName][] = $temp;
						$first=false;
					}
					$children[$elementName][] = $value;
				}
				else{
					$children[$elementName] = $value;
				}
			}
			if(count($children)>0){
				if(!$flattenChildren){$return[$childrenKey] = $children;}
				else{$return = array_merge($return,$children);}
			}

			$attributes = array();
			foreach($xml->attributes() as $name=>$value){
				$attributes[$name] = trim($value);
			}
			if(count($attributes)>0){
				if(!$flattenAttributes){$return[$attributesKey] = $attributes;}
				else{$return = array_merge($return, $attributes);}
			}
		   
			return $return;
		}
	}

	if( !function_exists( "argv2array" ) )
	{			
		function argv2array ($argv)
		{
			$opts = array();
			$argv0 = array_shift($argv);

			while(count($argv))
			{
				$key = array_shift($argv);
				$value = array_shift($argv);
				$opts[$key] = $value;
			}
			return $opts;
		}
	}


	//
	if( !function_exists( "xml2array" ) )
	{		
		function xml2array($contents, $get_attributes=1, $priority = 'tag') {
			if(!$contents) return array();

			if(!function_exists('xml_parser_create')) {
				//print "'xml_parser_create()' function not found!";
				return array();
			}

			//Get the XML parser of PHP - PHP must have this module for the parser to work
			$parser = xml_parser_create('');
			xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
			xml_parse_into_struct($parser, trim($contents), $xml_values);
			xml_parser_free($parser);

			if(!$xml_values) return;//Hmm...

			//Initializations
			$xml_array = array();
			$parents = array();
			$opened_tags = array();
			$arr = array();

			$current = &$xml_array; //Refference

			//Go through the tags.
			$repeated_tag_index = array();//Multiple tags with same name will be turned into an array
			foreach($xml_values as $data) {
				unset($attributes,$value);//Remove existing values, or there will be trouble

				//This command will extract these variables into the foreach scope
				// tag(string), type(string), level(int), attributes(array).
				extract($data);//We could use the array by itself, but this cooler.

				$result = array();
				$attributes_data = array();
				
				if(isset($value)) {
					if($priority == 'tag') $result = $value;
					else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
				}

				//Set the attributes too.
				if(isset($attributes) and $get_attributes) {
					foreach($attributes as $attr => $val) {
						if($priority == 'tag') $attributes_data[$attr] = $val;
						else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
					}
				}

				//See tag status and do the needed.
				if($type == "open") {//The starting of the tag '<tag>'
					$parent[$level-1] = &$current;
					if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
						$current[$tag] = $result;
						if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
						$repeated_tag_index[$tag.'_'.$level] = 1;

						$current = &$current[$tag];

					} else { //There was another element with the same tag name

						if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
							$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
							$repeated_tag_index[$tag.'_'.$level]++;
						} else {//This section will make the value an array if multiple tags with the same name appear together
							$current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
							$repeated_tag_index[$tag.'_'.$level] = 2;
							
							if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
								$current[$tag]['0_attr'] = $current[$tag.'_attr'];
								unset($current[$tag.'_attr']);
							}

						}
						$last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
						$current = &$current[$tag][$last_item_index];
					}

				} elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
					//See if the key is already taken.
					if(!isset($current[$tag])) { //New Key
						$current[$tag] = $result;
						$repeated_tag_index[$tag.'_'.$level] = 1;
						if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;

					} else { //If taken, put all things inside a list(array)
						if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...

							// ...push the new element into that array.
							$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
							
							if($priority == 'tag' and $get_attributes and $attributes_data) {
								$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
							}
							$repeated_tag_index[$tag.'_'.$level]++;

						} else { //If it is not an array...
							$current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
							$repeated_tag_index[$tag.'_'.$level] = 1;
							if($priority == 'tag' and $get_attributes) {
								if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
									
									$current[$tag]['0_attr'] = $current[$tag.'_attr'];
									unset($current[$tag.'_attr']);
								}
								
								if($attributes_data) {
									$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
								}
							}
							$repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
						}
					}

				} elseif($type == 'close') { //End of tag '</tag>'
					$current = &$parent[$level-1];
				}
			}
			
			return($xml_array);
		} 
	}

if( !function_exists( "smartCopy" ) )
{	
    function smartCopy($source, $dest, $options=array('folderPermission'=>0755,'filePermission'=>0644))
    {
        $result=false;
                 
        if (is_file($source)) 
		{
            if ($dest[strlen($dest)-1]== DS )
			{
                if (!file_exists($dest)) 
				{
					// PHP5 mkdir function supports recursive, so just use that
					mkdir($dest,$options['folderPermission'],true);
                }
                $__dest=$dest.DS.basename($source);
            }
			else 
			{
                $__dest=$dest;
            }
            $result=copy($source, $__dest);
            chmod($__dest,$options['filePermission']);
           
        } 
		elseif(is_dir($source)) 
		{
            if ($dest[strlen($dest)-1]== DS ) 
			{
                if ($source[strlen($source)-1]== DS ) 
				{
                    //Copy only contents
                }
				else 
				{
                    //Change parent itself and its contents
                    $dest=$dest.basename($source);
                    @mkdir($dest,$options['folderPermission']);
                }
            } 
			else 
			{
                if ($source[strlen($source)-1]== DS ) 
				{
                    //Copy parent directory with new name and all its content
                    @mkdir($dest,$options['folderPermission']);
                }
				else 
				{
                    //Copy parent directory with new name and all its content
                    @mkdir($dest,$options['folderPermission']);
                }
            }

            $dirHandle=opendir($source);
            while($file=readdir($dirHandle))
            {
                if($file!="." && $file!=".." && (!strstr($file, ".svn")) )
                {
                    $result=smartCopy($source.DS.$file, $dest.DS.$file, $options);
                }
            }
            closedir($dirHandle);
           
        }
		else
		{
            $result=false;
        }
        return $result;
    } 	
}

if (! function_exists('isWindows')) {
    function isWindows() {
        return stripos(PHP_OS, 'win') === 0;
    }
}

if( !function_exists( "rrmdir" ) )	
{
	 function rrmdir($dir)
	 {
         if (isWindows()) {
             system("rmdir $dir /s /q");
         } else {
             system("rm -rf $dir");
         }
	 }
}

if( !function_exists( "is_cli" ) )	
{
	function is_cli() 
	{ 
		if ( !isset($_SERVER['HTTP_USER_AGENT']) )
		{ 
			return true; 
		} else { 
			return false; 
		} 
	}
}

if( !function_exists( "file_perms" ) )
{
	function file_perms($file, $octal = false)
	{
		if(!file_exists($file)) return false;
		try {
			$perms = fileperms($file);
		} catch (Exception $e) {
			// It failed. Horribly!
			return false;
		}
		$cut = $octal ? 2 : 3;
		return substr(decoct($perms), $cut);
	}
}

if( !function_exists( "clean_domain" ) )
{
	function clean_domain( $domain )
	{
		// Lowercase it & trim it.
		$domain = strtolower( trim( $domain ) );
		
		// Explode it by the separator
		$x = explode('.', $domain);
		if(is_array($x))
		{
			// If the first part of the URL is "www"..
			if ($x[0] == "www")
			{
				// chop it off.
				unset( $x[0] );

				// Re-form the domain and give it back!
				return implode( '.', $x );
			}
		}
		// Return the domain (lowercased & trimmed) since it apparently did not contain "www." to begin with.
		return $domain;
	}
}

if( !function_exists( "array_sort" ) )
{
	function array_sort($array, $on, $order=SORT_ASC)
	{
		$new_array = array();
		$sortable_array = array();

		if (count($array) > 0 && is_array($array)) 
		{
			foreach ($array as $k => $v) 
			{
				if (is_array($v)) 
				{
					foreach ($v as $k2 => $v2) 
					{
						if ($k2 == $on) {
							$sortable_array[$k] = $v2;
						}
					}
				} else {
					$sortable_array[$k] = $v;
				}
			}

			switch ($order) 
			{
				case SORT_ASC:
					asort($sortable_array);
				break;
				case SORT_DESC:
					arsort($sortable_array);
				break;
			}

			foreach ($sortable_array as $k => $v) 
			{
				$new_array[$k] = $array[$k];
			}
		}

		return $new_array;
	}
}

// Workaround for [ZF-9088] and missing parse_ini_string for PHP < 5.3.0
if( !function_exists('parse_ini_string') )
{   
    function parse_ini_string( $string ) 
	{
        $array = Array();

        $lines = explode("\n", $string );
       
        foreach( $lines as $line ) {
            $statement = preg_match(
"/^(?!;)(?P<key>[\w+\.\-]+?)\s*=\s*(?P<value>.+?)\s*$/", $line, $match );

            if( $statement ) 
			{
                $key    = $match[ 'key' ];
                $value    = $match[ 'value' ];
               
                # Remove quote
                if( preg_match( "/^\".*\"$/", $value ) || preg_match( "/^'.*'$/", $value ) ) 
				{
                    $value = mb_substr( $value, 1, mb_strlen( $value ) - 2 );
                }
               
                $array[ $key ] = $value;
            }
        }
        return $array;
    }
}
?>