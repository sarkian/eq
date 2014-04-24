<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=yes">
    {{$HEAD_CONTENT}}
</head>
<body>

    <?= (new \favto\widgets\Navbar())->render() ?>

    <div class="container">
        {{$BODY_BEGIN_CONTENT}}
        <?= $content ?>
        {{$BODY_END_CONTENT}}
    </div>

</body>
</html>
