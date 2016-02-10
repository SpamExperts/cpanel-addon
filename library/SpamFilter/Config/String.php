<?php
/**
 * Creating a Zend_Config with a passed ini string.
 *
 * @author Romeo Disca
 */
class SpamFilter_Config_String extends Zend_Config_Ini
{

    /**
     * Load the INI file from disk using parse_ini_file(). Use a private error
     * handler to convert any loading errors into a Zend_Config_Exception
     *
     * @param string $iniContents
     * @throws Zend_Config_Exception
     * @return array
     */
    static public function _parseIniFileContents($iniContents)
    {
        $iniArray = self::parse_ini_string($iniContents, true); // Warnings and errors are suppressed

        // Check if there was a error while loading file
        if (!is_array($iniArray)) {
            /**
             * @see Zend_Config_Exception
             */
            // require_once 'Zend/Config/Exception.php';
            throw new Zend_Config_Exception();
        }

        return $iniArray;
    }

    /**
     * @param $string
     *
     * @return array
     */
    static private function parse_ini_string( $string )
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
		            if(function_exists('mb_substr'))
		            {			
                        $value = mb_substr( $value, 1, mb_strlen( $value ) - 2 );
		            } else {
                        $value = substr( $value, 1, strlen( $value ) - 2 );
		            }
                }

                $array[ $key ] = $value;
            }
        }
        return $array;
	}

}
