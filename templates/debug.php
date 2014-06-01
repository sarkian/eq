<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
<title><?= $this->page_title ?></title>
<style type="text/css">

    html, body {
        margin: 0;
        padding: 0;
    }

    html {
        position: relative;
        min-height: 100%;
    }

    body {
        margin-bottom: 40px;
        padding-bottom: 30px;
        background-color: #333;
        color: #f0f0f0;
        font-family: verdana, arial;
        font-size: 14px;
    }

    #footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        height: 40px;
    }

    .powered {
        font-size: 12px;
        font-style: italic;
        text-align: right;
        border-top: 1px #555555 solid;
        margin: 0 3%;
        padding: 0 2px;
        color: #888888;
    }




    .content {
        padding: 0 20px;
    }

    hr {
        margin-top: 16px;
        margin-bottom: 16px;
        border: 0;
        border-top: 1px solid #666;
    }

    .filename {
        color: #66D9EF;
    }

    a {
        color: #b9c6ff;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    td {
        vertical-align: top;
        padding: 5px;
    }

    .etype {
        margin: 0;
        padding: 20px 0 10px 0;
    }

    .red {
        color: #ff7777;
    }

    .code {
        font-family: monospace;
        background: #222;
        padding: 0.2em 0.5em;
    }

    .trace-step {
        /*padding: 0 2em;*/
        margin: 10px;
    }

    .stepnum {
        margin-left: -14px;
    }

</style>
</head>
<body>

    <div class="container">
        <div class="content">
            <?= $content ?>
        </div>
    </div>

    <div id="footer">
        <div class="powered">
            <?= EQ::powered() ?>
        </div>
    </div>

</body>
</html>
