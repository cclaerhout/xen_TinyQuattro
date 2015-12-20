<?php
class Sedo_TinyQuattro_ViewPublic_Editor_ToHtml extends XFCP_Sedo_TinyQuattro_ViewPublic_Editor_ToHtml
{
	protected $debug_pollution = false;
	protected $debug_WrappedList = false;

	protected $guiltyTags;
	protected $protectedTags = '';
	protected $regexCreatePollution;
	protected $regexMatchWrappedList;
	protected $datas;
	protected $activeTag;
	protected $depth = 0;
	protected $process = false;
	
	protected $quattroEnable = false;
	protected $oldXen;

	public function renderJson()
	{
		$xenOptions = XenForo_Application::get('options');
		
		if(!isset($this->_params['bbCode']) || !$xenOptions->quattro_converter_bb_to_html)
		{
			$parent = parent::renderJson();
			return $this->connexionCheck($parent);
		}

		$this->oldXen = ($xenOptions->currentVersionId  < 1020031);
		$this->quattroEnable = Sedo_TinyQuattro_Helper_Quattro::isEnabled();
		
		$content = $this->_params['bbCode'];

		//Create Tag pollution (only if code wrapping with guilty tags)...
		$this->_initTagPollution();
		$content = $this->_createTagPollution($content);

		if($this->debug_pollution === true) { Zend_Debug::dump($content); throw new Exception('Break'); }

		//Fix Wrapped List
		$content = preg_replace_callback($this->regexMatchWrappedList, array($this, '_fixWrappedList'), $content);

		//Modify Bb Codes Text
		$this->_params['bbCode'] = $content;

		//Active HTML Parser from parent function
		$parent = parent::renderJson();

		//Get back real HTML, PHP, CODE & QUOTE Bb Codes if they have been modified
		if($this->oldXen)
		{
			$parent = $this->_getBackRealTags($parent);
		}

		return $this->connexionCheck($parent);
	}

	public function connexionCheck($parent)
	{
		/* Detect if the user is no more connected */
		$visitor = XenForo_Visitor::getInstance();

		$parent['isConnected'] = ($visitor->user_id) ? 1 : 0;
		if(!$visitor->user_id)
		{
			$parent['notConnectedMessage'] = new XenForo_Phrase('quattro_no_more_connected');
		}
		
		return $parent;	
	}

	protected function _initTagPollution()
	{
      		$guiltyTags = array_filter(explode(',', XenForo_Application::get('options')->get('tinyquattro_guilty_tags')));
      		
		$this->protectedTags = "code|php|html|quote"; //To check: why these tags? I forgot / Need an option?
		
     		foreach($guiltyTags as $key => $tag)
      		{
			$opening_regex[] = "\[$tag(?:=.+?)?\]";
			$naked[] = $tag;
			$opening[] = "[$tag]";
			$closing[] = "[\/$tag]";
			$closing_regex[] = "\[\/$tag\]";
      		}

      		$opening_regex = implode('|', $opening_regex);
      		$naked = implode('|', $naked);
      		$opening = implode('|', $opening);
      		$closing = implode('|', $closing);
      		$closing_regex = implode('|', $closing_regex);

      		$this->guiltyTags = array(
      			'list' => $guiltyTags,
      			'opening_regex' => $opening_regex,
      			'naked' => $naked,
      			'opening' => $opening,
      			'closing' => $closing,
      			'closing_regex' => $closing_regex
      		);
      		
		$this->regexCreatePollution =	"/(?x)						#active regex comments
			(?P<beginTags>(?:(?:$opening_regex))+)					#capture beginTags group => must be GuiltyTags (repeat option)
			(?!(?:(?:$opening_regex)))						#the beginTags group must be followed by a 'normal' tag (which is not a GuiltyTag)
			(?:.*)?									#Edit above line... can have some text between beginTags group and 'normal' tag (experimental - delete line if problem)
			\[(?!\/)(?P<tag>(?!$naked\]).+?)\]					#the normal tag must be an opening tag (capture naked tag) and NOT a guilty tags 
			.*?									#go to...
			(?P<endingTags>(?:\[\/.+?\])+)?$					#... the end and if match a endingsTags group, proceed to the capture
			/iu";									//Options: case insensitive + unicode

		$this->regexMatchWrappedList = "/(?x)						#active regex comments
			(?P<beginTags>(?:(?:$opening_regex))+)					#capture beginTags group => must be GuiltyTags (repeat option)
			(?P<List>								#capture list starts...
			\s*									#... but firt capture and permit any white spaces before
			\[list(?:=.*?)?\]							#list opening tag is here (options supported)
			[\s\S]+?								#match any caracters (even white spaces such as carriage return) until...
			\[\/list\]								#list closing tag
			\s*									#capture and permit any white spaces after
			)									#capture list ends
			(?P<endingTags>(?:(?:$closing_regex))+)					#capture closingTags
			/ui";									//Options: case insensitive + unicode
	}

	/******
	*	Parent function to create Tag Pollution (Level: Parent)
	*	It will init the Regex Line by line mode (m)
	**/
	protected function _createTagPollution($string)
	{
  		//Line by Line
  		$string = preg_replace_callback('#^.+$#mui', array($this, '_createTagPollutionCallback_L1'), $string);

		return $string;
	}

	/******
	*	Meta Pre-Parser line by line (Level 1)
	**/
    	protected function _createTagPollutionCallback_L1($L0)
	{
		$line = $L0[0];

		//Execute Main Pre-Parser
		$line = $this->_createTagPollutionCallback_L2($line);

		//Avoid TinyMCE breaking for code, php, html & quote Bb codes [The Wysiwyg function of these Bb Codes is useless anyway]
		if($this->oldXen)
		{
			$line = preg_replace('#\[(/)?('.$this->protectedTags.')\]#ui', '[$1$2_parser_fix]', $line);
		}

		//Fix for Lists (only those processed and matched with Main Pre-parser)
		$line = $this->_fixPollutedBbCodeList($line);

		return $line;
	}

	/******
	*	Main Pre-Parser line by line (Level 2)
	**/
    	protected function _createTagPollutionCallback_L2($line)
	{
		//Check if guilty tags are found before another 'not guilty tag', capture begin group & ending group
		if(preg_match($this->regexCreatePollution, $line, $matches))
		{
			$tag = $matches['tag'];
			$wrappingTags = $this->_initWrappingTags($matches);

			//If wrappingTags not null proceed
			if(!empty($wrappingTags))
			{
				$this->process = true;

				//Increment if tags are found again
				if(	isset($this->activeTag[$this->depth]['tag']) 
					&& 
					$this->activeTag[$this->depth]['tag'] == $tag
				)
				{
					$this->depth++;
				}

				$this->activeTag[$this->depth]['tag'] = $tag;
				$this->_createWrappingTags($wrappingTags, $this->depth);

				$closingCurrent = $this->activeTag[$this->depth]['after'];				

				if($this->depth == 0)
				{
					$output = "{$line}{$closingCurrent}";
					if($this->debug_pollution === true){ $output .= '====== return 1'; }

					return $output;
				}
				else
				{
					$flattenParent = $this->_flattenParent($this->depth);
					$openingParent = $flattenParent['before'];
					$closingParent = $flattenParent['after'];
					
					$output = "{$openingParent}{$line}{$closingCurrent}{$closingParent}";
					if($this->debug_pollution === true){ $output .= '====== return 2'; }

					return $output;
				}
			}

			$flattenParent = $this->_flattenParent($this->depth);
			$openingParent =  $flattenParent['before'];
			$closingParent = $flattenParent['after'];

			$output = "{$openingParent}{$line}{$closingParent}";
			if($this->debug_pollution === true) { $output .= '====== return 3'; }
			
			return $output;
		}
		//Still need to increment and wrap when match an existed opening tag even if the regex pattern is not matched
		elseif(	isset($this->activeTag[$this->depth]['tag']) 
			&& 
			preg_match('#\[' . preg_quote($this->activeTag[$this->depth]['tag'], '#') . '\]#ui', $line)
		)
		{
			$tag = $this->activeTag[$this->depth]['tag'];
			$this->depth++;
			$this->activeTag[$this->depth]['tag'] = $tag;
			$wrappingTags = '';
			$this->_createWrappingTags($wrappingTags, $this->depth);

			$flattenParent = $this->_flattenParent($this->depth);
			$openingParent =  $flattenParent['before'];
			$closingParent = $flattenParent['after'];			

			$output = "{$openingParent}{$line}{$closingParent}";

			//check if close tag on the same line
			if(preg_match('#\[/' . preg_quote($tag, '#') . '\]#ui', $line))
			{
				$this->depth--;
			}

			if($this->debug_pollution === true) { $output .= '====== return 4'; }

			return $output;
		}


		if($this->process === false)
		{
			return $line;
		}

		//When match closing tag
		if(preg_match('#\[/' . preg_quote($this->activeTag[$this->depth]['tag'], '#') . '\]#iu', $line))
		{
			$currentOpening = $this->activeTag[$this->depth]['before'];

			if($this->depth == 0)
			{
				$output = "{$currentOpening}{$line}";
				$this->activeTag[0] = '';
				$this->process = false;

				if($this->debug_pollution === true) { $output .= '====== return 5'; }
			}
			else
			{
				$flattenParent = $this->_flattenParent($this->depth);
				$openingParent =  $flattenParent['before'];
				$closingParent = $flattenParent['after'];
				
				$output = "{$openingParent}{$currentOpening}{$line}{$closingParent}";

				unset($this->activeTag[$this->depth]);
				$this->depth--;

				if($this->debug_pollution === true) { $output .= '====== return 6'; }
			}

			return $output;
		}
		else
		{
			//Don't repeat twice the same opening/closing tags
			$currentOpening = $this->activeTag[$this->depth]['before'];
			$currentClosing = $this->activeTag[$this->depth]['after'];
			
			$line = preg_replace('#^' . preg_quote($currentOpening, '#') . '#ui', '', $line);
			$line = preg_replace('#' . preg_quote($currentClosing, '#') . '$#ui', '', $line);

			if($this->depth == 0)
			{
				$output = "{$currentOpening}{$line}{$currentClosing}";

				if($this->debug_pollution === true) { $output .= '====== return 7'; }
			}
			else
			{
				$flattenParent = $this->_flattenParent($this->depth);
				$openingParent =  $flattenParent['before'];
				$closingParent = $flattenParent['after'];

				$output = "{$openingParent}{$currentOpening}{$line}{$closingParent}";

				if($this->debug_pollution === true) { $output .= '====== return 8'; }
			}

			return $output;
		}
	}

	protected function _initWrappingTags($matches, $options = null)
	{
		//Get Wrapping tags based on the difference of begin & ending groups
      		$beginTags = $this->_getTagsArray($matches['beginTags']);
      		$beginTagWithOptions = $this->_getTagsArray($matches['beginTags'], false);
      		$endingTags = (isset($matches['endingTags'])) ? $this->_getTagsArray($matches['endingTags']) : array();

		if($options == 'intersect')
		{
			$array = array_intersect($beginTags, $endingTags);
			$array = $this->_getBackOptions($array, $beginTagWithOptions);
			
			$beginTags = $endingTags = array();

			foreach($array as $fullTag)
			{
				$beginTags[] = "[$fullTag]";
				$tag = $this->_removeOptionsInOpeningTag($fullTag);
				$endingTags[] = "[/$tag]";
			}

			$beginTags = implode('', $beginTags);
			$endingTags = implode('',  array_reverse($endingTags));

			return  array(
				'array' => $array,
				'begin' => $begin,
				'end' => $tag
			);
		}

		$array = array_diff($beginTags, $endingTags);

		return $this->_getBackOptions($array, $beginTagWithOptions);
	}

    	protected function _getBackOptions($results, $beginTagWithOptions)
	{
		$diff = array_diff($beginTagWithOptions, $results);

		//If both array are the same no need to waste time
		if(empty($diff))
		{
			return $results;
		}

		//Differences are with options
		$results = array();
		$mem = array();
		
		foreach($diff as $key => $tagWithOptions)
		{
			$nakedTag = $this->_removeOptionsInOpeningTag($tagWithOptions);
			
			if(array_search($nakedTag, $results) === false)
			{
				continue; //No tag found
			}

			/*At least 1 key found*/
			$mem[] = $nakedTag;
			$keys = array_keys($results, $nakedTag);
			$i = count(array_keys($mem, $nakedTag)) - 1;

			$results[$keys[$i]] = $tagWithOptions;
		}

		return array_filter($results);
	}

    	protected function _getTagsArray($datas, $killoptions = true)
	{
		if(empty($datas))
		{
			return array();
		}

		$datas = explode('][', $datas);

		foreach ($datas as &$data)
		{
			//Kill tags
			$data = preg_replace('#[\[\]/]#ui', '', $data);

			//Kill options
			if($killoptions !== false)
			{
				$data = $this->_removeOptionsInOpeningTag($data);
			}
		}

		return $datas;
	}

    	protected function _createWrappingTags($wrappingTags, $depth)
	{
		if(empty($wrappingTags))
		{
			$this->activeTag[$this->depth]['before'] = '';
			$this->activeTag[$this->depth]['after'] = '';
		}
		else
		{
			foreach($wrappingTags as &$tag)
			{
				$tag = "[$tag]";
			}

			//Opening tags
			$this->activeTag[$this->depth]['before'] = implode('', $wrappingTags);

			//Closing tags
			$wrappingTags = array_reverse($wrappingTags);
			$closingTags = $this->_deleteTagsOptions(implode('', $wrappingTags));
			$closingTags = str_replace('[', '[/', $closingTags);
			$this->activeTag[$this->depth]['after'] = $closingTags;
		}
	}

    	protected function _flattenParent($depth)
	{
		if($depth == 0)
		{
			$output['before'] = '';
			$output['after'] = '';

			if(isset($this->activeTag[0]['before']))
			{
				$output['before'] = $this->activeTag[0]['before'];
			}

			if(isset($this->activeTag[0]['after']))
			{
				$output['after'] = $this->activeTag[0]['after'];
			}

			return $output;
		}

		$parts = array_slice($this->activeTag, 0, $depth);

		foreach ($parts as $part)
		{
			$before[] = $part['before'];
			$after[] = $part['after'];
		}

		$output['before'] = implode('', $before);
		$output['after'] = implode('', array_reverse($after));

		return $output;
	}

	protected function _deleteTagsOptions($string)
	{
		$string = preg_replace('#(\[.+?)=.+?(\])#ui', '$1$2', $string);
		return $string;
	}

	protected function _removeOptionsInOpeningTag($openingTag)
	{	
		$cleanTag = preg_replace('#=.+$#ui', '', $openingTag);
		return $cleanTag;
	}

	protected function _fixPollutedBbCodeList($line)
	{
		/*****
		*	# Rules for lists
		*	0) If decoration BbCodes are on the same line than the list tag, then the Pollution Level 2 will have modified the code
		*	1) This tag [*] must be the first of the line (except if there is the list tag of course in the same line of course)
		*	2) All other decoration style must be line by line AFTER the tags [*]
		*
		*	# Example
		*	[center][LIST=1][*][B]zdzadzad[/B]
		*	[*][B]dzadza[/B]
		*	[*][B]dzadzad[/B]
		*	[*][B]zdzad[/B]
		*	[/LIST][/center]
		***/

		if(preg_match('#\[\*\]#ui', $line))
		{
			$line = str_replace('[*]', '', $line);
			$line = '[*]' . $line;
		}

		if(preg_match('#(\[(?:/)?list(?:=.*?)?\])#ui', $line, $match))
		{
			$line = str_replace($match[1], '', $line);
			$line = $match[1] . $line;
		}

		//To do: clean empty guiltyTags <= 2013/06 = To check

		return $line;
	}

    	protected function _getBackRealTags($string)
	{
		$string = preg_replace('#\[((?:/)?)(\w+?)_parser_fix\](?:\[\1\2\])?#ui', '[$1$2]', $string);

		return $string;
	}
	
	protected function _mceQuoteFix($string)
	{
		
		//Multi quotes clean end tag fix
		$string = preg_replace('#\[/QUOTE\]</p><p>\[QUOTE\](.*?</blockquote>)#i', '</blockquote></p><p><blockquote>$1', $string);

		//Multi quotes clean end tag fix
		$string = preg_replace('#\[/QUOTE\]</p><p>\[QUOTE\](.*?</blockquote>)#i', '</blockquote></p><p><blockquote>$1', $string);

		//Single quote end tag fix (don't use str_replace, cf for lowercase tag)
		$string = preg_replace('#\[/QUOTE\]</blockquote>#i', '</blockquote>', $string);


		$string = preg_replace('#\[/quote\]</p><p>(.*?)</blockquote>#i', '</blockquote></p><p>$1', $string);


		return $string;
	}

	protected function _fixWrappedList($matches)
	{
		/*****
		*	# Example
		*	[center]
		*	[LIST=1][*][B]zdzadzad[/B]
		*	[*][B]dzadza[/B]
		*	[*][B]dzadzad[/B]
		*	[*][B]zdzad[/B]
		*	[/LIST][/center]
		***/

		$wrappingTags = $this->_initWrappingTags($matches, 'intersect');

		if(empty($wrappingTags['array']))
		{
			//Must have an error, let's the user deals with it
			return $matches['beginTags'] . $matches['List'] . $matches['endingTags'];
		}

		$this->datas['begin'] = $wrappingTags['begin'];
		$this->datas['end'] = $wrappingTags['end'];
		$list = $matches['List'];

		$list = preg_replace_callback('#^.+$#mui', array($this, '_fixWrappedList_L1'), $list);

		if($this->debug_WrappedList === true) {	Zend_Debug::dump($list); throw new Exception('Break'); }

		return $list;
	}

	protected function _fixWrappedList_L1($matches)
	{
		$line = $matches[0];
		$line = $this->_fixPollutedBbCodeList($line); //Can use this function to be sure 'list' then [*] are first elements of the line

		if(preg_match('#\[\*\]#', $line))
		{
			$line = preg_replace('#(\[\*\])(.+?)$#ui', '$1' . $this->datas['begin'] . '$2' . $this->datas['end'], $line);
		}

		return $line;
	}
}
//Zend_Debug::dump($class);

/**********************************
	REGEX
***********************************
Old:
(?P<beginTags>(?:(?:\[left\]|\[center\]|\[right\]|\[b\]|\[i\]|\[u\]|\[s\]))+)(?!(?:(?:\[left\]|\[center\]|\[right\]|\[b\]|\[i\]|\[u\]|\[s\])))\[(?!/)(?P<tag>.+?)\].*?(?P<endingTags>(?:\[/.+?\])+)?$
New: includes tags options (ex: fonts, color, etc.)

**********************************
	Test patterns
**********************************
[CENTER][b][quote][/b]
aaaa
bbbb
cccc
[/quote][/CENTER]

=>OK but once the message is recorded inside the database if edit again, extra tags will be added (will not affect the message display) *See not at the bottom*

[CENTER][b][quote]
aaaa
bbbb
cccc
[/quote][/b][/CENTER]

=>OK | Saved -> edit OK

[CENTER][quote][/CENTER]
[CENTER]aaaa[/CENTER]
[CENTER]bbbb[/CENTER]
[CENTER]cccc[/CENTER]
[CENTER][/quote][/CENTER]

=>OK | just to check if  regex was not too greedy

[CENTER][B][quote]efezfez
[CENTER][B]efezf[/B][/CENTER]
[LEFT][B]zddzdzd[/B][/LEFT]
dzdzdzd
ezfez[/quote][/B][/CENTER]

=>OK | Saved -> edit OK

[CENTER][B][quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[right][quote2]eeeeee
ffff[/quote2][/right]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/B][/CENTER]
=>OK (level 1) | Saved -> edit OK

[CENTER][B][quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[right][quote2]eeeeee
zorro
ffff[/quote2][/right]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/B][/CENTER]
=>OK (level 1) | Saved -> edit OK

[CENTER][B][quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[quote2]eeeeee[/quote2]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/B][/CENTER]
=>OK (same tag line) | Saved -> edit OK


[CENTER][B][quote2]aaaaa[/B][/CENTER]
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[quote2]eeeeee[/quote2]
[RIGHT][B]dddddd[/B][/RIGHT]
[center][b][/quote2][/B][/CENTER]
=>OK | Saved -> edit OK

[CENTER][B][quote2]aaaaa[/B][/CENTER]
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[quote2]eeeeee[/quote2]
[RIGHT][B]dddddd[/B][/RIGHT]
[center][b][/quote2][/B][/CENTER]

ABC
=>OK | Saved -> edit OK

[CENTER][B][quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[quote2]eeeeee[/quote2]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/B][/CENTER]
=> OK | Saved -> edit OK

[CENTER][COLOR=#ffcc00][SIZE=5][FONT=arial black][quote2]
aaaa
bbbb
cccc
[/quote2][/FONT][/SIZE][/COLOR][/CENTER]
=> OK | Saved -> edit OK

[CENTER][b]dzadzdzadzad[quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/b][/CENTER]
=> OK with experimental mode | Saved -> edit OK

[CENTER][FONT=arial black]dzadzdzadzad[quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/FONT][/CENTER]
=> OK with experimental mode | Saved -> edit OK

[CENTER][FONT=arial black]dzadzdzadzad[quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[IMG]http://www.google.fr/images/srpr/logo3w.png[/IMG]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/FONT][/CENTER]
=> OK with experimental mode | Saved -> edit OK

[CENTER][FONT=arial black]dzadzdzadzad[quote2]aaaaa
[LEFT][B]bbbbb[/B][/LEFT]
cccccc
[media=youtube]uyln-E0HoEE[/media]
[RIGHT][B]dddddd[/B][/RIGHT]
[/quote2][/FONT][/CENTER]
=> OK with experimental mode | Saved -> edit OK

[CENTER][FONT=arial black][B][quote2][/B][/FONT][/CENTER]
[CENTER][FONT=arial black][B]aaaa[/B][/FONT][/CENTER]
[CENTER][FONT=arial black][B]bbbb[/B][/FONT][/CENTER]
[CENTER][FONT=arial black][B]cccc[/B][/FONT][/CENTER]
[CENTER][FONT=arial black][B][/quote2][/B][/FONT][/CENTER]

[CENTER][FONT=arial black][B][quote2]
aaaa
bbbb
cccc
[/quote2][/B][/FONT][/CENTER]
=>OK bug fixed (options are now getback inside the tag)


BUG: [ FIXED => REGEX TOO GREEDY]

[CENTER][COLOR=#808000][SIZE=5][B]Title[/B][/SIZE][/COLOR][COLOR=#808000][SIZE=5][B] Title complement[/B][/SIZE][/COLOR]

[COLOR=#808000][SIZE=2][B][SIZE=1]Text[/SIZE] Text 2[/B][/SIZE][/COLOR]
[I][SIZE=2][URL='http://www.google.fr']View: http://www.google.fr[/URL][/SIZE][/I][/CENTER]
 
 
this should not be centered but it is...


WORKS
[CENTER][COLOR=#808000][SIZE=5][B]Title[/B][/SIZE][/COLOR]Title complement[/COLOR]

[COLOR=#808000][SIZE=2][B][SIZE=1]Text[/SIZE] Text 2[/B][/SIZE][/COLOR]
[I][SIZE=2][URL='http://www.google.fr']View: http://www.google.fr[/URL][/SIZE][/I]
[/CENTER]
 
 
this should not be centered but it is...


BUG simplified:[ FIXED => REGEX TOO GREEDY]
[CENTER][COLOR=#808000][SIZE=5]Title[/SIZE][/COLOR][B] Title complement[/B]

[/CENTER]
  
this should not be centered but it is...


Works
[CENTER][COLOR=#808000][SIZE=5]Title[/SIZE][/COLOR]

[/CENTER]
  
this should not be centered but it is...


**********************************
	NOTE
**********************************

#Controller:  XenForo_ControllerPublic_Thread
Function: actionAddReply()
Callback: XenForo_Helper_String::autoLinkBbCode

#Helper & Function: XenForo_Helper_String::autoLinkBbCode
Callback:
		$parser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('XenForo_BbCode_Formatter_BbCode_AutoLink', false));
		return $parser->render($string);


***********************************/