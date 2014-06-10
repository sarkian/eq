<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
    {{$HEAD_CONTENT}}
</head>
<body>
{{$BODY_BEGIN_CONTENT}}

    <?= EQ::app()->module("eq:navigation")->renderNav("admin") ?>

    <div class="container">
        {{$PAGE_CONTENT}}
    </div>

    <div id="footer">
        <div class="container">
            <p class="text-muted powered"><?= EQ::powered() ?></p>
        </div>
    </div>

{{$BODY_END_CONTENT}}
</body>
</html>
