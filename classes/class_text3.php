<?
class TEXT_3 {
	// tag=>max number of attributes
	private $ValidTags = array('b'=>0, 'u'=>0, 'i'=>0, 's'=>0, '*'=>0, 'artist'=>0, 'user'=>0, 'n'=>0, 'inlineurl'=>0, 'inlinesize'=>1, 'align'=>1, 'color'=>1, 'colour'=>1, 'size'=>1, 'url'=>1, 'img'=>1, 'quote'=>1, 'pre'=>1, 'tex'=>0, 'hide'=>1, 'plain'=>0
	);
	private $Smileys = array(
		':angry:'			=> 'angry.gif',
		':-D'				=> 'biggrin.gif',
		':D'				=> 'biggrin.gif',
		':|'				=> 'blank.gif',
		':-|'				=> 'blank.gif',
		':blush:'			=> 'blush.gif',
		':cool:'			=> 'cool.gif',
		':\'('				=> 'crying.gif',
		'&gt;.&gt;'			=> 'eyesright.gif',
		':frown:'			=> 'frown.gif',
		'&lt;3'				=> 'heart.gif',
		':unsure:'			=> 'hmm.gif',
		':whatlove:'		=> 'ilu.gif',
		':lol:'				=> 'laughing.gif',
		':loveflac:'		=> 'loveflac.gif',
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
		':-P'				=> 'tongue.gif',
		':-p'				=> 'tongue.gif',
		':wave:'			=> 'wave.gif',
		':wink:'			=> 'wink.gif',
		':creepy:'			=> 'creepy.gif',
		':worried:'			=> 'worried.gif',
		':wtf:'				=> 'wtf.gif',
		':wub:'				=> 'wub.gif'
	);
	
	private $NoImg = 0; // If images should be turned into URLs
	private $Levels = 0; // If images should be turned into URLs
	
	function __construct() {
		foreach($this->Smileys as $Key=>$Val) {
			$this->Smileys[$Key] = '<img border="0" src="'.STATIC_SERVER.'common/smileys/'.$Val.'" alt="" />';
		}
		reset($this->Smileys);
	}
	
	function full_format($Str) {
		$Str = display_str($Str);

		//Inline links
		$Str = preg_replace('/(?<!(\[url\]|\[url\=|\[img\=|\[img\]))http(s)?:\/\//i', '$1[inlineurl]http$2://', $Str);
		// For anonym.to links. We can't have this in the regex because php freaks out at the ?, even if it's escaped
		$Str = strtr($Str, array('?[inlineurl]http'=>'?http', '=[inlineurl]http'=>'=http')); 
		$Str = preg_replace('/\=\=\=\=([^=].*)\=\=\=\=/i', '[inlinesize=3]$1[/inlinesize]', $Str);
		$Str = preg_replace('/\=\=\=([^=].*)\=\=\=/i', '[inlinesize=5]$1[/inlinesize]', $Str);
		$Str = preg_replace('/\=\=([^=].*)\=\=/i', '[inlinesize=7]$1[/inlinesize]', $Str);
		
		$Str = $this->parse($Str);
		
		$HTML = $this->to_html($Str);
		
		$HTML = nl2br($HTML);
		return $HTML;
	}
	
	function strip_bbcode($Str) {
		$Str = display_str($Str);
		
		//Inline links
		$Str = preg_replace('/(?<!(\[url\]|\[url\=|\[img\=|\[img\]))http(s)?:\/\//i', '$1[inlineurl]http$2://', $Str);
		
		$Str = $this->parse($Str);
		
		$Str = $this->raw_text($Str);
		
		$Str = nl2br($Str);
		return $Str;
	}
	
	
	function valid_url($Str, $Extension = '', $Inline = false) {
		$Regex = '/^';
		$Regex .= '(https?|ftps?|irc):\/\/'; // protocol
		$Regex .= '(\w+(:\w+)?@)?'; // user:pass@
		$Regex .= '(';
		$Regex .= '(([0-9]{1,3}\.){3}[0-9]{1,3})|'; // IP or...
		$Regex .= '(([a-z0-9\-\_]+\.)+\w{2,6})'; // sub.sub.sub.host.com
		$Regex .= ')';
		$Regex .= '(:[0-9]{1,5})?'; // port
		$Regex .= '\/?'; // slash?
		$Regex .= '(\/?[0-9a-z\-_.,&=@~%\/:;()+!#]+)*'; // /file
		if(!empty($Extension)) {
			$Regex.=$Extension;
		}

		// query string
		if ($Inline) {
			$Regex .= '(\?([0-9a-z\-_.,%\/\@~&=:;()+*\^$!#]|\[\d*\])*)?';
		} else {
			$Regex .= '(\?[0-9a-z\-_.,%\/\@[\]~&=:;()+*\^$!#]*)?';
		}

		$Regex .= '(#[a-z0-9\-_.,%\/\@[\]~&=:;()+*\^$!]*)?'; // #anchor
		$Regex .= '$/i';
		
		return preg_match($Regex, $Str, $Matches);
	}
	
	function local_url($Str) {
		$URLInfo = parse_url($Str);
		if(!$URLInfo) { return false; }
		$Host = $URLInfo['host'];
		// If for some reason your site does not require subdomains or contains a directory in the SITE_URL, revert to the line below.
		//if($Host == NONSSL_SITE_URL || $Host == SSL_SITE_URL || $Host == 'www.'.NONSSL_SITE_URL) {
		if(preg_match('/(\S+\.)*'.NONSSL_SITE_URL.'/', $Host)) {
			$URL = $URLInfo['path'];
			if(!empty($URLInfo['query'])) {
				$URL.='?'.$URLInfo['query'];
			}
			if(!empty($URLInfo['fragment'])) {
				$URL.='#'.$URLInfo['fragment'];
			}
			return $URL;
		} else {
			return false;
		}
		
	}
	
	
/* How parsing works

Parsing takes $Str, breaks it into blocks, and builds it into $Array. 
Blocks start at the beginning of $Str, when the parser encounters a [, and after a tag has been closed.
This is all done in a loop. 

EXPLANATION OF PARSER LOGIC

1) Find the next tag (regex)
	1a) If there aren't any tags left, write everything remaining to a block and return (done parsing)
	1b) If the next tag isn't where the pointer is, write everything up to there to a text block.
2) See if it's a [[wiki-link]] or an ordinary tag, and get the tag name
3) If it's not a wiki link:
	3a) check it against the $this->ValidTags array to see if it's actually a tag and not [bullshit]
		If it's [not a tag], just leave it as plaintext and move on
	3b) Get the attribute, if it exists [name=attribute]
4) Move the pointer past the end of the tag
5) Find out where the tag closes (beginning of [/tag])
	5a) Different for different types of tag. Some tags don't close, others are weird like [*]
	5b) If it's a normal tag, it may have versions of itself nested inside - eg:
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
	function parse($Str) {
		$i = 0; // Pointer to keep track of where we are in $Str
		$Len = strlen($Str);
		$Array = array();
		$ArrayPos = 0;
		
		while($i<$Len) {
			$Block = '';
			
			// 1) Find the next tag (regex)
			// [name(=attribute)?]|[[wiki-link]]
			$IsTag = preg_match("/((\[[a-zA-Z*]+)(=(?:[^\n'\"\[\]]|\[\d*\])+)?\])|(\[\[[^\n\"'\[\]]+\]\])/", $Str, $Tag, PREG_OFFSET_CAPTURE, $i);
			
			// 1a) If there aren't any tags left, write everything remaining to a block
			if(!$IsTag) {
				// No more tags
				$Array[$ArrayPos] = substr($Str, $i);
				break;
			}
			
			// 1b) If the next tag isn't where the pointer is, write everything up to there to a text block.
			$TagPos = $Tag[0][1];
			if($TagPos>$i) {
				$Array[$ArrayPos] = substr($Str, $i, $TagPos-$i);
				++$ArrayPos;
				$i=$TagPos;
			}
			
			// 2) See if it's a [[wiki-link]] or an ordinary tag, and get the tag name
			if(!empty($Tag[4][0])) { // Wiki-link
				$WikiLink = true;
				$TagName = substr($Tag[4][0], 2, -2);
				$Attrib = '';
			} else { // 3) If it's not a wiki link:
				$WikiLink = false;
				$TagName = strtolower(substr($Tag[2][0], 1));
				
				//3a) check it against the $this->ValidTags array to see if it's actually a tag and not [bullshit]
				if(!isset($this->ValidTags[$TagName])) {
					$Array[$ArrayPos] = substr($Str, $i, ($TagPos-$i)+strlen($Tag[0][0]));
					$i=$TagPos+strlen($Tag[0][0]);
					++$ArrayPos;
					continue;
				}
				
				$MaxAttribs = $this->ValidTags[$TagName];
				
				// 3b) Get the attribute, if it exists [name=attribute]
				if(!empty($Tag[3][0])) {
					$Attrib = substr($Tag[3][0], 1);
				} else {
					$Attrib='';
				}
			}
			
			// 4) Move the pointer past the end of the tag
			$i=$TagPos+strlen($Tag[0][0]);
			
			// 5) Find out where the tag closes (beginning of [/tag])
			
			// Unfortunately, BBCode doesn't have nice standards like xhtml
			// [*], [img=...], and http:// follow different formats
			// Thus, we have to handle these before we handle the majority of tags
			
			
			//5a) Different for different types of tag. Some tags don't close, others are weird like [*]
			if($TagName == 'img' && !empty($Tag[3][0])) { //[img=...]
				$Block = ''; // Nothing inside this tag
				// Don't need to touch $i
			} elseif($TagName == 'inlineurl') { // We did a big replace early on to turn http:// into [inlineurl]http://
				
				// Let's say the block can stop at a newline or a space
				$CloseTag = strcspn($Str, " \n\r", $i);
				if($CloseTag === false) { // block finishes with URL
					$CloseTag = $Len;
				}
				if(preg_match('/[!;,.?:]+$/',substr($Str, $i, $CloseTag), $Match)) {
					$CloseTag -= strlen($Match[0]);
				}
				$URL = substr($Str, $i, $CloseTag);
				if(substr($URL, -1) == ')' && substr_count($URL, '(') < substr_count($URL, ')')) {
					$CloseTag--;
					$URL = substr($URL, 0, -1);
				}
				$Block = $URL; // Get the URL
				
				// strcspn returns the number of characters after the offset $i, not after the beginning of the string
				// Therefore, we use += instead of the = everywhere else
				$i += $CloseTag; // 5d) Move the pointer past the end of the [/close] tag. 
			} elseif($WikiLink == true || $TagName == 'n') { 
				// Don't need to do anything - empty tag with no closing
			} elseif($TagName == '*') {
				// We're in a list. Find where it ends
				$NewLine = $i;
				do { // Look for \n[*]
					$NewLine = strpos($Str, "\n", $NewLine+1);
				} while($NewLine!== false && substr($Str, $NewLine+1, 3) == '[*]');
				
				$CloseTag = $NewLine;
				if($CloseTag === false) { // block finishes with list
					$CloseTag = $Len;
				}
				$Block = substr($Str, $i, $CloseTag-$i); // Get the list
				$i = $CloseTag; // 5d) Move the pointer past the end of the [/close] tag. 
			} else {
				//5b) If it's a normal tag, it may have versions of itself nested inside
				$CloseTag = $i-1;
				$InTagPos = $i-1;
				$NumInOpens = 0;
				$NumInCloses = -1;
				
				$InOpenRegex = '/\[('.$TagName.')';
				if($MaxAttribs>0) {
					$InOpenRegex.="(=[^\n'\"\[\]]+)?";
				}
				$InOpenRegex.='\]/i';
				
				
				// Every time we find an internal open tag of the same type, search for the next close tag 
				// (as the first close tag won't do - it's been opened again)
				do {
					$CloseTag = stripos($Str, '[/'.$TagName.']', $CloseTag+1);
					if($CloseTag === false) {
						$CloseTag = $Len;
						break;
					} else {
						$NumInCloses++; // Majority of cases
					}
					
					// Is there another open tag inside this one?
					$OpenTag = preg_match($InOpenRegex, $Str, $InTag, PREG_OFFSET_CAPTURE, $InTagPos+1);
					if(!$OpenTag || $InTag[0][1]>$CloseTag) {
						break;
					} else {
						$InTagPos = $InTag[0][1];
						$NumInOpens++;
					}
					
				} while($NumInOpens>$NumInCloses);
				
				
				// Find the internal block inside the tag
				$Block = substr($Str, $i, $CloseTag-$i); // 5c) Get the contents between [open] and [/close] and call it the block.
				
				$i = $CloseTag+strlen($TagName)+3; // 5d) Move the pointer past the end of the [/close] tag. 
				
			}
			
			// 6) Depending on what type of tag we're dealing with, create an array with the attribute and block.
			switch($TagName) {
				case 'inlineurl':
					$Array[$ArrayPos] = array('Type'=>'inlineurl', 'Attr'=>$Block, 'Val'=>'');
					break;
				case 'url':
					$Array[$ArrayPos] = array('Type'=>'img', 'Attr'=>$Attrib, 'Val'=>$Block);
					if(empty($Attrib)) { // [url]http://...[/url] - always set URL to attribute
						$Array[$ArrayPos] = array('Type'=>'url', 'Attr'=>$Block, 'Val'=>'');
					} else {
						$Array[$ArrayPos] = array('Type'=>'url', 'Attr'=>$Attrib, 'Val'=>$this->parse($Block));
					}
					break;
				case 'quote':
					$Array[$ArrayPos] = array('Type'=>'quote', 'Attr'=>$this->Parse($Attrib), 'Val'=>$this->parse($Block));
					break;
				case 'img':
				case 'image':
					if(empty($Block)) {
						$Block = $Attrib;
					}
					$Array[$ArrayPos] = array('Type'=>'img', 'Val'=>$Block);
					break;
				case 'aud':
				case 'mp3':
				case 'audio':
					if(empty($Block)) {
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
				case 'tex':
					$Array[$ArrayPos] = array('Type'=>'tex', 'Val'=>$Block);
					break;
				case 'pre':
				case 'plain':
					$Block = strtr($Block, array('[inlineurl]'=>''));
					$Block = preg_replace('/\[inlinesize\=3\](.*?)\[\/inlinesize\]/i', '====$1====', $Block);
					$Block = preg_replace('/\[inlinesize\=5\](.*?)\[\/inlinesize\]/i', '===$1===', $Block);
					$Block = preg_replace('/\[inlinesize\=7\](.*?)\[\/inlinesize\]/i', '==$1==', $Block);
					
					$Array[$ArrayPos] = array('Type'=>$TagName, 'Val'=>$Block);
					break;
				case 'hide':
					$Array[$ArrayPos] = array('Type'=>'hide', 'Attr'=>$Attrib, 'Val'=>$this->parse($Block));
					break;
				case '*':
						$Array[$ArrayPos] = array('Type'=>'list');
						$Array[$ArrayPos]['Val'] = explode('[*]', $Block);
						foreach($Array[$ArrayPos]['Val'] as $Key=>$Val) {
							$Array[$ArrayPos]['Val'][$Key] = $this->parse(trim($Val));
						}
					break;
				case 'n':
					$ArrayPos--;
					break; // n serves only to disrupt bbcode (backwards compatibility - use [pre])
				default:
					if($WikiLink == true) {
						$Array[$ArrayPos] = array('Type'=>'wiki','Val'=>$TagName);
					} else { 
						
						// Basic tags, like [b] or [size=5]
						
						$Array[$ArrayPos] = array('Type'=>$TagName, 'Val'=>$this->parse($Block));
						if(!empty($Attrib) && $MaxAttribs>0) {
							$Array[$ArrayPos]['Attr'] = strtolower($Attrib);
						}
					}
			}
			
			$ArrayPos++; // 7) Increment array pointer, start again (past the end of the [/close] tag)
		}
		return $Array;
	}
	
	function to_html($Array) {
		$this->Levels++;
		if($this->Levels>10) { return $Block['Val']; } // Hax prevention
		$Str = '';
		
		foreach($Array as $Block) {
			if(is_string($Block)) {
				$Str.=$this->smileys($Block);
				continue;
			}
			switch($Block['Type']) {
				case 'b':
					$Str.='<strong>'.$this->to_html($Block['Val']).'</strong>';
					break;
				case 'u':
					$Str.='<span style="text-decoration: underline;">'.$this->to_html($Block['Val']).'</span>';
					break;
				case 'i':
					$Str.='<em>'.$this->to_html($Block['Val'])."</em>";
					break;
				case 's':
					$Str.='<span style="text-decoration: line-through">'.$this->to_html($Block['Val']).'</span>';
					break;
				case 'user':
					$Str.='<a href="user.php?action=search&amp;search='.urlencode($Block['Val']).'">'.$Block['Val'].'</a>';
					break;
				case 'artist':
					$Str.='<a href="artist.php?artistname='.urlencode(mb_convert_encoding($Block['Val'],"UTF-8","HTML-ENTITIES")).'">'.$Block['Val'].'</a>';
					break;
				case 'wiki':
					$Str.='<a href="wiki.php?action=article&amp;name='.urlencode($Block['Val']).'">'.$Block['Val'].'</a>';
					break;
				case 'tex':
					$Str.='<img style="vertical-align: middle" src="'.STATIC_SERVER.'blank.gif" onload="if (this.src.substr(this.src.length-9,this.src.length) == \'blank.gif\') { this.src = \'http://chart.apis.google.com/chart?cht=tx&amp;chf=bg,s,FFFFFF00&amp;chl='.urlencode(mb_convert_encoding($Block['Val'],"UTF-8","HTML-ENTITIES")).'&amp;chco=\' + hexify(getComputedStyle(this.parentNode,null).color); }" />';
					break;
				case 'plain':
					$Str.=$Block['Val'];
					break;
				case 'pre':
					$Str.='<pre>'.$Block['Val'].'</pre>';
					break;
				case 'list':
					$Str .= '<ul>';
					foreach($Block['Val'] as $Line) {
						
						$Str.='<li>'.$this->to_html($Line).'</li>';
					}
					$Str.='</ul>';
					break;
				case 'align':
					$ValidAttribs = array('left', 'center', 'right');
					if(!in_array($Block['Attr'], $ValidAttribs)) {
						$Str.='[align='.$Block['Attr'].']'.$this->to_html($Block['Val']).'[/align]';
					} else {
						$Str.='<div style="text-align:'.$Block['Attr'].'">'.$this->to_html($Block['Val']).'</div>';
					}
					break;
				case 'color':
				case 'colour':
					$ValidAttribs = array('aqua', 'black', 'blue', 'fuchsia', 'green', 'grey', 'lime', 'maroon', 'navy', 'olive', 'purple', 'red', 'silver', 'teal', 'white', 'yellow');
					if(!in_array($Block['Attr'], $ValidAttribs) && !preg_match('/^#[0-9a-f]{6}$/', $Block['Attr'])) { 
						$Str.='[color='.$Block['Attr'].']'.$this->to_html($Block['Val']).'[/color]';
					} else {
						$Str.='<span style="color:'.$Block['Attr'].'">'.$this->to_html($Block['Val']).'</span>';
					}
					break;
				case 'inlinesize':
				case 'size':
					$ValidAttribs = array('1','2','3','4','5','6','7','8','9','10');
					if(!in_array($Block['Attr'], $ValidAttribs)) {
						$Str.='[size='.$Block['Attr'].']'.$this->to_html($Block['Val']).'[/size]';
					} else {
						$Str.='<span class="size'.$Block['Attr'].'">'.$this->to_html($Block['Val']).'</span>';
					}
					break;
				case 'quote':
					$this->NoImg++; // No images inside quote tags
					if(!empty($Block['Attr'])) {
						$Str.= '<strong>'.$this->to_html($Block['Attr']).'</strong> wrote: ';
					}
					$Str.='<blockquote>'.$this->to_html($Block['Val']).'</blockquote>';
					$this->NoImg--;
					break;
				case 'hide':
					$Str.='<strong>'.(($Block['Attr']) ? $Block['Attr'] : 'Hidden text').'</strong>: <a href="javascript:void(0);" onclick="BBCode.spoiler(this);">Show</a>';
					$Str.='<blockquote class="hidden spoiler">'.$this->to_html($Block['Val']).'</blockquote>';
					break;
				case 'img':
					if($this->NoImg>0 && $this->valid_url($Block['Val'])) {
						$Str.='<a rel="noreferrer" target="_blank" href="'.$Block['Val'].'">'.$Block['Val'].'</a> (image)';
						break;
					}
					if(!$this->valid_url($Block['Val'], '\.(jpe?g|gif|png|bmp|tiff)')) {
						$Str.='[img]'.$Block['Val'].'[/img]';
					} else {
						if(check_perms('site_proxy_images')) {
							$Str.='<img style="max-width: 500px;" onclick="lightbox.init(this,500);" alt="'.$Block['Val'].'" src="http://'.SITE_URL.'/image.php?i='.urlencode($Block['Val']).'" />';
						} else {
							$Str.='<img style="max-width: 500px;" onclick="lightbox.init(this,500);" alt="'.$Block['Val'].'" src="'.$Block['Val'].'" />';
						}
					}
					break;
					
				case 'aud':
					if($this->NoImg>0 && $this->valid_url($Block['Val'])) {
						$Str.='<a rel="noreferrer" target="_blank" href="'.$Block['Val'].'">'.$Block['Val'].'</a> (audio)';
						break;
					}
					if(!$this->valid_url($Block['Val'], '\.(mp3|ogg|wav)')) {
						$Str.='[aud]'.$Block['Val'].'[/aud]';
					} else {
						//TODO: Proxy this for staff?
						$Str.='<audio controls="controls" src="'.$Block['Val'].'"><a rel="noreferrer" target="_blank" href="'.$Block['Val'].'">'.$Block['Val'].'</a></audio>';
					}
					break;
					
				case 'url':
					// Make sure the URL has a label
					if(empty($Block['Val'])) {
						$Block['Val'] = $Block['Attr'];
						$NoName = true; // If there isn't a Val for this
					} else {
						$Block['Val'] = $this->to_html($Block['Val']);
						$NoName = false;
					}
					
					if(!$this->valid_url($Block['Attr'])) {
						$Str.='[url='.$Block['Attr'].']'.$Block['Val'].'[/url]';
					} else {
						$LocalURL = $this->local_url($Block['Attr']);
						if($LocalURL) {
							if($NoName) { $Block['Val'] = substr($LocalURL,1); }
							$Str.='<a href="'.$LocalURL.'">'.$Block['Val'].'</a>';
						} else {
							$Str.='<a rel="noreferrer" target="_blank" href="'.$Block['Attr'].'">'.$Block['Val'].'</a>';
						}
					}
					break;
					
				case 'inlineurl':
					if(!$this->valid_url($Block['Attr'], '', true)) {
						$Array = $this->parse($Block['Attr']);
						$Block['Attr'] = $Array;
						$Str.=$this->to_html($Block['Attr']);
					}
					
					else {
						$LocalURL = $this->local_url($Block['Attr']);
						if($LocalURL) {
							$Str.='<a href="'.$LocalURL.'">'.substr($LocalURL,1).'</a>';
						} else {
							$Str.='<a rel="noreferrer" target="_blank" href="'.$Block['Attr'].'">'.$Block['Attr'].'</a>';
						} 
					}
					
					break;
				
			}
		}
		$this->Levels--;
		return $Str;
	}
	
	function raw_text($Array) {
		$Str = '';
		foreach($Array as $Block) {
			if(is_string($Block)) {
				$Str.=$Block;
				continue;
			}
			switch($Block['Type']) {
			
				case 'b':
				case 'u':
				case 'i':
				case 's':
				case 'color':
				case 'size':
				case 'quote':
				case 'align':
				
					$Str.=$this->raw_text($Block['Val']);
					break;
				case 'tex': //since this will never strip cleanly, just remove it
					break;
				case 'artist':
				case 'user':
				case 'wiki':
				case 'pre':
				case 'aud':
				case 'img':
					$Str.=$Block['Val'];
					break;
				case 'list':
					foreach($Block['Val'] as $Line) {
						$Str.='*'.$this->raw_text($Line);
					}
					break;
					
				case 'url':
					// Make sure the URL has a label
					if(empty($Block['Val'])) {
						$Block['Val'] = $Block['Attr'];
					} else {
						$Block['Val'] = $this->raw_text($Block['Val']);
					}
					
					$Str.=$Block['Val'];
					break;
					
				case 'inlineurl':
					if(!$this->valid_url($Block['Attr'], '', true)) {
						$Array = $this->parse($Block['Attr']);
						$Block['Attr'] = $Array;
						$Str.=$this->raw_text($Block['Attr']);
					}
					else {
						$Str.=$Block['Attr'];
					}
					
					break;
			}
		}
		return $Str;
	}
	
	function smileys($Str) {
		global $LoggedUser;
		if(!empty($LoggedUser['DisableSmileys'])) {
			return $Str;
		}
		$Str = strtr($Str, $this->Smileys);
		return $Str;
	}
}
/*

//Uncomment this part to test the class via command line: 
function display_str($Str) {return $Str;}
function check_perms($Perm) {return true;}
$Str = "hello 
[pre]http://anonym.to/?http://whatshirts.portmerch.com/
====hi====
===hi===
==hi==[/pre]
====hi====
hi";
$Text = NEW TEXT;
echo $Text->full_format($Str);
echo "\n"
*/
?>
