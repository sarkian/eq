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

body {
    background-color: #333;
    color: #f0f0f0;
    font-family: verdana, arial;
    font-size: 14px;
}

html, body, .container {
    width: 100%;
    height: 100%;
}

.clearfix:after {
    content: ".";
	display: block;
	height: 0;
	clear: both;
	visibility: hidden;
}

.clearfix {
    display: inline-block;
}

* html .clearfix {
    height: 1%;
}

.clearfix {
    display: block;
}

body > .container {
    height: auto; min-height: 100%;
}

.footer {
    position: relative;
	margin-top: -50px;
	height: 50px;
	clear: both;
} 

.powered {
    margin: 0 100px;
    padding: 0 2px;
    border-top: 1px #666 solid;
    text-align: right;
    font-style: italic;
    font-size: 12px;
    color: #999;
}




.content {
    padding: 0 20px;
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

    <div class="container">
        <div class="content clearfix">
            <?= $content ?>
        </div>
    </div>
    <div class="footer">
        <div class="powered">
            <?= EQ::powered() ?>
        </div>
    </div>

</body>
</html>
