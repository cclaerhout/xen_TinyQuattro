<?php
class Sedo_TinyQuattro_Helper_WysiwygHtml extends Sedo_TinyQuattro_Helper_MiniParser
{
	//For reference
	protected $_blockTags = array(
		'address', 'article', 'aside', 'audio', 'blockquote',
		'canvas', 'dd', 'div', 'dl', 'dt', 'fieldset', 'figcaption',
		'figure', 'footer', 'form', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
		'header', 'hgroup', 'hr', 'li', 'nav', 'ol', 'output', 'p', 'pre',
		'section', 'table', 'tbody', 'tfoot', 'thead', 'tr', 'ul', 'video'
	);

	protected $_voidTags = array(
		'area', 'base', 'br', 'col', 'command', 'embed', 'hr', 'img', 'input', 'keygen', 'link', 'meta', 'param', 'source', 'wbr'
	);

	protected static $_compatibleParentTags = array(
		'li','bloquote','td','th','dt','dd', 'div'
	);

	//Class caller
	public static function create($htmlOutput)
	{
		$compatibleParents = self::$_compatibleParentTags;

		$targetedHtmlTags = array(
			'font' => array(),
			/***
			* A p html tag can't be nested in another p html tag
			* [SIZE=3][INDENT]a
			* b
			* c[/INDENT][/SIZE]
			* => ok
			*
			* [INDENT=1][size=3]a
			* b
			* c[/size][/INDENT]
			* =>ok
			* 
			* [QUOTE][indent=1]test 1
			* test 2[/indent][/QUOTE]
			* => OK | TO DO: test with disable wysiwyg
			* 
			* [indent][center]test 1
			* test 2[/center][/indent]
			* => OK
			* 
			* [indent=1]a[center]test 1
			* test 2[/center]a[/indent]
			* =>not ok
			* 
			* [indent=1][center]test 1
			* test 2[/center]a[/indent]
			* => not ok
			* 
			**/
			'p' => array(
				'forbiddenParents_all' => array('p'),
				'forbiddenParents_exceptions' => $compatibleParents,
				'fixerMode' => array('mergeOptionWithParent', 'transparentTag')
			),
			/***
			* A blockquote html tag can't be nested in a p html tag
			* Html example:
			* <p>aaaa<blockquote><p>test 1</p></blockquote>bbbbbbb</p>
			* <p>aaaa</p>
			* <blockquote>
			* <p>test 1</p>
			* </blockquote>
			* <p>bbbbb</p>
			**/			
			'blockquote' => array(
				'forbiddenParents_all' => array('p'),
				'forbiddenParents_exceptions' => $compatibleParents,
				'fixerMode' => array('todo')
			),
			/***
			* The purpose of the table fix is to avoid to break when a text has been written from the Bb Code editor:
			* [SIZE=3][INDENT][xtable=skin4]
			* {tbody}
			* {tr}
			* {td}[[COLOR=transparent][B]x[/B][/COLOR]] {/td}
			* {/tr}
			* {/tbody}
			* [/xtable][/INDENT][/SIZE]
			*
			* => a table can't be nested in a p tag.
			* => the indent can't be managed with the css padding but with the margin property
			*    Warning: once back in the wysiwyg mode, the indent can't be managed with the RTE.
			*/
			'table' => array(
				'forbiddenParents_all' => array('p'),
				'forbiddenParents_exceptions' => $compatibleParents,
				'fixerMode' => array('moveToPrevParent', 'maxForbiddenParent'),
				'cssModification' => array('paddingToMargin')
			) 			
	 	);

		$parserOptions = array(
			'parserOpeningCharacter' => '<',
			'parserClosingCharacter' => '>',
			'htmlspecialcharsForContent' => false,
			'htmlspecialcharsForOptions' => false,
			'checkClosingTag' => true,
			'checkSelfClosingTag' => true,
			'mergeAdjacentTextNodes' => true,
			'nl2br' => false,
			'trimTextNodes' => false
		 );

//$htmlOutput = '<blockquote><p>test 1<p style="text-align:center">test 2</p></p></blockquote>'; //OK

//$htmlOutput = '<p>test 1<p>test2</p>test3</p>'; //OK

//$htmlOutput = '<p><blockquote><p>test 1</p></blockquote></p>'; // OK
//$htmlOutput = '<p>aaaa<blockquote><p>test 1</p></blockquote></p>'; // OK

/*
[B][COLOR=#0000ff][LEFT]test here[/LEFT][/COLOR][/B]


[QUOTE][indent=1]test 1
test 2[/indent][/QUOTE]
=> OK | TO DO: test with disable wysiwyg

[indent][center]test 1
test 2[/center][/indent]
=> OK

[indent=1]a[center]test 1
test 2[/center]a[/indent]
=>not ok

[indent=1][center]test 1
test 2[/center]a[/indent]
=> not ok


<p>aaaa<blockquote><p>test 1</p></blockquote>bbbbbbb</p>
<p>aaaa</p>
<blockquote>
<p>test 1</p>
</blockquote>
<p>bbbbb</p>

[SIZE=3][INDENT]a
b
c[/INDENT][/SIZE]
=> ok

[INDENT=1][size=3]a
b
c[/size][/INDENT]
=>ok

[SIZE=3][INDENT][xtable=skin4]
{tbody}
{tr}
{td}[[COLOR=transparent][B]x[/B][/COLOR]] {/td}
{/tr}
{/tbody}
[/xtable][/INDENT][/SIZE]

[color]
*/
		$miniParser = new Sedo_TinyQuattro_Helper_WysiwygHtml($htmlOutput, $targetedHtmlTags, array(), $parserOptions);

		$tree = $miniParser->getTree();
		$miniParser->wysiwygHtmlAnalyzer($tree);
		$miniParser->setTree($tree);
		$fixedOutput = $miniParser->render();
		
		var_dump("before:$htmlOutput");
		var_dump("after:$fixedOutput");

		return $fixedOutput;
		/*
		var_dump("before:$htmlOutput");
		var_dump("after:$fixedOutput");
		var_dump($miniParser->getTree());
		*/
	}

	//Modify the tree if needed
	protected $_wysiwygTagMap = array();
	
	public function getWysiwygTagDataById($tagId)
	{
		if(isset($this->_wysiwygTagMap[$tagId]))
		{
			return $this->_wysiwygTagMap[$tagId];
		}
		
		return null;
	}
	
	public function wysiwygHtmlAnalyzer(&$tree, &$tasks = array(), $preventRecursive = false)
	{
		$tree = array_values($tree);
		
		for($n=0; ; $n++)
		{
			if(!isset($tree[$n])) break;
			$data = &$tree[$n];

			$nextData = (isset($tree[$n+1])) ? $tree[$n+1] : null;
			$nextDataSkipContent = false;

			if($nextData && $this->wysiwygHasBlankChildren($nextData))
			{
				$nextDataSkipContent = true;
			}

			if(is_string($data)) continue;	

			$data['children'] = array_values($data['children']);

			$tagName = $data['tag'];
			$tagId = $data['tagId'];
			$parentTagId = $data['parentTagId'];
			$option = $data['option'];
			$children = (isset($data['children'])) ? $data['children'] : array();
			$depth = $data['depth'];

			$this->_wysiwygTagMap[$tagId] = &$data;
			$parentData = $this->getWysiwygTagDataById($parentTagId);

			list($parentTags, $parentTagsId) = $this->wysiwygGetParentTagsData($tagName, $tagId, $parentTagId);

			$tagRules = $this->getTagRules($tagName);
			
			/*This part will be used by children*/
			if(isset($tagRules['forbiddenParents_all']) && array_intersect($tagRules['forbiddenParents_all'], $parentTags))
			{
				$isValidHtml = true;
				$targetParentId = null;
				$targetParentName = null;
				$lastParentId = null;
				$forbiddenParentsByKey = array_flip($tagRules['forbiddenParents_all']);
				$forbiddenParentsExceptionsByKey = array();
				$maxForbiddenParent = in_array('maxForbiddenParent', $tagRules['fixerMode']);
			
				if(isset($tagRules['forbiddenParents_exceptions']))
				{
					$forbiddenParentsExceptionsByKey = array_flip($tagRules['forbiddenParents_exceptions']);	
				}

				do{
/*
					if($lastParentId == null)
					{
						$prevChildId = $tagId;
					}
					else
					{
						$prevChildId = $lastParentId;
					}
*/					
					$lastParent = array_pop($parentTags);
					$lastParentId = array_pop($parentTagsId);

/*
					if(isset($this->_wysiwygTagMap[$prevChildId], $this->_wysiwygTagMap[$lastParentId]))
					{
						//Conditional should not be needed
						$prevChildData = $this->_wysiwygTagMap[$prevChildId];
						$lastParentData = $this->_wysiwygTagMap[$lastParentId];
						$contextIt = new Sedo_TinyQuattro_Helper_MiniIterator($lastParentData['children'], $prevChildData);
						
						if($contextIt->prevString())
						{
							break;						
						}
					}
*/
					
					if(isset($forbiddenParentsByKey[$lastParent]))
					{
						$isValidHtml = false;
						$targetParentId = $lastParentId;
						$targetParentName = $this->_wysiwygTagNameByTagId[$lastParentId];
						
						if(!$maxForbiddenParent){
							break;
						}
					}
					
					if(isset($forbiddenParentsExceptionsByKey[$lastParent])){
						break;
					}
					
				}while(!empty($parentTags));

				if(!$isValidHtml && !empty($tagRules['fixerMode']))
				{
					if(!empty($option) && in_array('mergeOptionWithParent', $tagRules['fixerMode']))
					{
						//Children to parent
						$tasks[$targetParentId]['mergeStyleOption'][] = $option;
					}

					if(in_array('transparentTag', $tagRules['fixerMode']))
					{
						$data['transparentTag'] = true;
						$contextChildren = $parentData['children'];
						$contextIt = new Sedo_TinyQuattro_Helper_MiniIterator($contextChildren, $data);
						
						if($contextIt->treeHasString())
						{
							$tasks[$targetParentId]['splitParentTagAtChildrenStrings'] = true;
						}

						if($parentData)
						{
							if($contextIt->key() > 0)
							{
								//Since the node has a parent and is not the first (index = 0) child
								$contextIt->prev();
								$prevNodeData = $contextIt->current();
								$prevNodeHasLineBreak = $contextIt->isCurrentBlankStringWithCarriageReturn();
								$contextIt->rewind();

								//Only allow prevBreak if previous sibling has not nextBreak and no real carriage return
								if(empty($prevNodeData['nextBreak']) && !$prevNodeHasLineBreak)
								{
									$data['prevBreak'] = true;
									//var_dump("prevBreak:$tagId");
								}
							}
						}

						if($nextData && !$nextDataSkipContent)
						{
							$data['nextBreak'] = true;
							//var_dump("nextBreak:$tagId");
						}		
					}

					if(in_array('prevBreak', $tagRules['fixerMode']))
					{
						$data['prevBreak'] = true;
					}
					
					if(in_array('nextBreak', $tagRules['fixerMode']))
					{
						$data['nextBreak'] = true;					
					}					

					if(in_array('moveToPrevParent', $tagRules['fixerMode']))
					{
						$tasks[$targetParentId]['moveToPrevParent'][] = $data;
						unset($tree[$n]);
						$tree = array_values($tree);
						$n--;
						continue;				
					}
				}
			}

			//Recursive starts here: children are processed
			if($children != null && !$preventRecursive)
			{
				$this->wysiwygHtmlAnalyzer($data['children'], $tasks);
			}
			
			/*This part will be used by the designated parent tag*/
			if(isset($tasks[$tagId]))
			{
				$wipTasks = $tasks[$tagId];

				if(isset($wipTasks['mergeStyleOption']))
				{
					$styleAttributesToMerge = array();
					
					foreach($wipTasks['mergeStyleOption'] as $elements)
					{
						//each element processed is going step by step to the parent
						$styleAttributes = $this->getHtmlStyleOption($elements);
						//parents attributes have the priority to merge current ($styleAttributes) to previous ($styleAttributesToMerge)
						$styleAttributesToMerge = array_merge($styleAttributes, $styleAttributesToMerge);
					}

					if(!isset($option['style']))
					{
						$option['style'] = array();
					}

					$option['style'] = array_merge($option['style'], $styleAttributesToMerge);

					//Save
					$data['option']['style'] = $option['style'];
					
					//Even if the tag has no valid closing force its rendering
					$this->_makeClosingTagValid($tagId);
					
					unset($tasks[$tagId]['mergeStyleOption']);
				}
				
				if(isset($wipTasks['moveToPrevParent']))
				{
					$arraysToAdd = count($wipTasks['moveToPrevParent']);
					$toMove = $wipTasks['moveToPrevParent'];

					$styleAttributes = $this->getHtmlStyleOption($option);
					
					for($i=0; isset($toMove[$i]); $i++)
					{
						$currentData = &$toMove[$i];
						$currentData['parentTagId'] = $parentTagId;
						$currentData['depth'] = $depth+1; //TO DO: need to update depth for children
						
						$currentStyle = $this->getHtmlStyleOption($currentData);
						$currentData['option']['style'] = array_merge($option['style'], $currentStyle);
						
						if($currentData['children'] != null && !$preventRecursive)
						{
							$this->wysiwygHtmlAnalyzer($currentData['children'], $tasks);
						}
					}
					
					array_splice($tree, $n, 0, $toMove);
					$n = $n+$arraysToAdd;

					unset($tasks[$tagId]['moveToPrevParent']);
				}
				
				if(isset($wipTasks['splitParentTagAtChildrenStrings2']) && !empty($data['children']))
				{
					$children = $data['children']; //already modified
					$modelTag = $data;
					$modelTag['children'] = array();
					$wipChildren = array();
					
					for($i=0; ; ++$i)
					{
						if(!isset($children[$i])) break;
						$child = $children[$i];

						$wipData = $modelTag;
						$wipData['children'][] = $child;
						$wipData['tagId'] .= "_$i";
						
						$wipChildren[] = $wipData;
					}
					
					$data['transparentTag'] = true;
					$data['children'] = $wipChildren;

					unset($tasks[$tagId]['splitParentTagAtChildrenStrings']);	
				}
			}
		}
	}

	public function wysiwygHasBlankChildren($tag)
	{
		if(isset($tag['children']) && count($tag['children']) == 1 && is_string($tag['children']))
		{
			$dataCheck = preg_replace('#</?[^>]+?>#', '', $tag['children'][0]);// to improve if needed
			if(trim($dataCheck) == '')
			{
				return true;
			}
		}
		return false;
	}

	protected $_wysiwygParentsMap = array();
	protected $_wysiwygTagNameByTagId = array();
	protected $_wysiwygParentTagNamesByTagId = array();

	public function wysiwygGetParentTagsData($tagName, $tagId, $parentTagId)
	{
		$fallback = array(array(), array());

		$this->_wysiwygTagNameByTagId[$tagId] = $tagName;

		if(!$parentTagId)
		{
			$this->_wysiwygParentsMap[$tagId] = null;
			return $fallback;
		}

		$this->_wysiwygParentsMap[$tagId] = $parentTagId;
		
		$parentTags = array();
		$parentTagsId = array();

		for(	$wipParentTagId = $parentTagId;
			array_key_exists($wipParentTagId, $this->_wysiwygParentsMap);
			$wipParentTagId = $this->_wysiwygParentsMap[$wipParentTagId]
		)
		{
			$parentTagName = $this->_wysiwygTagNameByTagId[$wipParentTagId];
			array_unshift($parentTags, $parentTagName);
			array_unshift($parentTagsId, $wipParentTagId);
		}
		
		$this->_wysiwygParentTagNamesTagById[$tagId] = $parentTags;

		return array($parentTags, $parentTagsId);
	}


	/**
	 * Formatter - unfold tree and return text with formatted tags
	 */

	//@extended - needed to enable fixerValidTag
	protected $_fixerMode = true; 
	
	//@extended
	public function fixerValidTag(array $tagRules, array $tag, array &$rendererStates)
	{
		/*Get data*/
		$tagName = $tag['tag'];
		$tagId = $tag['tagId'];
		
		$flattenOption = $this->flattenHtmlTagOption($tag['option']);

		if(isset($tagRules['cssModification']))
		{
			$cssRules = $tagRules['cssModification'];
			if(in_array('paddingToMargin', $cssRules))
			{
				$flattenOption = str_replace('padding', 'margin', $flattenOption);
			}
		}
		
		$option = $this->filterString($flattenOption, $rendererStates);

		if(!empty($tag['transparentTag']))
		{
			$prepend = '';
			$append = '';
			$option = null;
		}
		else
		{
			$prepend = ($option) ? "<{$tagName} {$option}>" : "<{$tagName}>";
			$append = "</{$tagName}>";
		}

		if(!empty($tag['prevBreak']))
		{
			$prepend = '<br />'.$prepend;
		}

		if(!empty($tag['nextBreak']))
		{
			$append .= '<br />';
		}

		$text = $prepend . $this->renderSubTree($tag['children'], $rendererStates) . $append;

		return $text;
	}
}
//Zend_Debug::dump($bbmSmilies);