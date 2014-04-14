<!DOCTYPE html>
<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
<title><?= EQ::app()->client_script->title ?></title>
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

.error {
    padding-bottom: 50px;
}

.footer {
    position: relative;
	margin-top: -50px;
	height: 50px;
	clear: both;
} 

.container {
    text-align: center;
}

.error {
    padding: 20px 0 30px 0;
}

.status {
    font-size: 30px;
    font-weight: bold;
    margin: 0 0 20px 0;
}

.message {
    font-size: 16px;
}

.powered {
    margin: 0 3%;
    padding: 0 2px;
    border-top: 1px #666 solid;
    text-align: right;
    font-style: italic;
    font-size: 12px;
    color: #999;
}

</style>
</head>
<body>
    
    <div class="container">
        <div class="error clearfix">
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
