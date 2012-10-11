<?
class View {
	/**
	 * This function is to include the header file on a page.
	 *
	 * @param $PageTitle the title of the page
	 * @param $JSIncludes is a comma separated list of js files to be inclides on
	 *                    the page, ONLY PUT THE RELATIVE LOCATION WITHOUT .js
	 *                    ex: 'somefile,somdire/somefile'
	 */
	public static function show_header($PageTitle='',$JSIncludes='') {
		global $Document, $Cache, $DB, $LoggedUser, $Mobile, $Classes;

		if($PageTitle!='') { $PageTitle.=' :: '; }
		$PageTitle .= SITE_NAME;

		if(!is_array($LoggedUser) || empty($LoggedUser['ID'])) {
			require(SERVER_ROOT.'/design/publicheader.php');
		} else {
			require(SERVER_ROOT.'/design/privateheader.php');
		}
	}

	/**
	 * This function is to include the footer file on a page.
	 *
	 * @param $Options an optional array that you can pass information to the
	 *                 header through as well as setup certain limitations
	 *	               Here is a list of parameters that work in the $Options array:
	 *                 ['disclaimer'] = [boolean] (False) Displays the disclaimer in the footer
	 */
	public static function show_footer($Options=array()) {
		global $ScriptStartTime, $LoggedUser, $Cache, $DB, $SessionID, $UserSessions, $Debug, $Time;
		if (!is_array($LoggedUser)) { require(SERVER_ROOT.'/design/publicfooter.php'); }
		else { require(SERVER_ROOT.'/design/privatefooter.php'); }
	}


	/**
	 * This is a generic function to load a template fromm /design and render it.
	 * The template should be in /design/my_template_name.php, and have a class
	 * in it called MyTemplateNameTemplate (my_template_name transformed to
	 * MixedCase, with the word 'Template' appended).
	 * This class should have a public static function render($Args), where
	 * $Args is an associative array of the template variables.
	 * You should note that by "Template", we mean "php file that outputs stuff".
	 *
	 * This function loads /design/$TemplateName.php, and then calls
	 * render($Args) on the class.
	 *
	 * @param string $TemplateName The name of the template, in underscore_format
	 * @param array $Args the arguments passed to the template.
	 */
	public static function render_template($TemplateName, $Args) {
		static $LoadedTemplates; // Keep track of templates we've already loaded.
		$ClassName = '';
		if (isset($LoadedTemplates[$TemplateName])) {
			$ClassName = $LoadedTemplates[$TemplateName];
		} else {
			include(SERVER_ROOT.'/design/' . $TemplateName . '.php');

			// Turn template_name into TemplateName
			$ClassNameParts = explode('_', $TemplateName);
			foreach ($ClassNameParts as $Index => $Part) {
				$ClassNameParts[$Index] = ucfirst($Part);
			}
			$ClassName = implode($ClassNameParts). 'Template';
			$LoadedTemplates[$TemplateName] = $ClassName;
		}
		$ClassName::render($Args);
	}
}
?>
