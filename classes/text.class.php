<?
class Text {
	/**
	 * Array of valid tags; tag => max number of attributes
	 * @var array $ValidTags
	 */
	private static $ValidTags = array('b'=>0, 'u'=>0, 'i'=>0, 's'=>0, '*'=>0, '#'=>0, 'artist'=>0, 'user'=>0, 'n'=>0, 'inlineurl'=>0, 'inlinesize'=>1, 'headline'=>1, 'align'=>1, 'color'=>1, 'colour'=>1, 'size'=>1, 'url'=>1, 'img'=>1, 'quote'=>1, 'pre'=>1, 'code'=>1, 'tex'=>0, 'hide'=>1, 'spoiler' => 1, 'plain'=>0, 'important'=>0, 'torrent'=>0, 'rule'=>0, 'mature'=>1,
	);

	/**
	 * Array of smilies; code => image file in STATIC_SERVER/common/smileys
	 * @var array $Smileys
	 */
	private static $Smileys = array(
		':angry:'			=> 'angry.gif',
		':-D'				=> 'biggrin.gif',
		':D'				=> 'biggrin.gif',
		':|'				=> 'blank.gif',
		':-|'				=> 'blank.gif',
		':blush:'			=> 'blush.gif',
		':cool:'			=> 'cool.gif',
		':&#39;('			=> 'crying.gif',
		':crying:'			=> 'crying.gif',
		'&gt;.&gt;'			=> 'eyesright.gif',
		':frown:'			=> 'frown.gif',
		'&lt;3'				=> 'heart.gif',
		':unsure:'			=> 'hmm.gif',
		//':\\'				=> 'hmm.gif',
		':whatlove:'		=> 'ilu.gif',
		':lol:'				=> 'laughing.gif',
		':loveflac:'		=> 'loveflac.gif',
		':flaclove:'		=> 'loveflac.gif',
		':ninja:'			=> 'ninja.gif',
		':no:'				=> 'no.gif',
		':nod:'				=> 'nod.gif',
		':ohno:'			=> 'ohnoes.gif',
		':ohnoes:'			=> 'ohnoes.gif',
		':omg:'				=> 'omg.gif',
		':o'				=> 'ohshit.gif',
		':O'				=> 'ohshit.gif',
		':paddle:'			=> 'paddle.gif',
		':('				=> 'sad.gif',
		':-('				=> 'sad.gif',
		':shifty:'			=> 'shifty.gif',
		':sick:'			=> 'sick.gif',
		':)'				=> 'smile.gif',
		':-)'				=> 'smile.gif',
		':sorry:'			=> 'sorry.gif',
		':thanks:'			=> 'thanks.gif',
		':P'				=> 'tongue.gif',
		':p'				=> 'tongue.gif',
		':-P'				=> 'tongue.gif',
		':-p'				=> 'tongue.gif',
		':wave:'			=> 'wave.gif',
		';-)'				=> 'wink.gif',
		':wink:'			=> 'wink.gif',
		':creepy:'			=> 'creepy.gif',
		':worried:'			=> 'worried.gif',
		':wtf:'				=> 'wtf.gif',
		':wub:'				=> 'wub.gif',
	);

	/**
	 * Processed version of the $Smileys array, see {@link smileys}
	 * @var array $ProcessedSmileys
	 */
	private static $ProcessedSmileys = array();

	/**
	 * Whether or not to turn images into URLs (used inside [quote] tags).
	 * This is an integer reflecting the number of levels we're doing that
	 * transition, i.e. images will only be displayed as images if $NoImg <= 0.
	 * By setting this variable to a negative number you can delay the
	 * transition to a deeper level of quotes.
	 * @var int $NoImg
	 */
	private static $NoImg = 0;

	/**
	 * Internal counter for the level of recursion in to_html
	 * @var int $Levels
	 */
	private static $Levels = 0;

	/**
	 * The maximum amount of nesting allowed (exclusive)
	 * In reality n-1 nests are shown.
	 * @var int $MaximumNests
	 */
	private static $MaximumNests = 10;

	/**
	 * Used to detect and disable parsing (e.g. TOC) within quotes
	 * @var int $InQuotes
	 */
	private static $InQuotes = 0;

	/**
	 * Used to [hide] quote trains starting with the specified depth (inclusive)
	 * @var int $NestsBeforeHide
	 *
	 * This defaulted to 5 but was raised to 10 to effectively "disable" it until
	 * an optimal number of nested [quote] tags is chosen. The variable $MaximumNests
	 * effectively overrides this variable, if $MaximumNests is less than the value
	 * of $NestsBeforeHide.
	 */
	private static $NestsBeforeHide = 10;

	/**
	 * Array of headlines for Table Of Contents (TOC)
	 * @var array $HeadLines
	 */
	private static $Headlines;

	/**
	 * Counter for making headline URLs unique
	 * @var int $HeadLines
	 */
	private static $HeadlineID = 0;

	/**
	 * Depth
	 * @var array $HeadlineLevels
	 */
	private static $HeadlineLevels = array('1', '2', '3', '4');

	/**
	 * TOC enabler
	 * @var bool $TOC
	 */
	public static $TOC = false;

	/**
	 * Output BBCode as XHTML
	 * @param string $Str BBCode text
	 * @param bool $OutputTOC Ouput TOC near (above) text
	 * @param int $Min See {@link parse_toc}
	 * @return string
	 */
	public static function full_format($Str, $OutputTOC = true, $Min = 3) {
		global $Debug;
		$Debug->set_flag('BBCode start');
		$Str = display_str($Str);
		self::$Headlines = array();

		//Inline links
		$URLPrefix = '(\[url\]|\[url\=|\[img\=|\[img\])';
		$Str = preg_replace('/'.$URLPrefix.'\s+/i', '$1', $Str);
		$Str = preg_replace('/(?<!'.$URLPrefix.')http(s)?:\/\//i', '$1[inlineurl]http$2://', $Str);
		// For anonym.to and archive.org links, remove any [inlineurl] in the middle of the link
		$callback = create_function('$matches', 'return str_replace("[inlineurl]", "", $matches[0]);');
		$Str = preg_replace_callback('/(?<=\[inlineurl\]|'.$URLPrefix.')(\S*\[inlineurl\]\S*)/m', $callback, $Str);

		if (self::$TOC) {
			$Str = preg_replace('/(\={5})([^=].*)\1/i', '[headline=4]$2[/headline]', $Str);
			$Str = preg_replace('/(\={4})([^=].*)\1/i', '[headline=3]$2[/headline]', $Str);
			$Str = preg_replace('/(\={3})([^=].*)\1/i', '[headline=2]$2[/headline]', $Str);
			$Str = preg_replace('/(\={2})([^=].*)\1/i', '[headline=1]$2[/headline]', $Str);
		} else {
			$Str = preg_replace('/(\={4})([^=].*)\1/i', '[inlinesize=3]$2[/inlinesize]', $Str);
			$Str = preg_replace('/(\={3})([^=].*)\1/i', '[inlinesize=5]$2[/inlinesize]', $Str);
			$Str = preg_replace('/(\={2})([^=].*)\1/i', '[inlinesize=7]$2[/inlinesize]', $Str);
		}

		$HTML = nl2br(self::to_html(self::parse($Str)));

		if (self::$TOC && $OutputTOC) {
			$HTML = self::parse_toc($Min) . $HTML;
		}

		$Debug->set_flag('BBCode end');
		return $HTML;
	}

	public static function strip_bbcode($Str) {
		$Str = display_str($Str);

		//Inline links
		$Str = preg_replace('/(?<!(\[url\]|\[url\=|\[img\=|\[img\]))http(s)?:\/\//i', '$1[inlineurl]http$2://', $Str);

		return nl2br(self::raw_text(self::parse($Str)));
	}


	private static function valid_url($Str, $Extension = '', $Inline = false) {
		$Regex = '/^';
		$Regex .= '(https?|ftps?|irc):\/\/'; // protocol
		$Regex .= '(\w+(:\w+)?@)?'; // user:pass@
		$Regex .= '(';
		$Regex .= '(([0-9]{1,3}\.){3}[0-9]{1,3})|'; // IP or...
		$Regex .= '(([a-z0-9\-\_]+\.)+\w{2,6})'; // sub.sub.sub.host.com
		$Regex .= ')';
		$Regex .= '(:[0-9]{1,5})?'; // port
		$Regex .= '\/?'; // slash?
		$Regex .= '(\/?[0-9a-z\-_.,&=@~%\/:;()+|!#]+)*'; // /file
		if (!empty($Extension)) {
			$Regex.=$Extension;
		}

		// query string
		if ($Inline) {
			$Regex .= '(\?([0-9a-z\-_.,%\/\@~&=:;()+*\^$!#|?]|\[\d*\])*)?';
		} else {
			$Regex .= '(\?[0-9a-z\-_.,%\/\@[\]~&=:;()+*\^$!#|?]*)?';
		}

		$Regex .= '(#[a-z0-9\-_.,%\/\@[\]~&=:;()+*\^$!]*)?'; // #anchor
		$Regex .= '$/i';

		return preg_match($Regex, $Str, $Matches);
	}

	public static function local_url($Str) {
		$URLInfo = parse_url($Str);
		if (!$URLInfo) {
			return false;
		}
		$Host = $URLInfo['host'];
		// If for some reason your site does not require subdomains or contains a directory in the SITE_URL, revert to the line below.
		//if ($Host == NONSSL_SITE_URL || $Host == SSL_SITE_URL || $Host == 'www.'.NONSSL_SITE_URL) {
		if (empty($URLInfo['port']) && preg_match('/(\S+\.)*'.NONSSL_SITE_URL.'/', $Host)) {
			$URL = '';
			if (!empty($URLInfo['path'])) {
				$URL .= ltrim($URLInfo['path'], '/'); // Things break if the path starts with '//'
			}
			if (!empty($URLInfo['query'])) {
				$URL .= "?$URLInfo[query]";
			}
			if (!empty($URLInfo['fragment'])) {
				$URL .= "#$URLInfo[fragment]";
			}
			return $URL ? "/$URL" : false;
		} else {
			return false;
		}

	}


	/*
	How parsing works

	Parsing takes $Str, breaks it into blocks, and builds it into $Array.
	Blocks start at the beginning of $Str, when the parser encounters a [, and after a tag has been closed.
	This is all done in a loop.

	EXPLANATION OF PARSER LOGIC

	1) Find the next tag (regex)
		1a) If there aren't any tags left, write everything remaining to a block and return (done parsing)
		1b) If the next tag isn't where the pointer is, write everything up to there to a text block.
	2) See if it's a [[wiki-link]] or an ordinary tag, and get the tag name
	3) If it's not a wiki link:
		3a) check it against the self::$ValidTags array to see if it's actually a tag and not [bullshit]
			If it's [not a tag], just leave it as plaintext and move on
		3b) Get the attribute, if it exists [name=attribute]
	4) Move the pointer past the end of the tag
	5) Find out where the tag closes (beginning of [/tag])
		5a) Different for different types of tag. Some tags don't close, others are weird like [*]
		5b) If it's a normal tag, it may have versions of itself nested inside - e.g.:
			[quote=bob]*
				[quote=joe]I am a redneck!**[/quote]
				Me too!
			***[/quote]
		If we're at the position *, the first [/quote] tag is denoted by **.
		However, our quote tag doesn't actually close there. We must perform
		a loop which checks the number of opening [quote] tags, and make sure
		they are all closed before we find our final [/quote] tag (***).

		5c) Get the contents between [open] and [/close] and call it the block.
		In many cases, this will be parsed itself later on, in a new parse() call.
		5d) Move the pointer past the end of the [/close] tag.
	6) Depending on what type of tag we're dealing with, create an array with the attribute and block.
		In many cases, the block may be parsed here itself. Stick them in the $Array.
	7) Increment array pointer, start again (past the end of the [/close] tag)

	*/
	private static function parse($Str) {
		$i = 0; // Pointer to keep track of where we are in $Str
		$Len = strlen($Str);
		$Array = array();
		$ArrayPos = 0;
		$StrLC = strtolower($Str);

		while ($i < $Len) {
			$Block = '';

			// 1) Find the next tag (regex)
			// [name(=attribute)?]|[[wiki-link]]
			$IsTag = preg_match("/((\[[a-zA-Z*#]+)(=(?:[^\n'\"\[\]]|\[\d*\])+)?\])|(\[\[[^\n\"'\[\]]+\]\])/", $Str, $Tag, PREG_OFFSET_CAPTURE, $i);

			// 1a) If there aren't any tags left, write everything remaining to a block
			if (!$IsTag) {
				// No more tags
				$Array[$ArrayPos] = substr($Str, $i);
				break;
			}

			// 1b) If the next tag isn't where the pointer is, write everything up to there to a text block.
			$TagPos = $Tag[0][1];
			if ($TagPos > $i) {
				$Array[$ArrayPos] = substr($Str, $i, $TagPos - $i);
				++$ArrayPos;
				$i = $TagPos;
			}

			// 2) See if it's a [[wiki-link]] or an ordinary tag, and get the tag name
			if (!empty($Tag[4][0])) { // Wiki-link
				$WikiLink = true;
				$TagName = substr($Tag[4][0], 2, -2);
				$Attrib = '';
			} else { // 3) If it's not a wiki link:
				$WikiLink = false;
				$TagName = strtolower(substr($Tag[2][0], 1));

				//3a) check it against the self::$ValidTags array to see if it's actually a tag and not [bullshit]
				if (!isset(self::$ValidTags[$TagName])) {
					$Array[$ArrayPos] = substr($Str, $i, ($TagPos - $i) + strlen($Tag[0][0]));
					$i = $TagPos + strlen($Tag[0][0]);
					++$ArrayPos;
					continue;
				}

				$MaxAttribs = self::$ValidTags[$TagName];

				// 3b) Get the attribute, if it exists [name=attribute]
				if (!empty($Tag[3][0])) {
					$Attrib = substr($Tag[3][0], 1);
				} else {
					$Attrib = '';
				}
			}

			// 4) Move the pointer past the end of the tag
			$i = $TagPos + strlen($Tag[0][0]);

			// 5) Find out where the tag closes (beginning of [/tag])

			// Unfortunately, BBCode doesn't have nice standards like XHTML
			// [*], [img=...], and http:// follow different formats
			// Thus, we have to handle these before we handle the majority of tags


			//5a) Different for different types of tag. Some tags don't close, others are weird like [*]
			if ($TagName == 'img' && !empty($Tag[3][0])) { //[img=...]
				$Block = ''; // Nothing inside this tag
				// Don't need to touch $i
			} elseif ($TagName == 'inlineurl') { // We did a big replace early on to turn http:// into [inlineurl]http://

				// Let's say the block can stop at a newline or a space
				$CloseTag = strcspn($Str, " \n\r", $i);
				if ($CloseTag === false) { // block finishes with URL
					$CloseTag = $Len;
				}
				if (preg_match('/[!,.?:]+$/',substr($Str, $i, $CloseTag), $Match)) {
					$CloseTag -= strlen($Match[0]);
				}
				$URL = substr($Str, $i, $CloseTag);
				if (substr($URL, -1) == ')' && substr_count($URL, '(') < substr_count($URL, ')')) {
					$CloseTag--;
					$URL = substr($URL, 0, -1);
				}
				$Block = $URL; // Get the URL

				// strcspn returns the number of characters after the offset $i, not after the beginning of the string
				// Therefore, we use += instead of the = everywhere else
				$i += $CloseTag; // 5d) Move the pointer past the end of the [/close] tag.
			} elseif ($WikiLink == true || $TagName == 'n') {
				// Don't need to do anything - empty tag with no closing
			} elseif ($TagName === '*' || $TagName === '#') {
				// We're in a list. Find where it ends
				$NewLine = $i;
				do { // Look for \n[*]
					$NewLine = strpos($Str, "\n", $NewLine + 1);
				} while ($NewLine !== false && substr($Str, $NewLine + 1, 3) == "[$TagName]");

				$CloseTag = $NewLine;
				if ($CloseTag === false) { // block finishes with list
					$CloseTag = $Len;
				}
				$Block = substr($Str, $i, $CloseTag - $i); // Get the list
				$i = $CloseTag; // 5d) Move the pointer past the end of the [/close] tag.
			} else {
				//5b) If it's a normal tag, it may have versions of itself nested inside
				$CloseTag = $i - 1;
				$InTagPos = $i - 1;
				$NumInOpens = 0;
				$NumInCloses = -1;

				$InOpenRegex = '/\[('.$TagName.')';
				if ($MaxAttribs > 0) {
					$InOpenRegex .= "(=[^\n'\"\[\]]+)?";
				}
				$InOpenRegex .= '\]/i';


				// Every time we find an internal open tag of the same type, search for the next close tag
				// (as the first close tag won't do - it's been opened again)
				do {
					$CloseTag = strpos($StrLC, "[/$TagName]", $CloseTag + 1);
					if ($CloseTag === false) {
						$CloseTag = $Len;
						break;
					} else {
						$NumInCloses++; // Majority of cases
					}

					// Is there another open tag inside this one?
					$OpenTag = preg_match($InOpenRegex, $Str, $InTag, PREG_OFFSET_CAPTURE, $InTagPos + 1);
					if (!$OpenTag || $InTag[0][1] > $CloseTag) {
						break;
					} else {
						$InTagPos = $InTag[0][1];
						$NumInOpens++;
					}

				} while ($NumInOpens > $NumInCloses);


				// Find the internal block inside the tag
				$Block = substr($Str, $i, $CloseTag - $i); // 5c) Get the contents between [open] and [/close] and call it the block.

				$i = $CloseTag + strlen($TagName) + 3; // 5d) Move the pointer past the end of the [/close] tag.

			}

			// 6) Depending on what type of tag we're dealing with, create an array with the attribute and block.
			switch ($TagName) {
				case 'inlineurl':
					$Array[$ArrayPos] = array('Type'=>'inlineurl', 'Attr'=>$Block, 'Val'=>'');
					break;
				case 'url':
					$Array[$ArrayPos] = array('Type'=>'img', 'Attr'=>$Attrib, 'Val'=>$Block);
					if (empty($Attrib)) { // [url]http://...[/url] - always set URL to attribute
						$Array[$ArrayPos] = array('Type'=>'url', 'Attr'=>$Block, 'Val'=>'');
					} else {
						$Array[$ArrayPos] = array('Type'=>'url', 'Attr'=>$Attrib, 'Val'=>self::parse($Block));
					}
					break;
				case 'quote':
					$Array[$ArrayPos] = array('Type'=>'quote', 'Attr'=>self::parse($Attrib), 'Val'=>self::parse($Block));
					break;
				case 'img':
				case 'image':
					if (empty($Block)) {
						$Block = $Attrib;
					}
					$Array[$ArrayPos] = array('Type'=>'img', 'Val'=>$Block);
					break;
				case 'aud':
				case 'mp3':
				case 'audio':
					if (empty($Block)) {
						$Block = $Attrib;
					}
					$Array[$ArrayPos] = array('Type'=>'aud', 'Val'=>$Block);
					break;
				case 'user':
					$Array[$ArrayPos] = array('Type'=>'user', 'Val'=>$Block);
					break;
				case 'artist':
					$Array[$ArrayPos] = array('Type'=>'artist', 'Val'=>$Block);
					break;
				case 'torrent':
					$Array[$ArrayPos] = array('Type'=>'torrent', 'Val'=>$Block);
					break;
				case 'tex':
					$Array[$ArrayPos] = array('Type'=>'tex', 'Val'=>$Block);
					break;
				case 'rule':
					$Array[$ArrayPos] = array('Type'=>'rule', 'Val'=>$Block);
					break;
				case 'pre':
				case 'code':
				case 'plain':
					$Block = strtr($Block, array('[inlineurl]' => ''));

					$Callback = function ($matches) {
						$n = $matches[2];
						$text = '';
						if ($n < 5 && $n > 0) {
							$e = str_repeat('=', $matches[2] + 1);
							$text = $e . $matches[3] . $e;
						}
						return $text;
					};
					$Block = preg_replace_callback('/\[(headline)\=(\d)\](.*?)\[\/\1\]/i', $Callback, $Block);

					$Block = preg_replace('/\[inlinesize\=3\](.*?)\[\/inlinesize\]/i', '====$1====', $Block);
					$Block = preg_replace('/\[inlinesize\=5\](.*?)\[\/inlinesize\]/i', '===$1===', $Block);
					$Block = preg_replace('/\[inlinesize\=7\](.*?)\[\/inlinesize\]/i', '==$1==', $Block);


					$Array[$ArrayPos] = array('Type'=>$TagName, 'Val'=>$Block);
					break;
				case 'spoiler':
				case 'hide':
					$Array[$ArrayPos] = array('Type'=>'hide', 'Attr'=>$Attrib, 'Val'=>self::parse($Block));
					break;
				case 'mature':
					$Array[$ArrayPos] = array('Type'=>'mature', 'Attr'=>$Attrib, 'Val'=>self::parse($Block));
					break;
				case '#':
				case '*':
						$Array[$ArrayPos] = array('Type'=>'list');
						$Array[$ArrayPos]['Val'] = explode("[$TagName]", $Block);
						$Array[$ArrayPos]['ListType'] = $TagName === '*' ? 'ul' : 'ol';
						$Array[$ArrayPos]['Tag'] = $TagName;
						foreach ($Array[$ArrayPos]['Val'] as $Key=>$Val) {
							$Array[$ArrayPos]['Val'][$Key] = self::parse(trim($Val));
						}
					break;
				case 'n':
					$ArrayPos--;
					break; // n serves only to disrupt bbcode (backwards compatibility - use [pre])
				default:
					if ($WikiLink == true) {
						$Array[$ArrayPos] = array('Type'=>'wiki','Val'=>$TagName);
					} else {

						// Basic tags, like [b] or [size=5]

						$Array[$ArrayPos] = array('Type'=>$TagName, 'Val'=>self::parse($Block));
						if (!empty($Attrib) && $MaxAttribs > 0) {
							$Array[$ArrayPos]['Attr'] = strtolower($Attrib);
						}
					}
			}

			$ArrayPos++; // 7) Increment array pointer, start again (past the end of the [/close] tag)
		}
		return $Array;
	}

	/**
	 * Generates a navigation list for TOC
	 * @param int $Min Minimum number of headlines required for a TOC list
	 */
	public static function parse_toc ($Min = 3) {
		if (count(self::$Headlines) > $Min) {
			$list = '<ol class="navigation_list">';
			$i = 0;
			$level = 0;
			$off = 0;

			foreach (self::$Headlines as $t) {
				$n = (int)$t[0];
				if ($i === 0 && $n > 1) {
					$off = $n - $level;
				}
				self::headline_level($n, $level, $list, $i, $off);
				$list .= sprintf('<li><a href="#%2$s">%1$s</a>', $t[1], $t[2]);
				$level = $t[0];
				$off = 0;
				$i++;
			}

			$list .= str_repeat('</li></ol>', $level);
			$list .= "\n\n";
			return $list;
		}
	}

	/**
	 * Generates the list items and proper depth
	 *
	 * First check if the item should be higher than the current level
	 * - Close the list and previous lists
	 *
	 * Then check if the item should go lower than the current level
	 * - If the list doesn't open on level one, use the Offset
	 * - Open appropriate sub lists
	 *
	 * Otherwise the item is on the same as level as the previous item
	 *
	 * @param int $ItemLevel Current item level
	 * @param int $Level Current list level
	 * @param str $List reference to an XHTML string
	 * @param int $i Iterator digit
	 * @param int $Offset If the list doesn't start at level 1
	 */
	private static function headline_level (&$ItemLevel, &$Level, &$List, $i, &$Offset) {
		if ($ItemLevel < $Level) {
			$diff = $Level - $ItemLevel;
			$List .= '</li>' . str_repeat('</ol></li>', $diff);
		} elseif ($ItemLevel > $Level) {
			$diff = $ItemLevel - $Level;
			if ($Offset > 0) $List .= str_repeat('<li><ol>', $Offset - 2);

			if ($ItemLevel > 1) {
				$List .= $i === 0 ? '<li>' : '';
				$List .= "\n<ol>\n";
			}
		} else {
			$List .= $i > 0 ? '</li>' : '<li>';
		}
	}

	private static function to_html ($Array) {
		global $SSL;
		self::$Levels++;
		/*
		 * Hax prevention
		 * That's the original comment on this.
		 * Most likely this was implemented to avoid anyone nesting enough
		 * elements to reach PHP's memory limit as nested elements are
		 * solved recursively.
		 * Original value of 10, it is now replaced in favor of
		 * $MaximumNests.
		 * If this line is ever executed then something is, infact
		 * being haxed as the if before the block type switch for different
		 * tags should always be limiting ahead of this line.
		 * (Larger than vs. smaller than.)
		 */
		if (self::$Levels > self::$MaximumNests) {
			return $Block['Val']; // Hax prevention, breaks upon exceeding nests.
		}
		$Str = '';
		foreach ($Array as $Block) {
			if (is_string($Block)) {
				$Str .= self::smileys($Block);
				continue;
			}
			if (self::$Levels < self::$MaximumNests) {
			switch ($Block['Type']) {
				case 'b':
					$Str .= '<strong>'.self::to_html($Block['Val']).'</strong>';
					break;
				case 'u':
					$Str .= '<span style="text-decoration: underline;">'.self::to_html($Block['Val']).'</span>';
					break;
				case 'i':
					$Str .= '<span style="font-style: italic;">'.self::to_html($Block['Val'])."</span>";
					break;
				case 's':
					$Str .= '<span style="text-decoration: line-through;">'.self::to_html($Block['Val']).'</span>';
					break;
				case 'important':
					$Str .= '<strong class="important_text">'.self::to_html($Block['Val']).'</strong>';
					break;
				case 'user':
					$Str .= '<a href="user.php?action=search&amp;search='.urlencode($Block['Val']).'">'.$Block['Val'].'</a>';
					break;
				case 'artist':
					$Str .= '<a href="artist.php?artistname='.urlencode(Format::undisplay_str($Block['Val'])).'">'.$Block['Val'].'</a>';
					break;
				case 'rule':
					$Rule = trim(strtolower($Block['Val']));
					if ($Rule[0] != 'r' && $Rule[0] != 'h') {
						$Rule = 'r'.$Rule;
					}
					$Str .= '<a href="rules.php?p=upload#'.urlencode(Format::undisplay_str($Rule)).'">'.preg_replace('/[aA-zZ]/', '', $Block['Val']).'</a>';
					break;
				case 'torrent':
					$Pattern = '/('.NONSSL_SITE_URL.'\/torrents\.php.*[\?&]id=)?(\d+)($|&|\#).*/i';
					$Matches = array();
					if (preg_match($Pattern, $Block['Val'], $Matches)) {
						if (isset($Matches[2])) {
							$GroupID = $Matches[2];
							$Groups = Torrents::get_groups(array($GroupID), true, true, false);
							if ($Groups[$GroupID]) {
								$Group = $Groups[$GroupID];
								$Str .= Artists::display_artists($Group['ExtendedArtists']).'<a href="torrents.php?id='.$GroupID.'">'.$Group['Name'].'</a>';
							} else {
								$Str .= '[torrent]'.str_replace('[inlineurl]', '', $Block['Val']).'[/torrent]';
							}
						}
					} else {
						$Str .= '[torrent]'.str_replace('[inlineurl]', '', $Block['Val']).'[/torrent]';
					}
					break;
				case 'wiki':
					$Str .= '<a href="wiki.php?action=article&amp;name='.urlencode($Block['Val']).'">'.$Block['Val'].'</a>';
					break;
				case 'tex':
					$Str .= '<img style="vertical-align: middle;" src="'.STATIC_SERVER.'blank.gif" onload="if (this.src.substr(this.src.length - 9, this.src.length) == \'blank.gif\') { this.src = \'https://chart.googleapis.com/chart?cht=tx&amp;chf=bg,s,FFFFFF00&amp;chl='.urlencode(mb_convert_encoding($Block['Val'], 'UTF-8', 'HTML-ENTITIES')).'&amp;chco=\' + hexify(getComputedStyle(this.parentNode, null).color); }" alt="'.$Block['Val'].'" />';
					break;
				case 'plain':
					$Str .= $Block['Val'];
					break;
				case 'pre':
					$Str .= '<pre>'.$Block['Val'].'</pre>';
					break;
				case 'code':
					$Str .= '<code>'.$Block['Val'].'</code>';
					break;
				case 'list':
					$Str .= "<$Block[ListType] class=\"postlist\">";
					foreach ($Block['Val'] as $Line) {

						$Str .= '<li>'.self::to_html($Line).'</li>';
					}
					$Str .= '</'.$Block['ListType'].'>';
					break;
				case 'align':
					$ValidAttribs = array('left', 'center', 'right');
					if (!in_array($Block['Attr'], $ValidAttribs)) {
						$Str .= '[align='.$Block['Attr'].']'.self::to_html($Block['Val']).'[/align]';
					} else {
						$Str .= '<div style="text-align: '.$Block['Attr'].';">'.self::to_html($Block['Val']).'</div>';
					}
					break;
				case 'color':
				case 'colour':
					$ValidAttribs = array('aqua', 'black', 'blue', 'fuchsia', 'green', 'grey', 'lime', 'maroon', 'navy', 'olive', 'purple', 'red', 'silver', 'teal', 'white', 'yellow');
					if (!in_array($Block['Attr'], $ValidAttribs) && !preg_match('/^#[0-9a-f]{6}$/', $Block['Attr'])) {
						$Str .= '[color='.$Block['Attr'].']'.self::to_html($Block['Val']).'[/color]';
					} else {
						$Str .= '<span style="color: '.$Block['Attr'].';">'.self::to_html($Block['Val']).'</span>';
					}
					break;
				case 'headline':
					$text = self::to_html($Block['Val']);
					$raw = self::raw_text($Block['Val']);
					if (!in_array($Block['Attr'], self::$HeadlineLevels)) {
						$Str .= sprintf('%1$s%2$s%1$s', str_repeat('=', $Block['Attr'] + 1), $text);
					} else {
						$id = '_' . crc32($raw . self::$HeadlineID);
						if (self::$InQuotes === 0) {
							self::$Headlines[] = array($Block['Attr'], $raw, $id);
						}

						$Str .= sprintf('<h%1$d id="%3$s">%2$s</h%1$d>', ($Block['Attr'] + 2), $text, $id);
						self::$HeadlineID++;
					}
					break;
				case 'inlinesize':
				case 'size':
					$ValidAttribs = array('1', '2', '3', '4', '5', '6', '7', '8', '9', '10');
					if (!in_array($Block['Attr'], $ValidAttribs)) {
						$Str .= '[size='.$Block['Attr'].']'.self::to_html($Block['Val']).'[/size]';
					} else {
						$Str .= '<span class="size'.$Block['Attr'].'">'.self::to_html($Block['Val']).'</span>';
					}
					break;
				case 'quote':
					self::$NoImg++; // No images inside quote tags
					self::$InQuotes++;
					if (self::$InQuotes == self::$NestsBeforeHide) { //Put quotes that are nested beyond the specified limit in [hide] tags.
						$Str .= '<strong>Older quotes</strong>: <a href="javascript:void(0);" onclick="BBCode.spoiler(this);">Show</a>';
						$Str .= '<blockquote class="hidden spoiler">';
					}
					if (!empty($Block['Attr'])) {
						$Exploded = explode('|', self::to_html($Block['Attr']));
						if (isset($Exploded[1]) && (is_numeric($Exploded[1]) || (in_array($Exploded[1][0], array('a', 't', 'c', 'r')) && is_numeric(substr($Exploded[1], 1))))) {
							// the part after | is either a number or starts with a, t, c or r, followed by a number (forum post, artist comment, torrent comment, collage comment or request comment, respectively)
							$PostID = trim($Exploded[1]);
							$Str .= '<a href="#" onclick="QuoteJump(event, \''.$PostID.'\'); return false;"><strong class="quoteheader">'.$Exploded[0].'</strong> wrote: </a>';
						}
						else {
							$Str .= '<strong class="quoteheader">'.$Exploded[0].'</strong> wrote: ';
						}
					}
					$Str .= '<blockquote>'.self::to_html($Block['Val']).'</blockquote>';
					if (self::$InQuotes == self::$NestsBeforeHide) { //Close quote the deeply nested quote [hide].
						$Str .= '</blockquote><br />'; // Ensure new line after quote train hiding
					}
					self::$NoImg--;
					self::$InQuotes--;
					break;
				case 'hide':
					$Str .= '<strong>'.(($Block['Attr']) ? $Block['Attr'] : 'Hidden text').'</strong>: <a href="javascript:void(0);" onclick="BBCode.spoiler(this);">Show</a>';
					$Str .= '<blockquote class="hidden spoiler">'.self::to_html($Block['Val']).'</blockquote>';
					break;
				case 'mature':
					if (G::$LoggedUser['EnableMatureContent']) {
						if (!empty($Block['Attr'])) {
							$Str .= '<strong class="mature" style="font-size: 1.2em;">Mature content:</strong><strong> ' . $Block['Attr'] . '</strong><br /> <a href="javascript:void(0);" onclick="BBCode.spoiler(this);">Show</a>';
							$Str .= '<blockquote class="hidden spoiler">'.self::to_html($Block['Val']).'</blockquote>';
						}
						else {
							$Str .= '<strong>Use of the [mature] tag requires a description.</strong> The correct format is as follows: <strong>[mature=description] ...content... [/mature]</strong>, where "description" is a mandatory description of the post. Misleading descriptions will be penalized. For further information on our mature content policies, please refer to this <a href="wiki.php?action=article&amp;id=1063">wiki</a>.';
						}
					}
					else {
						$Str .= '<span class="mature_blocked" style="font-style: italic;"><a href="wiki.php?action=article&amp;id=1063">Mature content</a> has been blocked. You can choose to view mature content by editing your <a href="user.php?action=edit&amp;userid=' . G::$LoggedUser['ID'] . '">settings</a>.</span>';
					}
					break;
				case 'img':
					if (self::$NoImg > 0 && self::valid_url($Block['Val'])) {
						$Str .= '<a rel="noreferrer" target="_blank" href="'.$Block['Val'].'">'.$Block['Val'].'</a> (image)';
						break;
					}
					if (!self::valid_url($Block['Val'], '\.(jpe?g|gif|png|bmp|tiff)')) {
						$Str .= '[img]'.$Block['Val'].'[/img]';
					} else {
						$LocalURL = self::local_url($Block['Val']);
						if ($LocalURL) {
							$Str .= '<img class="scale_image" onclick="lightbox.init(this, $(this).width());" alt="'.$Block['Val'].'" src="'.$LocalURL.'" />';
						} else {
							$Str .= '<img class="scale_image" onclick="lightbox.init(this, $(this).width());" alt="'.$Block['Val'].'" src="'.ImageTools::process($Block['Val']).'" />';
						}
					}
					break;

				case 'aud':
					if (self::$NoImg > 0 && self::valid_url($Block['Val'])) {
						$Str .= '<a rel="noreferrer" target="_blank" href="'.$Block['Val'].'">'.$Block['Val'].'</a> (audio)';
						break;
					}
					if (!self::valid_url($Block['Val'], '\.(mp3|ogg|wav)')) {
						$Str .= '[aud]'.$Block['Val'].'[/aud]';
					} else {
						//TODO: Proxy this for staff?
						$Str .= '<audio controls="controls" src="'.$Block['Val'].'"><a rel="noreferrer" target="_blank" href="'.$Block['Val'].'">'.$Block['Val'].'</a></audio>';
					}
					break;

				case 'url':
					// Make sure the URL has a label
					if (empty($Block['Val'])) {
						$Block['Val'] = $Block['Attr'];
						$NoName = true; // If there isn't a Val for this
					} else {
						$Block['Val'] = self::to_html($Block['Val']);
						$NoName = false;
					}

					if (!self::valid_url($Block['Attr'])) {
						$Str .= '[url='.$Block['Attr'].']'.$Block['Val'].'[/url]';
					} else {
						$LocalURL = self::local_url($Block['Attr']);
						if ($LocalURL) {
							if ($NoName) { $Block['Val'] = substr($LocalURL,1); }
							$Str .= '<a href="'.$LocalURL.'">'.$Block['Val'].'</a>';
						} else {
							$Str .= '<a rel="noreferrer" target="_blank" href="'.$Block['Attr'].'">'.$Block['Val'].'</a>';
						}
					}
					break;

				case 'inlineurl':
					if (!self::valid_url($Block['Attr'], '', true)) {
						$Array = self::parse($Block['Attr']);
						$Block['Attr'] = $Array;
						$Str .= self::to_html($Block['Attr']);
					}

					else {
						$LocalURL = self::local_url($Block['Attr']);
						if ($LocalURL) {
							$Str .= '<a href="'.$LocalURL.'">'.substr($LocalURL,1).'</a>';
						} else {
							$Str .= '<a rel="noreferrer" target="_blank" href="'.$Block['Attr'].'">'.$Block['Attr'].'</a>';
						}
					}

					break;

			}
		}
		}
		self::$Levels--;
		return $Str;
	}

	private static function raw_text ($Array) {
		$Str = '';
		foreach ($Array as $Block) {
			if (is_string($Block)) {
				$Str .= $Block;
				continue;
			}
			switch ($Block['Type']) {
				case 'headline':
					break;
				case 'b':
				case 'u':
				case 'i':
				case 's':
				case 'color':
				case 'size':
				case 'quote':
				case 'align':

					$Str .= self::raw_text($Block['Val']);
					break;
				case 'tex': //since this will never strip cleanly, just remove it
					break;
				case 'artist':
				case 'user':
				case 'wiki':
				case 'pre':
				case 'code':
				case 'aud':
				case 'img':
					$Str .= $Block['Val'];
					break;
				case 'list':
					foreach ($Block['Val'] as $Line) {
						$Str .= $Block['Tag'].self::raw_text($Line);
					}
					break;

				case 'url':
					// Make sure the URL has a label
					if (empty($Block['Val'])) {
						$Block['Val'] = $Block['Attr'];
					} else {
						$Block['Val'] = self::raw_text($Block['Val']);
					}

					$Str .= $Block['Val'];
					break;

				case 'inlineurl':
					if (!self::valid_url($Block['Attr'], '', true)) {
						$Array = self::parse($Block['Attr']);
						$Block['Attr'] = $Array;
						$Str .= self::raw_text($Block['Attr']);
					}
					else {
						$Str .= $Block['Attr'];
					}

					break;
			}
		}
		return $Str;
	}

	private static function smileys($Str) {
		if (!empty(G::$LoggedUser['DisableSmileys'])) {
			return $Str;
		}
		if (count(self::$ProcessedSmileys) == 0 && count(self::$Smileys) > 0) {
			foreach (self::$Smileys as $Key => $Val) {
				self::$ProcessedSmileys[$Key] = '<img border="0" src="'.STATIC_SERVER.'common/smileys/'.$Val.'" alt="" />';
			}
			reset(self::$ProcessedSmileys);
		}
		$Str = strtr($Str, self::$ProcessedSmileys);
		return $Str;
	}
}
/*

// Uncomment this part to test the class via command line:
function display_str($Str) {
	return $Str;
}
function check_perms($Perm) {
	return true;
}
$Str = "hello
[pre]http://anonym.to/?http://whatshirts.portmerch.com/
====hi====
===hi===
==hi==[/pre]
====hi====
hi";
echo Text::full_format($Str);
echo "\n"
*/
