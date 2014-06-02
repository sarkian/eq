<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
    <title><?= htmlentities($this->page_title) ?></title>
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
        background-color: #222222;
        color: #ffffff;
        font-family: verdana, arial, serif;
        font-size: 14px;
    }

    #footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        height: 40px;
    }

    #powered {
        font-size: 12px;
        font-style: italic;
        text-align: right;
        border-top: 1px #454545 solid;
        margin: 0 3%;
        padding: 0 2px;
    }

    .content {
        padding: 6px 20px 0 20px;
    }

    hr {
        margin-top: 16px;
        margin-bottom: 16px;
        border: 0;
        border-top: 1px solid #555;
    }

    .filename {
        color: #66D9EF;
    }

    .funcname {
        color: #A6E22E;
    }

    a {
        color: #0CE3AC;
        text-decoration: none;
    }

    a:hover {
        text-decoration: underline;
    }

    h3 {
        font-weight: 400;
        font-size: 22px;
        margin: 10px 2px;
    }

    h4 {
        font-weight: 400;
        font-size: 18px;
        margin: 8px 2px;
    }

    .mute {
        color: #999;
    }

    .exception-info-table {

    }

    .exception-info-table td {
        line-height: 14px;
        padding: 4px;
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
    }

    .exception-info-table td.row-name {
        padding-right: 4px;
        color: #ccc;
    }

    .exception-info-table td.row-value {
        padding-left: 4px;
    }

    /* Trace */

    .trace-step {
        display: block;
        margin: 16px 0;
        background-color: #151515;
        border-radius: 3px;
    }

    .trace-step .trace-step-header {
        display: block;
        padding: 6px 10px;
        border-bottom: 1px #333 solid;
    }

    .trace-step-header.no-border {
        border-bottom: none;
    }

    .trace-step-header .trace-step-num {
        color: #999;
    }

    .trace-step-header .trace-step-location {
        color: #cccccc;
    }

    .trace-step .trace-step-desc {
        display: block;
        height: 30px;
        line-height: 30px;
        padding: 0 10px;
    }

    .trace-step-desc span {
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
        float: left;
    }

    .trace-step-header .toggle-code-link {
        float: right;
    }

    /* Code */

    .trace-step-code {
        background-color: #151515;
        border-top: 1px #333333 solid;
        font-family: Menlo, Monaco, Consolas, "Courier New", monospace;
        font-size: 12px;
        border-radius: 0 0 5px 5px;
        padding: 6px 0;
        max-height: 221px;
        overflow: hidden;
        transition: max-height 0.3s ease-in-out, border-top-width 0.3s ease-in-out, padding 0.3s ease-in-out;
        -moz-transition: max-height 0.3s ease-in-out, border-top-width 0.3s ease-in-out, padding 0.3s ease-in-out;
        -webkit-transition: max-height 0.3s ease-in-out, border-top-width 0.3s ease-in-out, padding 0.3s ease-in-out;
        -o-transition: max-height 0.3s ease-in-out, border-top-width 0.3s ease-in-out, padding 0.3s ease-in-out;
        position: relative;
    }

    .trace-step-code.collapsed {
        max-height: 0;
        padding: 0;
        border-top-width: 0;
    }

    .trace-step-code .line {
        display: block;
        height: 16px;
        line-height: 16px;
        background-color: #1c1c1c;
    }

    .trace-step-code .line.cursor {
        background-color: #121212;
    }

    .trace-step-code .line .line-num {
        display: inline-block;
        float: left;
        width: 40px;
        height: 16px;
        line-height: 16px;
        background-color: #151515;
        text-align: right;
        margin-right: 10px;
        padding-right: 4px;
        color: #555;
    }

    .trace-step-code .line.cursor .line-num {
        background-color: #121212;
    }

    .trace-step-code .line.cursor .line-num {
        color: #aaa;
    }

    .trace-step-code .gradient-top, .trace-step-code .gradient-bottom {
        position: absolute;
        left: 44px;
        right: 0;
        height: 33px;
    }

    .trace-step-code .gradient-top {
        top: 0;
        background: linear-gradient(to bottom, rgba(21, 21, 21, 1), rgba(21, 21, 21, 0));
    }

    .trace-step-code .gradient-bottom {
        bottom: 0;
        background: linear-gradient(to top, rgba(21, 21, 21, 1), rgba(21, 21, 21, 0));
    }

    /* Highlighter */

    .hl-unknown {
        background-color: #990000;
        color: #fff;
    }

    .hl-default {
        color: #E3E0D7;
    }

    .hl-phptag {
        color: #E22E2E;
        font-weight: bold;
    }

    .hl-keyword {
        color: #66D9EF;
    }

    .hl-predefined {
        color: #5FD75F;
    }

    .hl-variable {
        color: #D9C76F;
    }

    .hl-comment {
        color: #75715E;
    }

    .hl-operator {
        color: #A8A8A8;
    }

    .hl-string {
        color: #5FAF00;
    }

    .hl-escape {
        color: #8CADE3;
    }

    .hl-constant {
        color: #AE81FF;
    }

    .hl-number {
        color: #AE81FF;
    }

    .hl-braces {
        color: #8CADE3;
    }

    .hl-brackets {
        color: #8CADE3;
    }

    .hl-parentheses {
        color: #8CADE3;
    }

    </style>

    <script type="text/javascript">
        function toggleCode(id) {
            document.getElementById('code-' + id).classList.toggle('collapsed');
        }
    </script>

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

</body>
</html>
