<?php
class Sedo_TinyQuattro_Helper_MiniParser
{
	protected $_text = '';
	protected $_slaveTags = array();
	protected $_position = 0;
	protected $_tagsTree = array();
	protected $_tagsList = array();
	protected $_tagsDepth = 0;
	protected $_masterTag;

	public function __construct($text, $masterTag, array $slaveTags)
	{
		$this->_reset();
		$this->_text = $text;
		$this->_masterTag = $masterTag;
		$this->_slaveTags = $slaveTags;
	
		$this->_tagsTree[0][] = array(
			'tagName' => $masterTag,
			'isMasterTag' => true,
			'depth' => 0
		);

		//Let's start!
		$this->_init();
	}

	protected function _reset()
	{
		$this->_text = '';
		$this->_slaveTags = array();
		$this->_position = 0;
		$this->_wipTags = array();
		$this->_tagsDepth = 0;
		$this->_tagsTree = array();
		$this->_masterTag;
	}

	protected function _resetPosition()
	{
		$this->_position = 0;
	}

	protected function _init()
	{
		$length = strlen($this->_text);

		while ($this->_position < $length)
		{
			$found = $this->_searchMasterTags();
			
			if(!$found)
			{
				$this->position = $length;
				break;
			}
		}
		
		$this->_resetPosition();

		while ($this->_position < $length)
		{
			$found = $this->_searchSlaveTags();
			
			if(!$found)
			{
				$this->position = $length;
				break;
			}
		}
		
//		Zend_Debug::dump($this->_tagsTree);
		Zend_Debug::dump($this->_tagsList);
	}

	protected function _searchSlaveTags()
	{
		/*
		$masterOpeningTagStartPosition = strpos($this->_text, '['.$this->masterTag, $this->_position);
		$masterClosingTagStartPosition = strpos($this->_text, '[/'.$this->masterTag.'/]', $this->_position);
		
		if ($masterOpeningTagStartPosition !== false && $masterClosingTagStartPosition !== false)
		{
			$masterOpeningTagContentEndPosition = strpos($this->_text, ']', $masterOpeningTagStartPosition);
			
			if($masterOpeningTagContentEndPosition !== false)
			{
				$this->_position = $masterClosingTagStartPosition;
				return false;
			}
		}

			NOT GOOD
		*/

		$tagStartPosition = strpos($this->_text, '{', $this->_position);
		if ($tagStartPosition === false)
		{
			return false;
		}

		$tagContentEndPosition = strpos($this->_text, '}', $tagStartPosition);
		if ($tagContentEndPosition === false)
		{
			return false;
		}

		$tagContentStartPosition = $tagStartPosition + 1;
		$tagEndPosition = $tagContentEndPosition + 1;

		$isClosingTag = ($this->_text[$tagContentStartPosition] == '/') ? true : false;

		if(!$isClosingTag)
		{
			$tagOptionPosition = strpos($this->_text, '=', $tagContentStartPosition);
			$tagOption = ($tagOptionPosition === false) ? false : true;
			
			if($tagOption)
			{
				$tagName = substr($this->_text, $tagContentStartPosition, ($tagOptionPosition-1) - $tagContentStartPosition);
				$tagOption = substr($this->_text, $tagOptionPosition+1, $tagContentEndPosition - ($tagContentStartPosition+1));
			}
			else
			{
				$tagName = substr($this->_text, $tagContentStartPosition, $tagContentEndPosition - $tagContentStartPosition);
			}
		}
		else
		{
				$tagName = substr($this->_text, $tagContentStartPosition+1, $tagContentEndPosition - ($tagContentStartPosition+1));
				$tagOption = false;	
		}

		if(empty($this->_slaveTags[$tagName]))
		{
			$this->_position++;
			return true;
		}

		$length = $tagEndPosition - $tagStartPosition;

		$tagInfo = array(
			'tagName' => $tagName,
			'tagOption' => $tagOption,
			'isMasterTag' => false
		);
		
		if($isClosingTag)
		{
			$tagInfo['closingTag'] = array(
				'tagText'		=> substr($this->_text, $tagStartPosition, $length),
				'closingTagStart' 	=> $tagStartPosition,
				'closingTagLength'	=> $length,
				'tagContentEnd' 	=> $tagStartPosition
			);
		}
		else
		{
			$tagInfo['openingTag'] = array(
				'tagText' 		=> substr($this->_text, $tagStartPosition, $length),
				'openingTagStart' 	=> $tagStartPosition,
				'openingTagLength'	=> $length,		
				'tagContentStart' 	=> $tagEndPosition
			);		
		}

		$success = $this->_pushTags($tagInfo, $isClosingTag);

		if($success)
		{
			$this->_position = $tagEndPosition;
		}
		else
		{
			$this->_position++;		
		}
		
		return true;
	}

	protected function _pushTags(array $tagInfo, $isClosingTag)
	{
		$tagName = $tagInfo['tagName'];
		$isOpeningTag = !$isClosingTag;

		if($isOpeningTag)
		{
			$this->_tagsDepth++; //Important: Increase depth
			$this->_tagsTree[$this->_tagsDepth][] = $tagInfo;
			
			return true;
		}
		
		if($isClosingTag)
		{
			/*The expected closing tag is the last opening tag*/
			$expectedTag = end($this->_tagsTree[$this->_tagsDepth]);
			reset($this->_tagsTree[$this->_tagsDepth]);
			$closingTagKey = count($this->_tagsTree[$this->_tagsDepth])-1;
			
			if(empty($expectedTag['tagName']))
			{
				return false;
			}

			if($tagName == $expectedTag['tagName'])
			{
				$currentDepth = $this->_tagsDepth;
				
				/*Tag Info*/	
				$allTagInfo = $expectedTag;
				$allTagInfo['closingTag'] = $tagInfo['closingTag'];
				$allTagInfo['depth'] = $currentDepth;
				
				/*Tag Content*/				
				$contentStart = $allTagInfo['openingTag']['tagContentStart'];
				$contentEnd = $allTagInfo['closingTag']['tagContentEnd'];
				$contentLength = $contentEnd-$contentStart;
				$allTagInfo['content'] = substr($this->_text, $contentStart, $contentLength);

				/*Important: decrease depth*/
				$this->_tagsDepth--;

				/*Parent Tag*/
				$parentTagInfo = end($this->_tagsTree[$this->_tagsDepth]);
				reset($this->_tagsTree[$this->_tagsDepth]);
				$allTagInfo['parentTag'] = $parentTagInfo;
	
				$this->_tagsList[] = $allTagInfo;
				
				if(!empty($this->_tagsTree[$currentDepth][$closingTagKey]))
				{
					$this->_tagsTree[$currentDepth][$closingTagKey] = $allTagInfo;
				}

				return true;
			}
		}

		return false;
	}

	//substr_replace	
}