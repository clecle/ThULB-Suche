<?php
    /**
     * Reads the given file and returns a associative array.
     * Values in the file are separated by a '='.
     *
     * @param String $fileName Name of the file to be read.
     *
     * @return array
     */
    function readLanguageFile($fileName) {

        $values = array();

        if(is_file($fileName) && is_readable($fileName)) {
            try{
                $file = fopen($fileName, 'r');

                while (($line = fgets($file)) !== false) {
                    $line = trim($line);

                    $pos = strpos($line, '=');
                    $tag = substr($line, 0, $pos);
                    $text = substr($line, $pos + 1);

                    $tag = trim($tag);
                    $text = trim($text);
                    $text = str_replace('<br>', "\n", $text);

                    $values[$tag] = substr($text, 1, strlen($text) - 2);
                }
            }
            finally {
                if($file !== false) {
                    fclose($file);
                }
            }
        }
        else {
            echo "Die Datei '$fileName' konnte nicht gelesen werden.<br>";
        }

        return $values;
    }

    /**
     * Writes the given values into the specified file.
     * Values in the file are separated by a '='.
     *
     * @param String $fileName  Name of the file to be written.
     * @param array  $values    Values to be written.
     *
     * @return boolean  Returns TRUE if files could be written, FALSE otherwise.
     */
    function writeLanguageFile($fileName, $values) {

        $success = true;
        if(is_file($fileName) && is_writable($fileName)) {
            ksort($values);

            try{
                $file = fopen($fileName, 'w');
                foreach ($values as $tag => $text) {
                    $tag = trim($tag);
                    $text = strip_tags($text, '<a><i>');
                    $text = trim($text);
                    $text = str_replace(["\r\n", "\n"], '<br>', $text);
                    $line = "$tag = \"$text\"\n";

                    fwrite($file, $line);
                }
            }
            catch (Exception $e) {
                $success = false;
            }
            finally {
                if($file !== false) {
                    fclose($file);
                }
            }        }
        else {
            echo "Die Datei '$fileName' konnte nicht geschrieben werden.<br>";
        }

        return $success;
    }

    /**
     * Deletes a directory with its content.
     *
     * @param String $dir Path of the directory to delete
     *
     * @return bool Returns TRUE if the directory does not exist OR if the directory was successfully deleted.
     */
    function delTree($dir) {
        if(!file_exists($dir)) {
            return true;
        }
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }

    // Login for this site
    $__user = 'ThULB';
    $__password = 'Retiebratim';

    // File paths
    $__basePath = './../local_thulb/languages/';
    $__iniGerman = $__basePath . 'dynMessages_de.ini';
    $__iniEnglish = $__basePath . 'dynMessages_en.ini';
    $__languageCache = $__basePath . '../cache/languages';

    // start session and check if user is logged in
    session_start();
    setcookie(session_name(),session_id(),time() + 900);            // Session expires after 15 minutes (900 seconds)
    if(($_POST['user'] === $__user && $_POST['pass'] === $__password)) {
        $_SESSION['logged_in_dyn_content'] = true;
    }
    $loggedIn = $_SESSION['logged_in_dyn_content'];

    $tags = $_POST['text_key'];
    if(!empty($tags) && $loggedIn) {
        $english = array_combine($tags, $_POST['english']);
        $german = array_combine($tags, $_POST['german']);

        if (writeLanguageFile($__iniGerman, $german)
                && writeLanguageFile($__iniEnglish, $english)
                && delTree($__languageCache)) {

            $successMessage = 'Die Eingaben wurden erfolgreich gespeichert.';
        }
    }
    else {
        $german = readLanguageFile($__iniGerman);
        $english = readLanguageFile($__iniEnglish);

        $tagsGerman = array_keys($german);
        $tagsEnglish = array_keys($english);

        $tags = array_unique(array_merge($tagsEnglish, $tagsGerman));

        sort($tags);
    }
?>

<html lang="de">
    <style>
        body {
            width: 1080px;
            margin: 100px auto auto;
        }

        input[type="submit"] {
            margin: 10px auto;
        }

        form {
            text-align: center;
            width: 75%;
            float: left;
        }

        table {
            width:100%;
            border-collapse: collapse;
            border-spacing: 0;
        }
        th {
            text-align: center;
            font-size: large;
            padding-bottom: 8px;
            background-color: #8ab5d7;
        }
        tr:nth-child(even) {
            background-color: #e0e6ef;
        }

        span {
            font-weight: bold;
            font-size: large;
            font-style: italic;
        }

        .success {
            width: 100%;
            background-color: #56d169;
            text-align: center;
            font-size: larger;
            font-weight: bold;
            margin: 15px 0;
            padding: 5px;
        }

        .tag-input, .text-input {
            width: 100%;
            min-width: 180px;
        }

        .tag-input {
            pointer-events: none;
            border: none;
            background-color: #fff0;
            padding: 5px;
        }

        .text-input {
            height: 10em;
            resize: none;
        }

        .tag-col {
            width: 20%;
            vertical-align: top;
        }

        .text-col {
            width: 40%;
        }

        .tag-information {
            width: 21%;
            float: right;
            background-color: #8ab5d74d;
            padding: 10px;
        }
    </style>

    <?php if(!$loggedIn): ?>
        <div>
            <h2>Bitte loggen Sie sich ein!</h2>
            <form action="dynMessages.php" method="post">
                <label>Nutzername:</label> <input type="text" name="user">
                <label>Passwort:</label> <input type="password" name="pass">
                <input type="submit" value="Login">
            </form>
        </div>
    <?php else: ?>
        <?php if(!empty($successMessage)): ?>
            <div class="success"><?php echo $successMessage; ?></div>
        <?php endif; ?>

        <form action="dynMessages.php" method="post">
            <table>
                <thead>
                    <tr>
                        <th class="tag-col">Textbezeichner</th>
                        <th class="text-col">Deutsch</th>
                        <th class="text-col">Englisch</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($tags as $tag): ?>
                    <tr>
                        <td class="tag-col">
                            <input class="tag-input" type="text" name="text_key[]" value="<?php echo $tag; ?>">
                        </td>
                        <td class="text-col">
                            <textarea class="text-input" rows=5 name="german[]"><?php echo $german[$tag]; ?></textarea>
                        </td>
                        <td class="text-col">
                            <textarea class="text-input" rows=5 name="english[]"><?php echo $english[$tag]; ?></textarea>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <input type="submit" value="Speichern">
        </form>
    <div class="tag-information">
        <p><span>Information:</span> Zeilenumbrüche die durch die Enter-Taste eingefügt wurden werden automatisch in HTML-Zeilenumbrüche umgewandelt.
            Zudem werden alle HTML-Tags, außer dem<a>-Tag, automatisch aus dem Text entfernt.</p>
        <p><span>message_under_search_box:</span> Text für eine Hinweisbox, die unter dem Suchfeld angezeigt wird,</p>
    </div>
    <? endif; ?>
</html>
