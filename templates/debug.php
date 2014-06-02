<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
    <title><?= htmlentities($this->page_title) ?></title>
    <link rel="stylesheet" href="/tmp/debug.css" />
</head>
<body>

    <div class="container">
        <div class="content">
            {{$PAGE_CONTENT}}
        </div>
    </div>

    <div id="footer">
        <div id="powered" class="mute">
            <?= htmlentities(EQ::powered()) ?>
        </div>
    </div>

    <script type="text/javascript" src="/tmp/debug.js"></script>

</body>
</html>
