<!DOCTYPE html>
<html>
<head>
    <title><?= $this->page_title ?></title>
    <style type="text/css">
        html, body {
            margin: 0;
            padding: 0 0.7em;
            background: #333;
            color: #f0f0f0;
            font-family: verdana, arial;
            font-size: 14px;
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
        .red {
            color: #ff7777;
        }
        .code {
            font-family: monospace;
            /*font-size: 16px;*/
            background: #222;
            padding: 0.2em 0.5em;
        }
        .trace-step {
            padding: 0 2em;
            margin: 0.5em;
        }
        .trace-step span {
            display: block;
        }
        .trace-step span:first-child {
            margin-left: -2em;
        }
    </style>
</head>
<body>
    <?= $content ?>
</body>
</html>
