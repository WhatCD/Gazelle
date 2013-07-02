<?
class GOOGLE_CHARTS {
	protected $URL = 'https://chart.googleapis.com/chart';
	protected $Labels = array();
	protected $Data = array();
	protected $Options = array();

	public function __construct($Type, $Width, $Height, $Options) {
		if ($Width * $Height > 300000 || $Height > 1000 || $Width > 1000) {
			trigger_error('Tried to make chart too large.');
		}
		$this->URL .= "?cht=$Type&amp;chs={$Width}x$Height";
		$this->Options = $Options;
	}

	protected function encode($Number) {
		if ($Number == -1) {
			return '__';
		}
		$CharKey = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-.';
		return $CharKey[floor($Number / 64)].$CharKey[floor($Number % 64)];
	}

	public function color($Colors) {
		$this->URL .= '&amp;chco='.$Colors;
	}

	public function lines($Thickness, $Solid = 1, $Blank = 0) {
		$this->URL .= "&amp;chls=$Thickness,$Solid,$Blank";
	}

	public function title($Title, $Color = '', $Size = '') {
		$this->URL .= '&amp;chtt='.str_replace(array(' ', "\n"), array('+', '|'), $Title);
		if (!empty($Color)) {
			$this->URL .= '&amp;chts='.$Color;
		}
		if (!empty($Size)) {
			$this->URL .= ','.$Size;
		}
	}

	public function legend($Items, $Placement = '') {
		$this->URL .= '&amp;chdl='.str_replace(' ', '+', implode('|', $Items));
		if (!empty($Placement)) {
			if (!in_array($Placement, array('b', 't', 'r', 'l', 'bv', 'tv'))) {
				trigger_error('Invalid legend placement.');
			}
			$this->URL .= '&amp;chdlp='.$Placement;
		}
	}

	public function add($Label, $Data) {
		if ($Label !== false) {
			$this->Labels[] = $Label;
		}
		$this->Data[] = $Data;
	}

	public function grid_lines($SpacingX = 0, $SpacingY = -1, $Solid = 1, $Blank = 1) {
		//Can take 2 more parameters for offset, but we're not bothering with that right now
		$this->URL .= "&amp;chg=$SpacingX,$SpacingY,$Solid,$Blank";
	}

	public function transparent() {
		$this->URL .= '&amp;chf=bg,s,FFFFFF00';
	}


	public function url() {
		return $this->URL;
	}
}

class AREA_GRAPH extends GOOGLE_CHARTS {
	public function __construct ($Width, $Height, $Options = array()) {
		parent::__construct('lc', $Width, $Height, $Options);
	}

	public function color ($Color) {
		$this->URL .= '&amp;chco='.$Color.'&amp;chm=B,'.$Color.'50,0,0,0';
	}

	public function generate() {
		$Max = max($this->Data);
		$Min = ((isset($this->Options['Break'])) ? $Min = min($this->Data) : 0);
		$Data = array();
		foreach ($this->Data as $Value) {
			$Data[] = $this->encode((($Value - $Min) / ($Max - $Min)) * 4095);
		}
		$this->URL .= "&amp;chxt=y,x&amp;chxs=0,h&amp;chxl=1:|".implode('|', $this->Labels).'&amp;chxr=0,'.$Min.','.($Max - $Min).'&amp;chd=e:'.implode('', $Data);
	}
}

class PIE_CHART extends GOOGLE_CHARTS {
	public function __construct ($Width, $Height, $Options = array()) {
		$Type = ((isset($this->Options['3D'])) ? 'p3' : 'p');
		parent::__construct($Type, $Width, $Height, $Options);
	}

	public function generate() {
		$Sum = array_sum($this->Data);
		$Other = isset($this->Options['Other']);
		$Sort = isset($this->Options['Sort']);
		$LabelPercent = isset($this->Options['Percentage']);

		if ($Sort && !empty($this->Labels)) {
			array_multisort($this->Data, SORT_DESC, $this->Labels);
		} elseif ($Sort) {
			sort($this->Data);
			$this->Data = array_reverse($this->Data);
		}

		$Data = array();
		$Labels = $this->Labels;
		$OtherPercentage = 0.00;
		$OtherData = 0;

		foreach ($this->Data as $Key => $Value) {
			$ThisPercentage = number_format(($Value / $Sum) * 100, 2);
			$ThisData = ($Value / $Sum) * 4095;
			if ($Other && $ThisPercentage < 1) {
				$OtherPercentage += $ThisPercentage;
				$OtherData += $ThisData;
				unset($Data[$Key]);
				unset($Labels[$Key]);
				continue;
			}
			if ($LabelPercent) {
				$Labels[$Key] .= ' ('.$ThisPercentage.'%)';
			}
			$Data[] = $this->encode($ThisData);
		}
		if ($OtherPercentage > 0) {
			$OtherLabel = 'Other';
			if ($LabelPercent) {
				$OtherLabel .= ' ('.$OtherPercentage.'%)';
			}
			$Labels[] = $OtherLabel;
			$Data[] = $this->encode($OtherData);
		}
		$this->URL .= "&amp;chl=".implode('|', $Labels).'&amp;chd=e:'.implode('', $Data);
	}
}


class LOG_BAR_GRAPH extends GOOGLE_CHARTS {
	//TODO: Finish.
	public function __construct ($Base, $Width, $Height, $Options = array()) {
		parent::__construct('lc', $Width, $Height, $Options);
	}

	public function color ($Color) {
		$this->URL .= '&amp;chco='.$Color.'&amp;chm=B,'.$Color.'50,0,0,0';
	}

	public function generate() {
		$Max = max($this->Data);
		$Min = ((isset($this->Options['Break'])) ? $Min = min($this->Data) : 0);
		$Data = array();
		foreach ($this->Data as $Value) {
			$Data[] = $this->encode((($Value - $Min) / ($Max - $Min)) * 4095);
		}
		$this->URL .= "&amp;chxt=y,x&amp;chxs=0,h&amp;chxl=1:|".implode('|', $this->Labels).'&amp;chxr=0,'.$Min.','.($Max-$Min).'&amp;chd=e:'.implode('', $Data);
	}
}

class POLL_GRAPH extends GOOGLE_CHARTS {
	public function __construct () {
		$this->URL .= '?cht=bhg';
	}

	public function add($Label, $Data) {
		if ($Label !== false) {
			$this->Labels[] = Format::cut_string($Label, 35);
		}
		$this->Data[] = $Data;
	}

	public function generate() {
		$Count = count($this->Data);
		$Height = (30 * $Count) + 20;
		$Max = max($this->Data);
		$Sum = array_sum($this->Data);
		$Increment = ($Max / $Sum) * 25; // * 100% / 4divisions
		$Data = array();
		$Labels = array();
		foreach ($this->Data as $Key => $Value) {
			$Data[] = $this->encode(($Value / $Max) * 4095);
			$Labels[] = '@t'.str_replace(array(' ', ','),array('+', '\,'), $this->Labels[$Key]).',000000,1,'.round((($Key + 1) / $Count) - (12 / $Height), 2).':0,12';
		}
		$this->URL .= "&amp;chbh=25,0,5&amp;chs=214x$Height&amp;chl=0%|".round($Increment, 1)."%|".round($Increment * 2, 1)."%|".round($Increment * 3, 1)."%|".round($Increment * 4, 1)."%&amp;chm=".implode('|', $Labels).'&amp;chd=e:'.implode('', $Data);
	}
}
