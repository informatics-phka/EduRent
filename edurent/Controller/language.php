<?php
    //require
    require_once("mailer.php");
    require_once("error.php");
    require_once("db_connect.php");
    require_once("functions.php");

    //get the selected language of the user
    global $SESSION;

    if(!isset($_SESSION['lang'])){
        $_SESSION['lang'] = 'de';
    }
    else{
        if(isset($_GET['lang'])){
            $_SESSION['lang'] = $_GET['lang'];
        }
    }

    if($_SESSION['lang'] == "de_wp") $_SESSION['lang'] = "de";

    require('lang/' . $_SESSION['lang'] . '.php');

    function translate($key, $placeholders = [], $default = 'N/A') {
        global $translations;
    
        if (!isset($translations[$key])) {
            save_in_logs("Translation key '" . $key . "' not found.");
            return "Translation key '{$key}' not found.";
        }
    
        $translation = $translations[$key];
    
        preg_match_all('/{\$(.*?)}/', $translation, $matches);
        $requiredPlaceholders = $matches[1];
    
        foreach ($requiredPlaceholders as $ph) {
            $replacement = isset($placeholders[$ph]) ? $placeholders[$ph] : $default;
            $translation = str_replace('{$' . $ph . '}', $replacement, $translation);
        }
    
        return $translation;
    }
    
    function get_language()
    {
        return $_SESSION['lang'];
    }
?>
<script>
    function translate(inputString, placeholders = []) {
        let matchIndex = 0;
        return inputString.replace(/N\/A/g, () => {
            const replacement = placeholders.length > matchIndex ? placeholders[matchIndex] : defaultText;
            matchIndex++;
            return replacement;
        });
    }
</script>