<?

/**
 * Class to manage site options
 */
class SiteOptions {

    /**
     * Get a site option
     *
     * @param string $Name The option name
     * @param string $DefaultValue The value to default to if the name can't be found in the cache
     */
    public static function getSiteOption($Name, $DefaultValue) {
        $Value = G::$Cache->get_value('site_option_' . $Name);

        if ($Value === false) {
            G::$DB->query("SELECT Value FROM site_options WHERE Name = '" . db_string($Name) . "'");

            if (G::$DB->has_results()) {
                list($Value) = G::$DB->next_record();
                G::$Cache->cache_value('site_option_' . $Name, $Value);
            }
        }

        return ($Value === false ? $DefaultValue : $Value);
    }
}