<html>
<head>
    <title>SugarCRM Language File Repair</title>
    <link rel="stylesheet" type="text/css" href="custom/modules/Administration/fixLanguageFiles.css">
</head>
<body>
{$TITLE}
<div id="bar_blank">
    <div id="bar_color"></div>
</div>
<div id="status"></div>
<form action="index.php" method="POST" id="fixLanguageFiles" enctype="multipart/form-data">
    <input type="hidden" name="action" value="fixLanguageFiles">
    <input type="hidden" name="module" value="Administration">
    <input type="hidden" name="step" value="start">

    <div id="checkboxes">
        <fieldset>
            <legend><b>Options:</b></legend>
            <label for="makeBackups">
                <input type="checkbox" name="makeBackups" id="makeBackups" value="1">
                <span>
                    Make Backups of files that are altered
                </span>
            </label><br>
            <label for="deleteEmpty">
                <input type="checkbox" name="deleteEmpty" id="deleteEmpty" value="1">
                <span>
                    Delete empty language files
                </span>
            </label><br>
            <label for="lowLevelLog">
                <input type="checkbox" name="lowLevelLog" id="lowLevelLog" value="1" CHECKED>
                <span>
                    Debug Logging
                </span>
            </label><br>
            <label for="compressWhitespace">
                <input type="checkbox" name="compressWhitespace" id="compressWhitespace" value="1" CHECKED>
                <span>
                    Compress any large areas of whitespace
                </span>
            </label><br>
        </fieldset>
    </div>
    <br><input type="button" id='start_button' value="Start Test">
</form>
<script type="text/javascript" src="custom/modules/Administration/fixLanguageFiles.js"></script>
</body>
</html>